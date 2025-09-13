<?php

namespace App\Http\Controllers;

use App\Services\DataQuality\AssetDataQualityService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AssetDataQualityController extends Controller
{
    protected $dataQualityService;

    public function __construct(AssetDataQualityService $dataQualityService)
    {
        $this->middleware('auth');
        $this->middleware('can:assets.view');
        $this->dataQualityService = $dataQualityService;
    }

    public function index()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $report = $this->dataQualityService->getDataQualityReport();
        $score = $this->dataQualityService->getDataQualityScore();

        return view('assets.data-quality.index', compact('report', 'score'));
    }

    public function duplicates()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $duplicates = $this->dataQualityService->getDuplicateAssets();

        return view('assets.data-quality.duplicates', compact('duplicates'));
    }

    public function incomplete()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $incompleteAssets = $this->dataQualityService->getIncompleteAssets();

        return view('assets.data-quality.incomplete', compact('incompleteAssets'));
    }

    public function consistency()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $report = $this->dataQualityService->getDataQualityReport();

        return view('assets.data-quality.consistency', compact('report'));
    }

    public function orphaned()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $report = $this->dataQualityService->getDataQualityReport();

        return view('assets.data-quality.orphaned', compact('report'));
    }

    public function getDuplicateDetails(Request $request)
    {
        $this->authorize('view', \App\Models\Asset::class);

        $request->validate([
            'type' => 'required|in:name,serial,code',
            'value' => 'required|string'
        ]);

        $assets = $this->dataQualityService->getDuplicateDetails(
            $request->get('type'),
            $request->get('value')
        );

        return response()->json($assets);
    }

    public function getAssetsByIssue(Request $request)
    {
        $this->authorize('view', \App\Models\Asset::class);

        $request->validate([
            'issue_type' => 'required|string',
            'issue_value' => 'nullable|string'
        ]);

        $assets = $this->dataQualityService->getAssetsByIssue(
            $request->get('issue_type'),
            $request->get('issue_value')
        );

        return response()->json($assets);
    }

    public function exportReport(Request $request)
    {
        $this->authorize('view', \App\Models\Asset::class);

        $format = $request->get('format', 'csv');

        if ($format === 'csv') {
            $csv = $this->dataQualityService->exportDataQualityReport('csv');

            return response($csv)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="asset_data_quality_report_' . date('Y-m-d_H-i-s') . '.csv"');
        }

        $report = $this->dataQualityService->exportDataQualityReport('json');

        return response()->json($report);
    }

    public function getDataQualityScore()
    {
        $this->authorize('view', \App\Models\Asset::class);

        $score = $this->dataQualityService->getDataQualityScore();

        return response()->json(['score' => $score]);
    }
}
