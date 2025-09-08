<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\CashExpense;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CashExpenseController extends Controller
{
    public function __construct(private PostingService $posting)
    {
        $this->middleware(['auth']);
    }

    public function index()
    {
        return view('cash_expenses.index');
    }

    public function create()
    {
        $expenseAccounts = DB::table('accounts')->where('type', 'expense')->where('is_postable', 1)->orderBy('code')->get();
        $cashAccounts = DB::table('accounts')->where('code', 'like', '1.1.2%')->where('is_postable', 1)->orderBy('code')->get();
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $funds = DB::table('funds')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        return view('cash_expenses.create', compact('expenseAccounts', 'cashAccounts', 'projects', 'funds', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer'],
            'fund_id' => ['nullable', 'integer'],
            'dept_id' => ['nullable', 'integer'],
        ]);

        return DB::transaction(function () use ($data) {
            $exp = CashExpense::create([
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'account_id' => $data['expense_account_id'],
                'amount' => $data['amount'],
                'status' => 'posted',
            ]);

            // Post journal: Debit Expense, Credit Cash
            $this->posting->postJournal([
                'date' => $exp->date,
                'description' => 'Cash Expense #' . $exp->id,
                'source_type' => 'cash_expense',
                'source_id' => $exp->id,
                'lines' => [
                    ['account_id' => (int)$data['expense_account_id'], 'debit' => (float)$data['amount'], 'credit' => 0, 'project_id' => $data['project_id'] ?? null, 'fund_id' => $data['fund_id'] ?? null, 'dept_id' => $data['dept_id'] ?? null, 'memo' => $data['description'] ?? null],
                    ['account_id' => (int)$data['cash_account_id'], 'debit' => 0, 'credit' => (float)$data['amount'], 'project_id' => $data['project_id'] ?? null, 'fund_id' => $data['fund_id'] ?? null, 'dept_id' => $data['dept_id'] ?? null, 'memo' => $data['description'] ?? null],
                ],
            ]);

            return redirect()->route('cash-expenses.index')->with('success', 'Cash expense posted');
        });
    }

    public function data(Request $request)
    {
        $q = DB::table('cash_expenses as ce')
            ->leftJoin('accounts as a', 'a.id', '=', 'ce.account_id')
            ->select('ce.id', 'ce.date', 'ce.description', 'a.code as expense_code', 'ce.amount');
        return DataTables::of($q)
            ->editColumn('date', function ($row) {
                return (string)$row->date;
            })
            ->editColumn('amount', function ($row) {
                return number_format((float)$row->amount, 2);
            })
            ->toJson();
    }
}
