<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AssistantRequestLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AssistantReportController extends Controller
{
    public function index(Request $request): View
    {
        $query = AssistantRequestLog::query()
            ->with(['user', 'conversation'])
            ->orderByDesc('created_at');

        if ($request->filled('status') && in_array($request->status, ['success', 'error'], true)) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $needle = '%'.addcslashes($request->q, '%_\\').'%';
            $query->whereHas('user', function ($u) use ($needle) {
                $u->where('name', 'like', $needle)
                    ->orWhere('email', 'like', $needle);
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('admin.assistant-report.index', compact('logs'));
    }
}
