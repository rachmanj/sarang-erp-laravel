<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Master\Customer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'permission:customers.view'])->only(['index']);
        $this->middleware(['auth', 'permission:customers.manage'])->only(['create', 'store', 'edit', 'update']);
    }

    public function index()
    {
        return view('customers.index');
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:customers,code'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        $cust = Customer::create($data);
        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'id' => $cust->id]);
        }
        return redirect()->route('customers.index')->with('success', 'Customer created');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:customers,code,' . $customer->id],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ]);
        $customer->update($data);
        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('customers.index')->with('success', 'Customer updated');
    }

    public function data(Request $request)
    {
        $q = Customer::query()->select(['id', 'code', 'name', 'email', 'phone']);
        return DataTables::of($q)
            ->addColumn('actions', function ($row) {
                $editUrl = route('customers.update', $row->id);
                return '<button type="button" class="btn btn-xs btn-info btn-edit" data-id="' . $row->id . '" data-code="' . e($row->code) . '" data-name="' . e($row->name) . '" data-email="' . e((string)$row->email) . '" data-phone="' . e((string)$row->phone) . '" data-url="' . $editUrl . '">Edit</button>';
            })
            ->rawColumns(['actions'])
            ->toJson();
    }
}
