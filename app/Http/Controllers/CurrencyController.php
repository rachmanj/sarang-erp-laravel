<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Services\CurrencyService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CurrencyController extends Controller
{
    protected $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
        $this->middleware('permission:currencies.view')->only(['index', 'show']);
        $this->middleware('permission:currencies.create')->only(['create', 'store']);
        $this->middleware('permission:currencies.update')->only(['edit', 'update']);
        $this->middleware('permission:currencies.delete')->only(['destroy']);
    }

    public function index()
    {
        return view('currencies.index');
    }

    public function data()
    {
        $currencies = $this->currencyService->getAllCurrencies();

        return DataTables::of($currencies)
            ->addColumn('actions', function ($currency) {
                $actions = '<a class="btn btn-xs btn-info" href="' . route('currencies.show', $currency->id) . '">View</a>';

                if (auth()->user()->can('currencies.update')) {
                    $actions .= ' <a class="btn btn-xs btn-warning" href="' . route('currencies.edit', $currency->id) . '">Edit</a>';
                }

                if (auth()->user()->can('currencies.delete') && !$currency->is_base_currency) {
                    $actions .= ' <button class="btn btn-xs btn-danger" onclick="deleteCurrency(' . $currency->id . ')">Delete</button>';
                }

                return $actions;
            })
            ->addColumn('status', function ($currency) {
                $badges = '';
                if ($currency->is_base_currency) {
                    $badges .= '<span class="badge badge-primary">Base Currency</span> ';
                }
                if ($currency->is_active) {
                    $badges .= '<span class="badge badge-success">Active</span>';
                } else {
                    $badges .= '<span class="badge badge-secondary">Inactive</span>';
                }
                return $badges;
            })
            ->rawColumns(['actions', 'status'])
            ->make(true);
    }

    public function create()
    {
        return view('currencies.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code',
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:4',
            'is_active' => 'boolean',
        ]);

        try {
            $currency = $this->currencyService->createCurrency($request->all());
            return redirect()->route('currencies.index')
                ->with('success', 'Currency created successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error creating currency: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $currency = Currency::findOrFail($id);
        return view('currencies.show', compact('currency'));
    }

    public function edit($id)
    {
        $currency = Currency::findOrFail($id);
        return view('currencies.edit', compact('currency'));
    }

    public function update(Request $request, $id)
    {
        $currency = Currency::findOrFail($id);

        $request->validate([
            'code' => 'required|string|max:3|unique:currencies,code,' . $id,
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:5',
            'decimal_places' => 'required|integer|min:0|max:4',
            'is_active' => 'boolean',
        ]);

        try {
            $this->currencyService->updateCurrency($currency, $request->all());
            return redirect()->route('currencies.index')
                ->with('success', 'Currency updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Error updating currency: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $currency = Currency::findOrFail($id);

        if ($currency->is_base_currency) {
            return response()->json(['error' => 'Cannot delete base currency'], 400);
        }

        try {
            $this->currencyService->deleteCurrency($currency);
            return response()->json(['success' => 'Currency deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error deleting currency: ' . $e->getMessage()], 500);
        }
    }
}
