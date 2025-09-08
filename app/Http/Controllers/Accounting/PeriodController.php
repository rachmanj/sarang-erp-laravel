<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\PeriodCloseService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PeriodController extends Controller
{
    public function __construct(private PeriodCloseService $service)
    {
        $this->middleware(['auth', 'permission:periods.view']);
    }

    public function index(Request $request)
    {
        $year = (int) ($request->query('year') ?: now()->year);
        $periods = $this->service->listPeriods($year);
        return view('periods.index', compact('year', 'periods'));
    }

    public function close(Request $request)
    {
        $this->authorize('periods.close');
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);
        $this->service->close($data['year'], $data['month']);
        return back()->with('success', 'Period closed');
    }

    public function open(Request $request)
    {
        $this->authorize('periods.close');
        $data = $request->validate([
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ]);
        $this->service->open($data['year'], $data['month']);
        return back()->with('success', 'Period opened');
    }
}
