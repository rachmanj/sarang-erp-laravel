<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\CashExpense;
use App\Services\Accounting\PostingService;
use App\Services\DocumentNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CashExpenseController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService
    ) {
        $this->middleware(['auth']);
    }

    public function index()
    {
        return view('cash_expenses.index');
    }

    public function create()
    {
        $expenseAccounts = DB::table('accounts')->where('type', 'expense')->where('is_postable', 1)->orderBy('code')->get();
        $cashAccounts = DB::table('accounts')->where('code', 'like', '1.1.1%')->where('is_postable', 1)->orderBy('code')->get();
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
            'amount_raw' => ['nullable', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer'],
            'fund_id' => ['nullable', 'integer'],
            'dept_id' => ['nullable', 'integer'],
        ]);

        // Use amount_raw if available (from formatted input), otherwise use amount
        $amount = $data['amount_raw'] ?? $data['amount'];

        return DB::transaction(function () use ($data, $amount) {
            $exp = CashExpense::create([
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'account_id' => $data['expense_account_id'],
                'amount' => $amount,
                'status' => 'posted',
                'created_by' => Auth::id(),
            ]);

            // Generate expense number
            $expenseNo = $this->documentNumberingService->generateNumber('cash_expense', $data['date']);
            $exp->update(['expense_no' => $expenseNo]);

            // Post journal: Debit Expense, Credit Cash
            $this->posting->postJournal([
                'date' => $exp->date,
                'description' => 'Cash Expense ' . $expenseNo,
                'source_type' => 'cash_expense',
                'source_id' => $exp->id,
                'lines' => [
                    ['account_id' => (int)$data['expense_account_id'], 'debit' => (float)$amount, 'credit' => 0, 'project_id' => $data['project_id'] ?? null, 'fund_id' => $data['fund_id'] ?? null, 'dept_id' => $data['dept_id'] ?? null, 'memo' => $data['description'] ?? null],
                    ['account_id' => (int)$data['cash_account_id'], 'debit' => 0, 'credit' => (float)$amount, 'project_id' => $data['project_id'] ?? null, 'fund_id' => $data['fund_id'] ?? null, 'dept_id' => $data['dept_id'] ?? null, 'memo' => $data['description'] ?? null],
                ],
            ]);

            return redirect()->route('cash-expenses.index')->with('success', 'Cash expense posted');
        });
    }

    public function data(Request $request)
    {
        $q = DB::table('cash_expenses as ce')
            ->leftJoin('accounts as a', 'a.id', '=', 'ce.account_id')
            ->leftJoin('users as u', 'u.id', '=', 'ce.created_by')
            ->leftJoin('journals as j', function ($join) {
                $join->on('j.source_type', '=', DB::raw("'cash_expense'"))
                    ->on('j.source_id', '=', 'ce.id');
            })
            ->leftJoin('journal_lines as jl', function ($join) {
                $join->on('jl.journal_id', '=', 'j.id')
                    ->where('jl.credit', '>', 0);
            })
            ->leftJoin('accounts as ca', 'ca.id', '=', 'jl.account_id')
            ->select('ce.id', 'ce.date', 'ce.description', 'a.code as expense_code', 'a.name as expense_name', 'ce.amount', 'u.name as creator_name', 'ca.code as cash_code', 'ca.name as cash_name');

        return DataTables::of($q)
            ->addIndexColumn()
            ->editColumn('date', function ($row) {
                return (string)$row->date;
            })
            ->editColumn('amount', function ($row) {
                return (float)$row->amount;
            })
            ->addColumn('cash_account', function ($row) {
                return $row->cash_code ? $row->cash_code . ' - ' . $row->cash_name : 'N/A';
            })
            ->addColumn('actions', function ($row) {
                return '<a href="/cash-expenses/' . $row->id . '/print" target="_blank" class="btn btn-sm btn-info" title="Print"><i class="fas fa-print"></i></a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function print(CashExpense $cashExpense)
    {
        // Load necessary relationships
        $cashExpense->load([
            'expenseAccount',
            'creator',
            'project',
            'fund',
            'department'
        ]);

        // Get cash account from journal lines
        $cashAccount = DB::table('journals as j')
            ->join('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->where('j.source_type', 'cash_expense')
            ->where('j.source_id', $cashExpense->id)
            ->where('jl.credit', '>', 0)
            ->select('a.id', 'a.code', 'a.name')
            ->first();

        // Convert amount to words (Indonesian)
        $terbilang = $this->convertToWords($cashExpense->amount);

        return view('cash_expenses.print', compact('cashExpense', 'cashAccount', 'terbilang'));
    }

    private function convertToWords($number)
    {
        $ones = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
            'dua belas',
            'tiga belas',
            'empat belas',
            'lima belas',
            'enam belas',
            'tujuh belas',
            'delapan belas',
            'sembilan belas'
        ];

        $tens = [
            '',
            '',
            'dua puluh',
            'tiga puluh',
            'empat puluh',
            'lima puluh',
            'enam puluh',
            'tujuh puluh',
            'delapan puluh',
            'sembilan puluh'
        ];

        $hundreds = [
            '',
            'seratus',
            'dua ratus',
            'tiga ratus',
            'empat ratus',
            'lima ratus',
            'enam ratus',
            'tujuh ratus',
            'delapan ratus',
            'sembilan ratus'
        ];

        $thousands = [
            '',
            'seribu',
            'dua ribu',
            'tiga ribu',
            'empat ribu',
            'lima ribu',
            'enam ribu',
            'tujuh ribu',
            'delapan ribu',
            'sembilan ribu'
        ];

        if ($number == 0) {
            return 'nol rupiah';
        }

        $result = '';
        $number = (int)$number;

        // Handle millions
        if ($number >= 1000000) {
            $millions = intval($number / 1000000);
            if ($millions == 1) {
                $result .= 'satu juta ';
            } else {
                $result .= $this->convertToWords($millions) . ' juta ';
            }
            $number %= 1000000;
        }

        // Handle thousands
        if ($number >= 1000) {
            $thousand = intval($number / 1000);
            if ($thousand == 1) {
                $result .= 'seribu ';
            } else {
                $result .= $this->convertToWords($thousand) . ' ribu ';
            }
            $number %= 1000;
        }

        // Handle hundreds
        if ($number >= 100) {
            $hundred = intval($number / 100);
            if ($hundred == 1) {
                $result .= 'seratus ';
            } else {
                $result .= $hundreds[$hundred] . ' ';
            }
            $number %= 100;
        }

        // Handle tens and ones
        if ($number >= 20) {
            $ten = intval($number / 10);
            $result .= $tens[$ten] . ' ';
            $number %= 10;
        } elseif ($number >= 10) {
            $result .= $ones[$number] . ' ';
            $number = 0;
        }

        if ($number > 0) {
            $result .= $ones[$number] . ' ';
        }

        return trim($result) . ' rupiah';
    }
}
