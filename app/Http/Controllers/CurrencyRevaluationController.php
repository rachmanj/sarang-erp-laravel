<?php

namespace App\Http\Controllers;

use App\Models\CurrencyRevaluation;
use App\Models\Currency;
use App\Services\CurrencyRevaluationService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CurrencyRevaluationController extends Controller
{
    protected $revaluationService;

    public function __construct(CurrencyRevaluationService $revaluationService)
    {
        $this->revaluationService = $revaluationService;
        $this->middleware('permission:currency-revaluations.view')->only(['index', 'show']);
        $this->middleware('permission:currency-revaluations.create')->only(['create', 'store', 'calculate']);
        $this->middleware('permission:currency-revaluations.post')->only(['post']);
        $this->middleware('permission:currency-revaluations.reverse')->only(['reverse']);
    }

    public function index()
    {
        $currencies = Currency::where('is_active', true)->where('is_base_currency', false)->orderBy('code')->get();
        return view('currency_revaluations.index', compact('currencies'));
    }

    public function data()
    {
        $revaluations = CurrencyRevaluation::with(['baseCurrency', 'journal', 'createdBy'])
            ->select(['id', 'revaluation_no', 'revaluation_date', 'base_currency_id', 'status', 'total_unrealized_gain', 'total_unrealized_loss', 'created_by', 'created_at']);

        return DataTables::of($revaluations)
            ->addColumn('base_currency_code', function ($revaluation) {
                return $revaluation->baseCurrency->code ?? '';
            })
            ->addColumn('created_by_name', function ($revaluation) {
                return $revaluation->createdBy->name ?? '';
            })
            ->addColumn('actions', function ($revaluation) {
                $actions = '<a class="btn btn-xs btn-info" href="' . route('currency-revaluations.show', $revaluation->id) . '">View</a>';

                if ($revaluation->status === 'draft' && auth()->user()->can('currency-revaluations.post')) {
                    $actions .= ' <button class="btn btn-xs btn-success" onclick="postRevaluation(' . $revaluation->id . ')">Post</button>';
                }

                if ($revaluation->status === 'posted' && auth()->user()->can('currency-revaluations.reverse')) {
                    $actions .= ' <button class="btn btn-xs btn-warning" onclick="reverseRevaluation(' . $revaluation->id . ')">Reverse</button>';
                }

                return $actions;
            })
            ->addColumn('status_badge', function ($revaluation) {
                $badges = [
                    'draft' => '<span class="badge badge-secondary">Draft</span>',
                    'posted' => '<span class="badge badge-success">Posted</span>',
                    'cancelled' => '<span class="badge badge-danger">Cancelled</span>',
                ];
                return $badges[$revaluation->status] ?? '<span class="badge badge-secondary">Unknown</span>';
            })
            ->addColumn('net_effect', function ($revaluation) {
                $net = $revaluation->total_unrealized_gain - $revaluation->total_unrealized_loss;
                if ($net > 0) {
                    return '<span class="text-success">+' . number_format($net, 2) . '</span>';
                } elseif ($net < 0) {
                    return '<span class="text-danger">' . number_format($net, 2) . '</span>';
                } else {
                    return '<span class="text-muted">0.00</span>';
                }
            })
            ->rawColumns(['actions', 'status_badge', 'net_effect'])
            ->make(true);
    }

    public function create()
    {
        $currencies = Currency::where('is_active', true)->where('is_base_currency', false)->orderBy('code')->get();
        return view('currency_revaluations.create', compact('currencies'));
    }

    public function calculate(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|integer|exists:currencies,id',
            'revaluation_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $calculation = $this->revaluationService->calculateRevaluation(
                $request->currency_id,
                $request->revaluation_date
            );

            return response()->json($calculation);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error calculating revaluation: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'currency_id' => 'required|integer|exists:currencies,id',
            'revaluation_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            $revaluation = $this->revaluationService->createRevaluation(
                $request->currency_id,
                $request->revaluation_date,
                $request->notes
            );

            return redirect()->route('currency-revaluations.show', $revaluation->id)
                ->with('success', 'Currency revaluation created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating revaluation: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $revaluation = CurrencyRevaluation::with([
            'baseCurrency',
            'lines.account',
            'lines.originalCurrency',
            'lines.businessPartner',
            'journal',
            'createdBy',
            'postedBy'
        ])->findOrFail($id);

        return view('currency_revaluations.show', compact('revaluation'));
    }

    public function post($id)
    {
        try {
            $journal = $this->revaluationService->postRevaluation($id);
            return response()->json([
                'success' => 'Revaluation posted successfully',
                'journal_id' => $journal->id,
                'journal_no' => $journal->journal_no
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error posting revaluation: ' . $e->getMessage()], 500);
        }
    }

    public function reverse($id)
    {
        try {
            $revaluation = $this->revaluationService->cancelRevaluation($id);
            return response()->json(['success' => 'Revaluation cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error cancelling revaluation: ' . $e->getMessage()], 500);
        }
    }

    public function preview($id)
    {
        $revaluation = CurrencyRevaluation::with([
            'baseCurrency',
            'lines.account',
            'lines.originalCurrency',
            'lines.businessPartner'
        ])->findOrFail($id);

        return view('currency_revaluations.preview', compact('revaluation'));
    }
}
