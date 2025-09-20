<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ErpParameter;
use Illuminate\Support\Facades\Auth;

class ErpParameterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage-erp-parameters');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ErpParameter::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $parameters = ErpParameter::orderBy('category')
            ->orderBy('parameter_name')
            ->get()
            ->groupBy('category');

        return view('admin.erp-parameters.index', compact('categories', 'parameters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = ErpParameter::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.erp-parameters.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'parameter_key' => 'required|string|max:100|unique:erp_parameters,category,' . $request->category . ',parameter_key',
            'parameter_name' => 'required|string|max:200',
            'parameter_value' => 'required',
            'data_type' => 'required|in:string,integer,boolean,json',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        ErpParameter::create([
            'category' => $request->category,
            'parameter_key' => $request->parameter_key,
            'parameter_name' => $request->parameter_name,
            'parameter_value' => $request->parameter_value,
            'data_type' => $request->data_type,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('erp-parameters.index')
            ->with('success', 'ERP Parameter created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ErpParameter $erpParameter)
    {
        return view('admin.erp-parameters.show', compact('erpParameter'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ErpParameter $erpParameter)
    {
        $categories = ErpParameter::select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('admin.erp-parameters.edit', compact('erpParameter', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ErpParameter $erpParameter)
    {
        $request->validate([
            'category' => 'required|string|max:100',
            'parameter_key' => 'required|string|max:100|unique:erp_parameters,category,' . $request->category . ',parameter_key,' . $erpParameter->id,
            'parameter_name' => 'required|string|max:200',
            'parameter_value' => 'required',
            'data_type' => 'required|in:string,integer,boolean,json',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $erpParameter->update([
            'category' => $request->category,
            'parameter_key' => $request->parameter_key,
            'parameter_name' => $request->parameter_name,
            'parameter_value' => $request->parameter_value,
            'data_type' => $request->data_type,
            'description' => $request->description,
            'is_active' => $request->has('is_active'),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('erp-parameters.index')
            ->with('success', 'ERP Parameter updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ErpParameter $erpParameter)
    {
        $erpParameter->delete();

        return redirect()->route('erp-parameters.index')
            ->with('success', 'ERP Parameter deleted successfully.');
    }

    /**
     * Get parameters by category for AJAX requests
     */
    public function getByCategory(Request $request)
    {
        $category = $request->get('category');

        if (!$category) {
            return response()->json([]);
        }

        $parameters = ErpParameter::where('category', $category)
            ->orderBy('parameter_name')
            ->get();

        return response()->json($parameters);
    }

    /**
     * Bulk update parameters
     */
    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'parameters' => 'required|array',
            'parameters.*.id' => 'required|exists:erp_parameters,id',
            'parameters.*.parameter_value' => 'required',
        ]);

        foreach ($request->parameters as $paramData) {
            $parameter = ErpParameter::find($paramData['id']);
            if ($parameter) {
                $parameter->update([
                    'parameter_value' => $paramData['parameter_value'],
                    'updated_by' => Auth::id(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Parameters updated successfully.'
        ]);
    }
}
