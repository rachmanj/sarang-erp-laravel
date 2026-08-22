<?php

namespace App\Http\Controllers;

use App\Models\Audit\AuditRun;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(): View
    {
        $runs = AuditRun::query()
            ->orderByDesc('id')
            ->paginate(25);

        $latestRun = AuditRun::query()->orderByDesc('id')->first();

        $stats = [
            'total_runs' => AuditRun::query()->count(),
            'latest_issues' => $latestRun?->total_issues ?? 0,
            'latest_status' => $latestRun?->status,
        ];

        return view('audit.index', compact('runs', 'stats'));
    }

    public function show(AuditRun $run): View
    {
        $run->load(['results' => fn ($query) => $query->orderBy('id')]);

        return view('audit.show', compact('run'));
    }
}
