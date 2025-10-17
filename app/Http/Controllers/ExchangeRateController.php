<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ExchangeRateController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
        $this->middleware('permission:exchange-rates.view')->only(['index', 'show']);
        $this->middleware('permission:exchange-rates.create')->only(['create', 'store', 'dailyRates']);
        $this->middleware('permission:exchange-rates.update')->only(['edit', 'update']);
        $this->middleware('permission:exchange-rates.delete')->only(['destroy']);
    }

    public function index()
    {
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        return view('exchange_rates.index', compact('currencies'));
    }

    public function data()
    {
        $rates = ExchangeRate::with(['fromCurrency', 'toCurrency'])
            ->select(['id', 'from_currency_id', 'to_currency_id', 'rate', 'effective_date', 'rate_type', 'source', 'created_at']);

        return DataTables::of($rates)
            ->addColumn('from_currency_code', function ($rate) {
                return $rate->fromCurrency->code ?? '';
            })
            ->addColumn('to_currency_code', function ($rate) {
                return $rate->toCurrency->code ?? '';
            })
            ->addColumn('currency_pair', function ($rate) {
                return ($rate->fromCurrency->code ?? '') . '/' . ($rate->toCurrency->code ?? '');
            })
            ->addColumn('actions', function ($rate) {
                $actions = '<a class="btn btn-xs btn-info" href="' . route('exchange-rates.show', $rate->id) . '">View</a>';

                if (auth()->user()->can('exchange-rates.update')) {
                    $actions .= ' <a class="btn btn-xs btn-warning" href="' . route('exchange-rates.edit', $rate->id) . '">Edit</a>';
                }

                if (auth()->user()->can('exchange-rates.delete')) {
                    $actions .= ' <button class="btn btn-xs btn-danger" onclick="deleteRate(' . $rate->id . ')">Delete</button>';
                }

                return $actions;
            })
            ->addColumn('rate_type_badge', function ($rate) {
                $badges = [
                    'daily' => '<span class="badge badge-primary">Daily</span>',
                    'manual' => '<span class="badge badge-warning">Manual</span>',
                    'custom' => '<span class="badge badge-info">Custom</span>',
                ];
                return $badges[$rate->rate_type] ?? '<span class="badge badge-secondary">Unknown</span>';
            })
            ->rawColumns(['actions', 'rate_type_badge'])
            ->make(true);
    }

    public function dailyRates()
    {
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        return view('exchange_rates.daily_rates', compact('currencies'));
    }

    public function storeDailyRates(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'rates' => 'required|array',
            'rates.*.from_currency_id' => 'required|integer|exists:currencies,id',
            'rates.*.to_currency_id' => 'required|integer|exists:currencies,id',
            'rates.*.rate' => 'required|numeric|min:0.000001',
        ]);

        try {
            $rates = $this->exchangeRateService->createDailyRates($request->date, $request->rates);
            return redirect()->route('exchange-rates.index')
                ->with('success', 'Daily exchange rates created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating daily rates: ' . $e->getMessage());
        }
    }

    public function create()
    {
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        return view('exchange_rates.create', compact('currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_currency_id' => 'required|integer|exists:currencies,id',
            'to_currency_id' => 'required|integer|exists:currencies,id',
            'rate' => 'required|numeric|min:0.000001',
            'effective_date' => 'required|date',
            'rate_type' => 'required|in:daily,manual,custom',
            'source' => 'nullable|string|max:50',
        ]);

        try {
            $rate = $this->exchangeRateService->createExchangeRate($request->all());
            return redirect()->route('exchange-rates.index')
                ->with('success', 'Exchange rate created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating exchange rate: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $rate = ExchangeRate::with(['fromCurrency', 'toCurrency', 'creator'])->findOrFail($id);
        return view('exchange_rates.show', compact('rate'));
    }

    public function edit($id)
    {
        $rate = ExchangeRate::findOrFail($id);
        $currencies = Currency::where('is_active', true)->orderBy('code')->get();
        return view('exchange_rates.edit', compact('rate', 'currencies'));
    }

    public function update(Request $request, $id)
    {
        $rate = ExchangeRate::findOrFail($id);

        $request->validate([
            'from_currency_id' => 'required|integer|exists:currencies,id',
            'to_currency_id' => 'required|integer|exists:currencies,id',
            'rate' => 'required|numeric|min:0.000001',
            'effective_date' => 'required|date',
            'rate_type' => 'required|in:daily,manual,custom',
            'source' => 'nullable|string|max:50',
        ]);

        try {
            $this->exchangeRateService->updateExchangeRate($rate, $request->all());
            return redirect()->route('exchange-rates.index')
                ->with('success', 'Exchange rate updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error updating exchange rate: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $rate = ExchangeRate::findOrFail($id);

        try {
            $this->exchangeRateService->deleteExchangeRate($rate);
            return response()->json(['success' => 'Exchange rate deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting exchange rate: ' . $e->getMessage()], 500);
        }
    }

    public function getRate(Request $request)
    {
        $request->validate([
            'from_currency_id' => 'required|integer',
            'to_currency_id' => 'required|integer',
            'date' => 'required|date',
        ]);

        try {
            $rate = $this->exchangeRateService->getRate(
                $request->from_currency_id,
                $request->to_currency_id,
                $request->date
            );

            if (!$rate) {
                return response()->json(['error' => 'Exchange rate not found'], 404);
            }

            return response()->json([
                'rate' => $rate->rate,
                'rate_type' => $rate->rate_type,
                'effective_date' => $rate->effective_date,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error retrieving exchange rate: ' . $e->getMessage()], 500);
        }
    }
}
