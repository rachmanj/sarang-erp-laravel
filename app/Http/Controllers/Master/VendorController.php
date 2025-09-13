<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Vendor;
use App\Models\Asset;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class VendorController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:vendors.view'])->only(['index']);
        $this->middleware(['auth', 'permission:vendors.manage'])->only(['create', 'store', 'edit', 'update']);
    }

    public function index()
    {
        return view('vendors.index');
    }

    public function create()
    {
        return view('vendors.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:vendors,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        $v = Vendor::create($data);
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $v->id]);
        }
        return redirect()->route('vendors.index')->with('success', 'Vendor created');
    }

    public function edit(Vendor $vendor)
    {
        return view('vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:vendors,code,' . $vendor->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        $vendor->update($data);
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('vendors.index')->with('success', 'Vendor updated');
    }

    public function data(Request $request)
    {
        $q = Vendor::query()->select(['id', 'code', 'name', 'email', 'phone']);
        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $editUrl = route('vendors.update', $row->id);
                $showUrl = route('vendors.show', $row->id);
                return '<a href="' . $showUrl . '" class="btn btn-xs btn-primary">View</a> ' .
                    '<button type="button" class="btn btn-xs btn-info btn-edit" data-id="' . $row->id . '" data-code="' . e($row->code) . '" data-name="' . e($row->name) . '" data-email="' . e((string)$row->email) . '" data-phone="' . e((string)$row->phone) . '" data-url="' . $editUrl . '">Edit</button>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function show(Vendor $vendor)
    {
        $this->authorize('view', $vendor);

        // Get vendor's assets
        $assets = Asset::where('vendor_id', $vendor->id)
            ->with(['category', 'fund', 'project', 'department'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get vendor's purchase orders
        $purchaseOrders = PurchaseOrder::where('vendor_id', $vendor->id)
            ->with('lines')
            ->orderBy('date', 'desc')
            ->get();

        // Calculate statistics
        $totalAssetValue = $assets->sum('acquisition_cost');
        $totalAssetCount = $assets->count();
        $totalPurchaseValue = $purchaseOrders->sum('total_amount');
        $totalPurchaseCount = $purchaseOrders->count();

        return view('vendors.show', compact(
            'vendor',
            'assets',
            'purchaseOrders',
            'totalAssetValue',
            'totalAssetCount',
            'totalPurchaseValue',
            'totalPurchaseCount'
        ));
    }

    public function assets(Vendor $vendor)
    {
        $this->authorize('view', $vendor);

        $query = Asset::where('vendor_id', $vendor->id)
            ->with(['category', 'fund', 'project', 'department']);

        return DataTables::of($query)
            ->addColumn('category_name', function ($asset) {
                return $asset->category ? $asset->category->name : '-';
            })
            ->addColumn('fund_name', function ($asset) {
                return $asset->fund ? $asset->fund->name : '-';
            })
            ->addColumn('project_name', function ($asset) {
                return $asset->project ? $asset->project->name : '-';
            })
            ->addColumn('department_name', function ($asset) {
                return $asset->department ? $asset->department->name : '-';
            })
            ->editColumn('acquisition_cost', function ($asset) {
                return 'Rp ' . number_format($asset->acquisition_cost, 0, ',', '.');
            })
            ->editColumn('current_book_value', function ($asset) {
                return 'Rp ' . number_format($asset->current_book_value, 0, ',', '.');
            })
            ->editColumn('placed_in_service_date', function ($asset) {
                return $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '-';
            })
            ->addColumn('actions', function ($asset) {
                return '<a href="' . route('assets.show', $asset->id) . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function purchaseOrders(Vendor $vendor)
    {
        $this->authorize('view', $vendor);

        $query = PurchaseOrder::where('vendor_id', $vendor->id)
            ->with('lines');

        return DataTables::of($query)
            ->editColumn('total_amount', function ($order) {
                return 'Rp ' . number_format($order->total_amount, 0, ',', '.');
            })
            ->editColumn('date', function ($order) {
                return $order->date ? $order->date->format('d/m/Y') : '-';
            })
            ->addColumn('actions', function ($order) {
                return '<a href="' . route('purchase-orders.show', $order->id) . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function assetAcquisitionHistory(Vendor $vendor)
    {
        $this->authorize('view', $vendor);

        // Get assets with their acquisition details
        $assets = Asset::where('vendor_id', $vendor->id)
            ->with(['category', 'fund', 'project', 'department'])
            ->orderBy('placed_in_service_date', 'desc')
            ->get();

        return DataTables::of($assets)
            ->addColumn('category_name', function ($asset) {
                return $asset->category ? $asset->category->name : '-';
            })
            ->addColumn('fund_name', function ($asset) {
                return $asset->fund ? $asset->fund->name : '-';
            })
            ->addColumn('project_name', function ($asset) {
                return $asset->project ? $asset->project->name : '-';
            })
            ->addColumn('department_name', function ($asset) {
                return $asset->department ? $asset->department->name : '-';
            })
            ->editColumn('acquisition_cost', function ($asset) {
                return 'Rp ' . number_format($asset->acquisition_cost, 0, ',', '.');
            })
            ->editColumn('placed_in_service_date', function ($asset) {
                return $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '-';
            })
            ->addColumn('age_months', function ($asset) {
                if (!$asset->placed_in_service_date) return '-';
                return $asset->placed_in_service_date->diffInMonths(now());
            })
            ->addColumn('actions', function ($asset) {
                return '<a href="' . route('assets.show', $asset->id) . '" class="btn btn-xs btn-info">View</a>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
