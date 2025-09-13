<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssetDepreciationRun;
use App\Models\AssetDepreciationEntry;
use App\Models\Asset;
use App\Services\Accounting\FixedAssetService;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AssetDepreciationController extends Controller
{
    public function __construct(
        private FixedAssetService $fixedAssetService
    ) {}

    public function index()
    {
        return view('assets.depreciation.index');
    }

    public function data()
    {
        $runs = AssetDepreciationRun::with(['creator', 'poster'])
            ->orderBy('period', 'desc')
            ->get();

        return DataTables::of($runs)
            ->addColumn('period_display', function ($run) {
                return $run->period_display;
            })
            ->addColumn('total_depreciation_formatted', function ($run) {
                return number_format($run->total_depreciation, 2);
            })
            ->addColumn('status_badge', function ($run) {
                return $run->status_badge;
            })
            ->addColumn('creator_name', function ($run) {
                return $run->creator->name ?? 'N/A';
            })
            ->addColumn('poster_name', function ($run) {
                return $run->poster->name ?? 'N/A';
            })
            ->addColumn('posted_at_formatted', function ($run) {
                return $run->posted_at ? $run->posted_at->format('M d, Y H:i') : 'N/A';
            })
            ->addColumn('actions', function ($run) {
                $actions = '';

                if (auth()->user()->can('assets.depreciation.run') && $run->isDraft()) {
                    $actions .= sprintf(
                        '<a href="#" class="btn btn-sm btn-success post-run" data-id="%d" data-period="%s">Post</a> ',
                        $run->id,
                        $run->period
                    );
                }

                if (auth()->user()->can('assets.depreciation.reverse') && $run->canBeReversed()) {
                    $actions .= sprintf(
                        '<a href="#" class="btn btn-sm btn-warning reverse-run" data-id="%d" data-period="%s">Reverse</a> ',
                        $run->id,
                        $run->period
                    );
                }

                $actions .= sprintf(
                    '<a href="/assets/depreciation/%d/details" class="btn btn-sm btn-info">Details</a>',
                    $run->id
                );

                return $actions;
            })
            ->rawColumns(['status_badge', 'actions'])
            ->make(true);
    }

    public function create()
    {
        return view('assets.depreciation.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'period' => 'required|date_format:Y-m',
        ]);

        try {
            $run = $this->fixedAssetService->createDepreciationRun(
                $request->period,
                auth()->id()
            );

            return response()->json([
                'success' => true,
                'message' => 'Depreciation run created successfully.',
                'data' => $run
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function show(AssetDepreciationRun $run)
    {
        $run->load(['creator', 'poster', 'depreciationEntries.asset.category']);

        return view('assets.depreciation.show', compact('run'));
    }

    public function calculate(AssetDepreciationRun $run)
    {
        try {
            $entries = $this->fixedAssetService->calculateDepreciationEntries($run);

            return response()->json([
                'success' => true,
                'message' => 'Depreciation calculated successfully.',
                'data' => [
                    'entries' => $entries,
                    'total_depreciation' => $run->total_depreciation,
                    'asset_count' => $run->asset_count
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function createEntries(AssetDepreciationRun $run)
    {
        try {
            $this->fixedAssetService->createDraftDepreciationEntries($run);

            return response()->json([
                'success' => true,
                'message' => 'Depreciation entries created successfully.',
                'data' => $run
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function post(AssetDepreciationRun $run)
    {
        try {
            $this->fixedAssetService->postDepreciationRun($run, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Depreciation run posted successfully.',
                'data' => $run->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function reverse(AssetDepreciationRun $run)
    {
        try {
            $this->fixedAssetService->reverseDepreciationRun($run, auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Depreciation run reversed successfully.',
                'data' => $run->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function entries(AssetDepreciationRun $run)
    {
        $entries = AssetDepreciationEntry::with(['asset.category', 'fund', 'project', 'department'])
            ->where('period', $run->period)
            ->where('book', 'financial')
            ->get();

        return DataTables::of($entries)
            ->addColumn('asset_name', function ($entry) {
                return $entry->asset->name;
            })
            ->addColumn('asset_code', function ($entry) {
                return $entry->asset->code;
            })
            ->addColumn('category_name', function ($entry) {
                return $entry->asset->category->name;
            })
            ->addColumn('amount_formatted', function ($entry) {
                return number_format($entry->amount, 2);
            })
            ->addColumn('dimensions', function ($entry) {
                $dimensions = [];
                if ($entry->fund) $dimensions[] = "Fund: {$entry->fund->name}";
                if ($entry->project) $dimensions[] = "Project: {$entry->project->name}";
                if ($entry->department) $dimensions[] = "Dept: {$entry->department->name}";

                return $dimensions ? implode('<br>', $dimensions) : 'No dimensions';
            })
            ->addColumn('is_posted', function ($entry) {
                return $entry->isPosted()
                    ? '<span class="badge badge-success">Posted</span>'
                    : '<span class="badge badge-warning">Draft</span>';
            })
            ->rawColumns(['dimensions', 'is_posted'])
            ->make(true);
    }

    public function schedule(Asset $asset)
    {
        $schedule = $this->fixedAssetService->getAssetDepreciationSchedule($asset, 24);

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }
}
