<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\PostingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class ManualJournalController extends Controller
{
    public function __construct(private PostingService $service)
    {
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
        return view('journals.manual.create', compact('accounts', 'projects', 'departments'));
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
            ->toJson();
    }
}
