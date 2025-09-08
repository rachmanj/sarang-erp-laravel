<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Vendor;
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
                return '<button type="button" class="btn btn-xs btn-info btn-edit" data-id="' . $row->id . '" data-code="' . e($row->code) . '" data-name="' . e($row->name) . '" data-email="' . e((string)$row->email) . '" data-phone="' . e((string)$row->phone) . '" data-url="' . $editUrl . '">Edit</button>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
