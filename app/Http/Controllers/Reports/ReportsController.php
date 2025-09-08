<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\Reports\ReportService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
    public function __construct(private ReportService $service)
    {
        $this->middleware('permission:reports.view');
    }

    public function trialBalance(Request $request)
    {
        if ($request->wantsJson()) {
            $data = $this->service->getTrialBalance($request->query('date'));
            return response()->json($data);
        }
        return view('reports.trial-balance');
    }

    public function glDetail(Request $request)
    {
        if ($request->wantsJson()) {
            $filters = $request->only(['account_id', 'from', 'to', 'project_id', 'fund_id', 'dept_id']);
            $data = $this->service->getGlDetail($filters);
            return response()->json($data);
        }
        return view('reports.gl-detail');
    }
}
