<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Services\Accounting\JournalSourceUrlResolver;
use App\Services\Reports\ReportService;
use App\Exports\AccountLedgerExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AccountController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth']);
        $this->middleware('permission:accounts.view')->only(['index', 'show', 'export']);
        $this->middleware('permission:accounts.manage')->only(['create', 'store', 'edit', 'update']);
    }

    public function index(Request $request)
    {
        $query = Account::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $accounts = $query->orderBy('code')->paginate(20)->appends($request->query());

        $accountTypes = [
            'asset' => 'Asset',
            'liability' => 'Liability',
            'net_assets' => 'Net Assets',
            'income' => 'Income',
            'expense' => 'Expense',
        ];

        return view('accounts.index', compact('accounts', 'accountTypes'));
    }

    public function show(
        Request $request,
        Account $account,
        ReportService $reportService,
        JournalSourceUrlResolver $sourceUrlResolver,
    ) {
        [$from, $to, $onlyPosted, $ledger, $rows] = $this->buildLedgerData(
            $request,
            $account,
            $reportService,
            $sourceUrlResolver
        );

        return view('accounts.show', [
            'account' => $account,
            'ledger' => $ledger,
            'rows' => $rows,
            'from' => $from,
            'to' => $to,
            'onlyPosted' => $onlyPosted,
        ]);
    }

    public function export(
        Request $request,
        Account $account,
        ReportService $reportService,
        JournalSourceUrlResolver $sourceUrlResolver,
    ) {
        [$from, $to, $onlyPosted, $ledger, $rows] = $this->buildLedgerData(
            $request,
            $account,
            $reportService,
            $sourceUrlResolver
        );

        $sheet = [
            ['Account', $account->code.' — '.$account->name],
            ['Period', $from.' to '.$to],
            ['Type', strtoupper($account->type)],
            ['Opening Balance', round((float) $ledger['opening_balance'], 2)],
            [],
            ['Date', 'Journal No', 'Description', 'Source Document', 'Debit', 'Credit', 'Balance'],
        ];

        foreach ($rows as $row) {
            $sheet[] = [
                $row['date'],
                $row['journal_no'] ?? '—',
                $row['memo'] ?: ($row['journal_desc'] ?? '—'),
                $row['source_label'] ?? '—',
                round((float) $row['debit'], 2),
                round((float) $row['credit'], 2),
                round((float) $row['balance'], 2),
            ];
        }

        $sheet[] = [];
        $sheet[] = [
            '', '', '', 'Totals',
            round((float) $ledger['total_debit'], 2),
            round((float) $ledger['total_credit'], 2),
            round((float) $ledger['closing_balance'], 2),
        ];

        $filename = 'account-ledger-'.$account->code.'-'.$from.'_'.$to.'.xlsx';

        return Excel::download(new AccountLedgerExport($sheet), $filename);
    }

    private function buildLedgerData(
        Request $request,
        Account $account,
        ReportService $reportService,
        JournalSourceUrlResolver $sourceUrlResolver,
    ): array {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());
        $onlyPosted = ! $request->boolean('include_unposted');

        $ledger = $reportService->getAccountLedger($account->id, [
            'from' => $from,
            'to' => $to,
            'company_entity_id' => $request->input('company_entity_id'),
        ], $onlyPosted);

        $rows = collect($ledger['rows'])->map(function (array $row) use ($sourceUrlResolver) {
            $row['source_url'] = $sourceUrlResolver->resolve(
                $row['source_type'],
                $row['source_id'],
                auth()->user()
            );
            $row['source_label'] = $sourceUrlResolver->label(
                $row['source_type'],
                $row['source_id'],
                $row['journal_no']
            );

            return $row;
        })->all();

        return [$from, $to, $onlyPosted, $ledger, $rows];
    }

    public function create()
    {
        $parents = Account::query()->orderBy('code')->get();

        return view('accounts.create', compact('parents'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,net_assets,income,expense'],
            'is_postable' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);
        Account::create($data);

        return redirect()->route('accounts.index')->with('success', 'Account created');
    }

    public function edit(Account $account)
    {
        $parents = Account::where('id', '!=', $account->id)->orderBy('code')->get();

        return view('accounts.edit', compact('account', 'parents'));
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:accounts,code,'.$account->id],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,net_assets,income,expense'],
            'is_postable' => ['required', 'boolean'],
            'parent_id' => ['nullable', 'integer', 'exists:accounts,id'],
        ]);
        $account->update($data);

        return redirect()->route('accounts.index')->with('success', 'Account updated');
    }
}
