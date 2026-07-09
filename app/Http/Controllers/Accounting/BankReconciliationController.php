<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExcludeReconciliationLineRequest;
use App\Http\Requests\ManualMatchGroupBankReconciliationRequest;
use App\Http\Requests\StoreBankReconciliationRequest;
use App\Http\Requests\StoreBankStatementLineRequest;
use App\Jobs\Bank\AutoMatchReconciliationJob;
use App\Jobs\Bank\FetchBookGlLinesJob;
use App\Jobs\Bank\ParseBankStatementJob;
use App\Models\Bank\BankAccount;
use App\Models\Bank\BankBookLine;
use App\Models\Bank\BankReconciliation;
use App\Models\Bank\BankStatementLine;
use App\Models\Bank\ReconciliationMatchGroup;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\KoranDashboardService;
use App\Services\Bank\ReconciliationBalanceService;
use App\Services\Bank\ReconciliationMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Yajra\DataTables\Facades\DataTables;

class BankReconciliationController extends Controller
{
    public function __construct(
        private BankReconciliationService $reconciliationService,
        private ReconciliationMatchingService $matchingService,
        private ReconciliationBalanceService $balanceService,
        private KoranDashboardService $koranDashboardService,
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:bank_reconciliation.view')->only([
            'index', 'sessions', 'show', 'data', 'status', 'report', 'koranCell', 'statementPdf',
        ]);
        $this->middleware('permission:bank_reconciliation.import')->only(['create', 'store']);
        $this->middleware('permission:bank_reconciliation.reconcile')->only([
            'parse', 'fetchBook', 'autoMatch', 'match', 'unmatch',
            'storeLine', 'updateLine', 'destroyLine',
            'excludeBankLine', 'excludeBookLine',
        ]);
        $this->middleware('permission:bank_reconciliation.finalize')->only(['finalize']);
    }

    public function index(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $dashboard = $this->koranDashboardService->buildMatrix($year);

        return view('bank-reconciliation.koran', [
            'year' => $dashboard['year'],
            'months' => $dashboard['months'],
            'accounts' => $dashboard['accounts'],
            'matrix' => $dashboard['matrix'],
        ]);
    }

    public function sessions()
    {
        return view('bank-reconciliation.sessions');
    }

    public function koranCell(Request $request)
    {
        $data = $request->validate([
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        return response()->json($this->koranDashboardService->cellFor(
            (int) $data['bank_account_id'],
            (int) $data['year'],
            (int) $data['month'],
        ));
    }

    public function create()
    {
        $bankAccounts = BankAccount::query()->where('is_active', true)->orderBy('name')->get();

        return view('bank-reconciliation.create', compact('bankAccounts'));
    }

    public function store(StoreBankReconciliationRequest $request)
    {
        $data = $request->validated();
        $bankAccount = BankAccount::query()->findOrFail($data['bank_account_id']);
        $periode = date('Y-m-01', strtotime($data['periode']));

        try {
            if ($data['source_mode'] === 'manual') {
                $reconciliation = $this->reconciliationService->createManualSession($bankAccount, $periode);
                FetchBookGlLinesJob::dispatch($reconciliation->id);

                return $this->redirectAfterStore($request, $reconciliation, 'Manual reconciliation session created.');
            }

            $reconciliation = $this->reconciliationService->createAiSession(
                $bankAccount,
                $periode,
                $request->file('file'),
            );

            ParseBankStatementJob::dispatch($reconciliation->id);

            return $this->redirectAfterStore($request, $reconciliation, 'Bank statement uploaded. Parsing in progress…');
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['periode' => $e->getMessage()]);
        }
    }

    private function redirectAfterStore(Request $request, BankReconciliation $reconciliation, string $message)
    {
        if ($request->input('redirect_to') === 'koran') {
            return redirect()
                ->route('bank-reconciliation.index', ['year' => $reconciliation->periode?->format('Y')])
                ->with('success', $message);
        }

        return redirect()
            ->route('bank-reconciliation.show', $reconciliation)
            ->with('success', $message);
    }

    public function show(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->load([
            'bankAccount.account',
            'statement',
            'bankLines' => fn ($q) => $q->orderBy('posting_date')->orderBy('line_order')->orderBy('id'),
            'bookLines' => fn ($q) => $q->orderBy('posting_date')->orderBy('id'),
            'matchGroups.bankLines',
            'matchGroups.bookLines',
        ]);

        $balance = $this->balanceService->statusPayload($bankReconciliation);

        return view('bank-reconciliation.show', compact('bankReconciliation', 'balance'));
    }

    public function status(BankReconciliation $bankReconciliation)
    {
        return response()->json($this->balanceService->statusPayload($bankReconciliation->fresh()));
    }

    public function report(BankReconciliation $bankReconciliation)
    {
        if ($bankReconciliation->status !== BankReconciliation::STATUS_COMPLETED) {
            abort(404);
        }

        $bankReconciliation->load([
            'bankAccount.account',
            'bankLines',
            'bookLines',
            'matchGroups.bankLines',
            'matchGroups.bookLines',
            'finalizedBy',
        ]);

        $balance = $this->balanceService->statusPayload($bankReconciliation);

        return view('bank-reconciliation.report', compact('bankReconciliation', 'balance'));
    }

    public function statementPdf(BankReconciliation $bankReconciliation): Response
    {
        $bankReconciliation->loadMissing('statement');
        $statement = $bankReconciliation->statement;
        $filePath = $statement?->file_path;

        if (! is_string($filePath) || $filePath === '' || ! Storage::disk('local')->exists($filePath)) {
            abort(404);
        }

        $filename = $statement->original_filename ?? 'bank-statement.pdf';
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]+/', '_', $filename) ?: 'bank-statement.pdf';

        return response()->file(
            Storage::disk('local')->path($filePath),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$safeFilename.'"',
            ]
        );
    }

    public function parse(BankReconciliation $bankReconciliation)
    {
        if ($bankReconciliation->source_mode !== BankReconciliation::SOURCE_AI) {
            return back()->withErrors(['error' => 'Only AI sessions can be re-parsed.']);
        }

        $bankReconciliation->update(['status' => BankReconciliation::STATUS_PROCESSING]);
        ParseBankStatementJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'Re-parse queued.');
    }

    public function fetchBook(BankReconciliation $bankReconciliation)
    {
        FetchBookGlLinesJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'Book line fetch queued.');
    }

    public function autoMatch(BankReconciliation $bankReconciliation)
    {
        AutoMatchReconciliationJob::dispatch($bankReconciliation->id);

        return back()->with('success', 'Auto-match queued.');
    }

    public function match(
        ManualMatchGroupBankReconciliationRequest $request,
        BankReconciliation $bankReconciliation,
    ) {
        try {
            $this->matchingService->manualMatch(
                $bankReconciliation,
                $request->validated('bank_line_ids'),
                $request->validated('book_line_ids'),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Manual match group created.');
    }

    public function unmatch(BankReconciliation $bankReconciliation, ReconciliationMatchGroup $group)
    {
        try {
            $this->matchingService->unmatchGroup($bankReconciliation, $group);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Match group removed.');
    }

    public function storeLine(
        StoreBankStatementLineRequest $request,
        BankReconciliation $bankReconciliation,
    ) {
        try {
            $this->reconciliationService->addBankLine($bankReconciliation, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Bank line added.');
    }

    public function updateLine(
        StoreBankStatementLineRequest $request,
        BankReconciliation $bankReconciliation,
        BankStatementLine $line,
    ) {
        try {
            $this->reconciliationService->updateBankLine($bankReconciliation, $line, $request->validated());
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Bank line updated.');
    }

    public function destroyLine(
        BankReconciliation $bankReconciliation,
        BankStatementLine $line,
    ) {
        try {
            $this->reconciliationService->deleteBankLine($bankReconciliation, $line);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Bank line deleted.');
    }

    public function excludeBankLine(
        ExcludeReconciliationLineRequest $request,
        BankReconciliation $bankReconciliation,
        BankStatementLine $line,
    ) {
        try {
            $this->reconciliationService->setBankLineExcluded(
                $bankReconciliation,
                $line,
                (bool) $request->validated('exclude'),
                $request->validated('exclude_reason'),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Bank line updated.');
    }

    public function excludeBookLine(
        ExcludeReconciliationLineRequest $request,
        BankReconciliation $bankReconciliation,
        BankBookLine $bookLine,
    ) {
        try {
            $this->reconciliationService->setBookLineExcluded(
                $bankReconciliation,
                $bookLine,
                (bool) $request->validated('exclude'),
                $request->validated('exclude_reason'),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Book line updated.');
    }

    public function finalize(BankReconciliation $bankReconciliation)
    {
        try {
            $this->reconciliationService->finalize($bankReconciliation);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()
            ->route('bank-reconciliation.report', $bankReconciliation)
            ->with('success', 'Bank reconciliation completed.');
    }

    public function data(Request $request)
    {
        $query = BankReconciliation::query()
            ->with(['bankAccount', 'statement'])
            ->orderByDesc('periode');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::of($query)
            ->editColumn('periode', fn (BankReconciliation $row) => $row->periode?->format('M Y'))
            ->editColumn('closing_balance_bank', fn (BankReconciliation $row) => number_format((float) ($row->closing_balance_bank ?? $row->statement_closing ?? 0), 2))
            ->addColumn('bank_name', fn (BankReconciliation $row) => $row->bankAccount?->name ?? '-')
            ->addColumn('source_mode_label', fn (BankReconciliation $row) => strtoupper($row->source_mode ?? 'ai'))
            ->addColumn('status_label', function (BankReconciliation $row) {
                $class = match ($row->status) {
                    BankReconciliation::STATUS_COMPLETED => 'success',
                    BankReconciliation::STATUS_IN_REVIEW => 'warning',
                    BankReconciliation::STATUS_PROCESSING => 'info',
                    BankReconciliation::STATUS_FAILED => 'danger',
                    default => 'secondary',
                };

                return '<span class="badge badge-'.$class.'">'.strtoupper(str_replace('_', ' ', $row->status)).'</span>';
            })
            ->addColumn('actions', function (BankReconciliation $row) {
                $html = '<a href="'.route('bank-reconciliation.show', $row).'" class="btn btn-xs btn-primary">Open</a>';
                if ($row->status === BankReconciliation::STATUS_COMPLETED) {
                    $html .= ' <a href="'.route('bank-reconciliation.report', $row).'" class="btn btn-xs btn-secondary">Report</a>';
                }

                return $html;
            })
            ->rawColumns(['status_label', 'actions'])
            ->make(true);
    }
}
