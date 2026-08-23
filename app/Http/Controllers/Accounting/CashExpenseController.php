<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\CashExpense;
use App\Models\Accounting\CashExpenseLine;
use App\Services\Accounting\PostingService;
use App\Services\CompanyEntityService;
use App\Services\DocumentNumberingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class CashExpenseController extends Controller
{
    public function __construct(
        private PostingService $posting,
        private DocumentNumberingService $documentNumberingService,
        private CompanyEntityService $companyEntityService
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
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);

        return view('cash_expenses.create', compact('expenseAccounts', 'cashAccounts', 'projects', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount_raw' => ['nullable', 'numeric', 'min:0.01'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.amount' => ['required', 'numeric', 'min:0.01'],
            'lines.*.amount_raw' => ['nullable', 'numeric', 'min:0.01'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.project_id' => ['nullable', 'integer', 'exists:projects,id'],
            'lines.*.dept_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $totalAmount = isset($data['amount_raw'])
            ? (float) $data['amount_raw']
            : collect($data['lines'])->sum(fn (array $line) => (float) ($line['amount_raw'] ?? $line['amount']));

        return DB::transaction(function () use ($data, $totalAmount) {
            $entity = $this->companyEntityService->getDefaultEntity();

            $exp = CashExpense::create([
                'date' => $data['date'],
                'description' => $data['description'] ?? null,
                'cash_account_id' => $data['cash_account_id'],
                'total_amount' => $totalAmount,
                'status' => 'posted',
                'created_by' => Auth::id(),
                'company_entity_id' => $entity->id,
            ]);

            $expenseNo = $this->documentNumberingService->generateNumber('cash_expense', $data['date'], [
                'company_entity_id' => $entity->id,
            ]);
            $exp->update(['expense_no' => $expenseNo]);

            $journalLines = [];
            foreach ($data['lines'] as $line) {
                $lineAmount = (float) ($line['amount_raw'] ?? $line['amount']);

                CashExpenseLine::create([
                    'cash_expense_id' => $exp->id,
                    'account_id' => $line['expense_account_id'],
                    'amount' => $lineAmount,
                    'description' => $line['description'] ?? null,
                    'project_id' => $line['project_id'] ?? null,
                    'dept_id' => $line['dept_id'] ?? null,
                ]);

                $journalLines[] = [
                    'account_id' => (int) $line['expense_account_id'],
                    'debit' => $lineAmount,
                    'credit' => 0,
                    'project_id' => $line['project_id'] ?? null,
                    'dept_id' => $line['dept_id'] ?? null,
                    'memo' => $line['description'] ?? null,
                ];
            }

            $journalLines[] = [
                'account_id' => (int) $data['cash_account_id'],
                'debit' => 0,
                'credit' => $totalAmount,
                'memo' => $data['description'] ?? null,
            ];

            $this->posting->postJournal([
                'date' => $exp->date,
                'description' => 'Cash Expense '.$expenseNo,
                'source_type' => 'cash_expense',
                'source_id' => $exp->id,
                'lines' => $journalLines,
            ]);

            return redirect()->route('cash-expenses.index')->with('success', 'Cash expense posted');
        });
    }

    public function data(Request $request)
    {
        $q = DB::table('cash_expenses as ce')
            ->leftJoin('cash_expense_lines as cel', function ($join) {
                $join->on('cel.cash_expense_id', '=', 'ce.id')
                    ->whereIn('cel.id', function ($query) {
                        $query->selectRaw('MIN(id)')
                            ->from('cash_expense_lines')
                            ->groupBy('cash_expense_id');
                    });
            })
            ->leftJoin('accounts as a', 'a.id', '=', 'cel.account_id')
            ->leftJoin('accounts as ca', 'ca.id', '=', 'ce.cash_account_id')
            ->leftJoin('users as u', 'u.id', '=', 'ce.created_by')
            ->select('ce.id', 'ce.date', 'ce.description', 'a.code as expense_code', 'a.name as expense_name', 'ce.total_amount as amount', 'u.name as creator_name', 'ca.code as cash_code', 'ca.name as cash_name');

        if ($request->filled('from')) {
            $q->whereDate('ce.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('ce.date', '<=', $request->input('to'));
        }

        return DataTables::of($q)
            ->addIndexColumn()
            ->editColumn('date', function ($row) {
                return (string) $row->date;
            })
            ->editColumn('amount', function ($row) {
                return (float) $row->amount;
            })
            ->addColumn('cash_account', function ($row) {
                return $row->cash_code ? $row->cash_code.' - '.$row->cash_name : 'N/A';
            })
            ->addColumn('actions', function ($row) {
                return '<a href="/cash-expenses/'.$row->id.'/print" target="_blank" class="btn btn-sm btn-info" title="Print"><i class="fas fa-print"></i></a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function print(CashExpense $cashExpense)
    {
        $cashExpense->load([
            'lines.account',
            'lines.project',
            'lines.department',
            'cashAccount',
            'creator',
        ]);

        $cashAccount = $cashExpense->cashAccount;
        $terbilang = $this->convertToWords($cashExpense->total_amount);

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
            'sembilan belas',
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
            'sembilan puluh',
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
            'sembilan ratus',
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
            'sembilan ribu',
        ];

        if ($number == 0) {
            return 'nol rupiah';
        }

        $result = '';
        $number = (int) $number;

        // Handle millions
        if ($number >= 1000000) {
            $millions = intval($number / 1000000);
            if ($millions == 1) {
                $result .= 'satu juta ';
            } else {
                $result .= $this->convertToWords($millions).' juta ';
            }
            $number %= 1000000;
        }

        // Handle thousands
        if ($number >= 1000) {
            $thousand = intval($number / 1000);
            if ($thousand == 1) {
                $result .= 'seribu ';
            } else {
                $result .= $this->convertToWords($thousand).' ribu ';
            }
            $number %= 1000;
        }

        // Handle hundreds
        if ($number >= 100) {
            $hundred = intval($number / 100);
            if ($hundred == 1) {
                $result .= 'seratus ';
            } else {
                $result .= $hundreds[$hundred].' ';
            }
            $number %= 100;
        }

        // Handle tens and ones
        if ($number >= 20) {
            $ten = intval($number / 10);
            $result .= $tens[$ten].' ';
            $number %= 10;
        } elseif ($number >= 10) {
            $result .= $ones[$number].' ';
            $number = 0;
        }

        if ($number > 0) {
            $result .= $ones[$number].' ';
        }

        return trim($result).' rupiah';
    }
}
