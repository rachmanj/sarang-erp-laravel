<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\PostingService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ManualJournalController extends Controller
{
    public function __construct(
        private PostingService $service,
        private CurrencyService $currencyService,
        private ExchangeRateService $exchangeRateService
    ) {
        $this->middleware(['auth']);
    }

    public function create()
    {
        $accounts = DB::table('accounts')
            ->where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $projects = DB::table('projects')->orderBy('code')->get(['id', 'code', 'name']);
        $departments = DB::table('departments')->orderBy('code')->get(['id', 'code', 'name']);
        $currencies = $this->currencyService->getActiveCurrencies();
        return view('journals.manual.create', compact('accounts', 'projects', 'departments', 'currencies'));
    }

    public function store(Request $request)
    {
        $this->authorize('journals.create');
        $data = $request->validate([
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.account_id' => ['required', 'integer', 'exists:accounts,id'],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.currency_id' => ['nullable', 'integer', 'exists:currencies,id'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'lines.*.debit_foreign' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit_foreign' => ['nullable', 'numeric', 'min:0'],
            'lines.*.project_id' => ['nullable', 'integer'],
            'lines.*.dept_id' => ['nullable', 'integer'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ]);

        $payload = [
            'date' => $data['date'],
            'description' => $data['description'] ?? null,
            'source_type' => 'manual_journal',
            'source_id' => 0,
            'posted_by' => $request->user()->id,
            'lines' => $data['lines'],
        ];

        $journalId = $this->service->postJournal($payload);

        return redirect()->route('journals.manual.create')->with('success', "Journal #{$journalId} posted");
    }

    public function getExchangeRate(Request $request)
    {
        $currencyId = $request->input('currency_id');
        $date = $request->input('date', now()->toDateString());

        try {
            $baseCurrency = $this->currencyService->getBaseCurrency();
            if (!$baseCurrency) {
                return response()->json(['error' => 'Base currency not found'], 400);
            }

            if ($currencyId == $baseCurrency->id) {
                return response()->json(['rate' => 1.000000]);
            }

            $exchangeRate = $this->exchangeRateService->getRate($currencyId, $baseCurrency->id, $date);

            if (!$exchangeRate) {
                return response()->json(['error' => 'Exchange rate not found for the selected currency and date'], 400);
            }

            return response()->json(['rate' => $exchangeRate->rate]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error retrieving exchange rate: ' . $e->getMessage()], 500);
        }
    }

    public function index()
    {
        $journals = DB::table('journals as j')
            ->leftJoin('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->select('j.journal_no', 'j.date', 'j.description', DB::raw('SUM(jl.debit) as debit'), DB::raw('SUM(jl.credit) as credit'))
            ->groupBy('j.id', 'j.date', 'j.description')
            ->orderByDesc('j.date')
            ->orderByDesc('j.id')
            ->limit(200)
            ->get();
        return view('journals.index', compact('journals'));
    }

    public function reverse(Request $request, int $journal)
    {
        $this->authorize('journals.reverse');
        $this->service->reverseJournal($journal, now()->toDateString(), $request->user()->id);
        return back()->with('success', "Journal #{$journal} reversed");
    }

    public function data(Request $request)
    {
        $query = DB::table('journals as j')
            ->leftJoin('journal_lines as jl', 'jl.journal_id', '=', 'j.id')
            ->select('j.id', 'j.journal_no', 'j.date', 'j.description', DB::raw('SUM(jl.debit) as debit'), DB::raw('SUM(jl.credit) as credit'))
            ->groupBy('j.id', 'j.date', 'j.description', 'j.journal_no');

        if ($request->filled('from')) {
            $query->whereDate('j.date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('j.date', '<=', $request->input('to'));
        }
        if ($request->filled('desc')) {
            $query->where('j.description', 'like', '%' . $request->input('desc') . '%');
        }

        return DataTables::of($query)
            ->editColumn('debit', function ($row) {
                return number_format((float)$row->debit, 2);
            })
            ->editColumn('credit', function ($row) {
                return number_format((float)$row->credit, 2);
            })
            ->addColumn('actions', function ($row) {
                $url = route('journals.reverse', $row->id);
                return '<button type="button" class="btn btn-danger btn-xs reverse-button" data-id="' . $row->id . '" data-url="' . $url . '">Reverse</button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
