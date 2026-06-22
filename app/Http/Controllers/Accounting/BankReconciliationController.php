<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Bank\BankAccount;
use App\Models\Bank\BankReconciliation;
use App\Services\Bank\BankReconciliationService;
use App\Services\Bank\BankReconciliationSupport;
use App\Services\Bank\BankStatementParser;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class BankReconciliationController extends Controller
{
    public function __construct(
        private BankStatementParser $statementParser,
        private BankReconciliationService $reconciliationService,
    ) {
        $this->middleware(['auth']);
        $this->middleware('permission:bank_reconciliation.view')->only(['index', 'show', 'data', 'bookData']);
        $this->middleware('permission:bank_reconciliation.import')->only(['importForm', 'import']);
        $this->middleware('permission:bank_reconciliation.reconcile')->only([
            'autoMatch', 'aiMatch', 'manualMatch', 'adjustment', 'ignoreLine',
        ]);
        $this->middleware('permission:bank_reconciliation.finalize')->only(['finalize']);
    }

    public function index()
    {
        return view('bank-reconciliation.index');
    }

    public function importForm()
    {
        $bankAccounts = BankAccount::query()->where('is_active', true)->orderBy('name')->get();

        return view('bank-reconciliation.import', compact('bankAccounts'));
    }

    public function import(Request $request)
    {
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'bank_account_id' => ['nullable', 'integer', 'exists:bank_accounts,id'],
        ]);

        $statement = $this->statementParser->importFromUpload(
            $request->file('file'),
            $data['bank_account_id'] ?? null,
        );

        $reconciliation = $this->reconciliationService->createSessionFromStatement($statement);

        return redirect()
            ->route('bank-reconciliation.show', $reconciliation)
            ->with('success', 'Bank statement imported successfully.');
    }

    public function show(BankReconciliation $bankReconciliation)
    {
        $bankReconciliation->load([
            'bankAccount.account',
            'statement.lines.match',
            'matches.statementLine',
        ]);

        $bookLines = $this->reconciliationService->getBookCandidates($bankReconciliation);
        $expenseAccounts = Account::query()
            ->where('is_postable', true)
            ->where('type', 'expense')
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('bank-reconciliation.show', compact('bankReconciliation', 'bookLines', 'expenseAccounts'));
    }

    public function autoMatch(BankReconciliation $bankReconciliation)
    {
        $count = $this->reconciliationService->autoMatch($bankReconciliation);

        return back()->with('success', "Auto-matched {$count} statement line(s).");
    }

    public function aiMatch(BankReconciliation $bankReconciliation)
    {
        $count = $this->reconciliationService->aiSuggestMatches($bankReconciliation);

        return back()->with('success', "AI suggested {$count} match(es).");
    }

    public function manualMatch(Request $request, BankReconciliation $bankReconciliation)
    {
        $data = $request->validate([
            'bank_statement_line_id' => ['required', 'integer'],
            'journal_line_id' => ['required', 'integer'],
        ]);

        $this->reconciliationService->manualMatch(
            $bankReconciliation,
            (int) $data['bank_statement_line_id'],
            (int) $data['journal_line_id'],
        );

        return back()->with('success', 'Manual match saved.');
    }

    public function adjustment(Request $request, BankReconciliation $bankReconciliation)
    {
        $data = $request->validate([
            'bank_statement_line_id' => ['required', 'integer'],
            'counter_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'memo' => ['nullable', 'string', 'max:255'],
        ]);

        $this->reconciliationService->createAdjustment(
            $bankReconciliation,
            (int) $data['bank_statement_line_id'],
            (int) $data['counter_account_id'],
            $data['memo'] ?? null,
        );

        return back()->with('success', 'Adjustment journal posted and line matched.');
    }

    public function ignoreLine(Request $request, BankReconciliation $bankReconciliation)
    {
        $data = $request->validate([
            'bank_statement_line_id' => ['required', 'integer'],
        ]);

        $this->reconciliationService->ignoreLine($bankReconciliation, (int) $data['bank_statement_line_id']);

        return back()->with('success', 'Statement line ignored.');
    }

    public function finalize(BankReconciliation $bankReconciliation)
    {
        $this->reconciliationService->finalize($bankReconciliation);

        return back()->with('success', 'Bank reconciliation finalized.');
    }

    public function data(Request $request)
    {
        $query = BankReconciliation::query()
            ->with(['bankAccount', 'statement'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::of($query)
            ->editColumn('period_start', fn (BankReconciliation $row) => $row->period_start?->format('d M Y'))
            ->editColumn('period_end', fn (BankReconciliation $row) => $row->period_end?->format('d M Y'))
            ->editColumn('statement_closing', fn (BankReconciliation $row) => number_format((float) $row->statement_closing, 2))
            ->editColumn('book_balance', fn (BankReconciliation $row) => $row->book_balance !== null ? number_format((float) $row->book_balance, 2) : '-')
            ->addColumn('bank_name', fn (BankReconciliation $row) => $row->bankAccount?->name ?? '-')
            ->addColumn('status_label', function (BankReconciliation $row) {
                $class = match ($row->status) {
                    'finalized' => 'success',
                    'open' => 'warning',
                    default => 'secondary',
                };

                return '<span class="badge badge-'.$class.'">'.strtoupper($row->status).'</span>';
            })
            ->addColumn('actions', fn (BankReconciliation $row) => '<a href="'.route('bank-reconciliation.show', $row).'" class="btn btn-xs btn-primary">Open</a>')
            ->rawColumns(['status_label', 'actions'])
            ->make(true);
    }

    public function bookData(BankReconciliation $bankReconciliation)
    {
        $rows = $this->reconciliationService->getBookCandidates($bankReconciliation)->map(function ($row) {
            $description = trim(($row->description ?? '').' '.($row->memo ?? ''));

            return [
                'journal_line_id' => $row->journal_line_id,
                'date' => $row->date,
                'date_display' => \Illuminate\Support\Carbon::parse($row->date)->format('d/m/Y'),
                'description' => $description,
                'debit' => number_format((float) $row->debit, 2),
                'credit' => number_format((float) $row->credit, 2),
                'amount' => number_format(BankReconciliationSupport::bookLineAmount($row), 2),
                'source_type' => $row->source_type,
            ];
        });

        return response()->json(['data' => $rows]);
    }
}
