<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Journal;
use App\Services\Accounting\JournalSourceUrlResolver;
use App\Services\Accounting\PostingService;
use App\Services\CurrencyService;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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

        $lines = collect($data['lines'])->map(function ($line) {
            if (empty($line['currency_id'])) {
                unset($line['debit_foreign'], $line['credit_foreign'], $line['exchange_rate']);
            }

            return $line;
        })->all();

        $payload = [
            'date' => $data['date'],
            'description' => $data['description'] ?? null,
            'source_type' => 'manual_journal',
            'source_id' => 0,
            'posted_by' => $request->user()->id,
            'lines' => $lines,
        ];

        $journalId = $this->service->postJournal($payload);

        return redirect()->route('journals.manual.create')->with('status', "Journal #{$journalId} posted");
    }

    public function getExchangeRate(Request $request)
    {
        $currencyId = $request->input('currency_id');
        $date = $request->input('date', now()->toDateString());

        try {
            $baseCurrency = $this->currencyService->getBaseCurrency();
            if (! $baseCurrency) {
                return response()->json(['error' => 'Base currency not found'], 400);
            }

            if ($currencyId == $baseCurrency->id) {
                return response()->json(['rate' => 1.000000]);
            }

            $exchangeRate = $this->exchangeRateService->getRate($currencyId, $baseCurrency->id, $date);

            if (! $exchangeRate) {
                return response()->json(['error' => 'Exchange rate not found for the selected currency and date'], 400);
            }

            return response()->json(['rate' => $exchangeRate->rate]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error retrieving exchange rate: '.$e->getMessage()], 500);
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

    public function show(Journal $journal, JournalSourceUrlResolver $sourceUrlResolver)
    {
        $journal->load([
            'lines.account',
            'lines.project',
            'lines.dept',
            'lines.currency',
            'postedBy',
        ]);

        $totalDebit = $journal->lines->sum('debit');
        $totalCredit = $journal->lines->sum('credit');
        $hasForeignCurrency = $journal->lines->contains(fn ($line) => $line->currency_id !== null);

        $sourceUrl = $sourceUrlResolver->resolve(
            $journal->source_type,
            $journal->source_id,
            auth()->user()
        );
        $sourceLabel = $sourceUrlResolver->label(
            $journal->source_type,
            $journal->source_id,
            $journal->journal_no
        );

        return view('journals.show', [
            'journal' => $journal,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'hasForeignCurrency' => $hasForeignCurrency,
            'sourceUrl' => $sourceUrl,
            'sourceLabel' => $sourceLabel,
        ]);
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
            $query->where('j.description', 'like', '%'.$request->input('desc').'%');
        }

        return DataTables::of($query)
            ->editColumn('date', function ($row) {
                return Carbon::parse($row->date)->format('d-M-Y');
            })
            ->editColumn('debit', function ($row) {
                return number_format((float) $row->debit, 2);
            })
            ->editColumn('credit', function ($row) {
                return number_format((float) $row->credit, 2);
            })
            ->addColumn('actions', function ($row) {
                $viewUrl = route('journals.show', $row->id);
                $reverseUrl = route('journals.reverse', $row->id);

                return '<a href="'.$viewUrl.'" class="btn btn-info btn-xs mr-1"><i class="fas fa-eye"></i> View</a>'
                    .'<button type="button" class="btn btn-danger btn-xs reverse-button" data-id="'.$row->id.'" data-url="'.$reverseUrl.'">Reverse</button>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }
}
