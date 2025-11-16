<?php

namespace App\Http\Controllers;

use App\Models\UnitOfMeasure;
use App\Models\UnitConversion;
use App\Services\UnitConversionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnitOfMeasureController extends Controller
{
    protected $unitConversionService;

    public function __construct(UnitConversionService $unitConversionService)
    {
        $this->unitConversionService = $unitConversionService;
        $this->middleware('permission:view_unit_of_measure')->only(['index', 'show']);
        $this->middleware('permission:create_unit_of_measure')->only(['create', 'store']);
        $this->middleware('permission:update_unit_of_measure')->only(['edit', 'update']);
        $this->middleware('permission:delete_unit_of_measure')->only(['destroy']);
    }

    public function index()
    {
        $units = UnitOfMeasure::with(['fromConversions', 'toConversions'])
            ->orderBy('unit_type')
            ->orderBy('name')
            ->get()
            ->groupBy('unit_type');

        return view('unit_of_measures.index', compact('units'));
    }

    public function create()
    {
        return view('unit_of_measures.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units_of_measure',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_base_unit' => 'boolean',
        ]);

        UnitOfMeasure::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'unit_type' => $request->unit_type ?? 'count',
            'is_base_unit' => $request->boolean('is_base_unit', false),
            'is_active' => true,
        ]);

        return redirect()->route('unit-of-measures.index')
            ->with('success', 'Unit of measure created successfully.');
    }

    public function show(UnitOfMeasure $unitOfMeasure)
    {
        $unitOfMeasure->load(['fromConversions.toUnit', 'toConversions.fromUnit']);
        return view('unit_of_measures.show', compact('unitOfMeasure'));
    }

    public function edit(UnitOfMeasure $unitOfMeasure)
    {
        return view('unit_of_measures.edit', compact('unitOfMeasure'));
    }

    public function update(Request $request, UnitOfMeasure $unitOfMeasure)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units_of_measure,code,' . $unitOfMeasure->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'is_base_unit' => 'boolean',
        ]);

        $unitOfMeasure->update([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'is_base_unit' => $request->boolean('is_base_unit', $unitOfMeasure->is_base_unit),
        ]);

        return redirect()->route('unit-of-measures.index')
            ->with('success', 'Unit of measure updated successfully.');
    }

    public function destroy(UnitOfMeasure $unitOfMeasure)
    {
        $unitOfMeasure->delete();

        return redirect()->route('unit-of-measures.index')
            ->with('success', 'Unit of measure deleted successfully.');
    }

    // API Methods for AJAX requests
    public function getItemUnits(Request $request): JsonResponse
    {
        $itemId = $request->get('item_id');
        $units = $this->unitConversionService->getAvailableUnitsForItem($itemId);

        return response()->json($units);
    }

    public function storeAjax(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units_of_measure,code',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $unit = UnitOfMeasure::create([
            'code' => $request->code,
            'name' => $request->name,
            'description' => $request->description,
            'unit_type' => 'count',
            'is_base_unit' => false,
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'unit' => [
                'id' => $unit->id,
                'code' => $unit->code,
                'name' => $unit->name,
                'display_name' => $unit->display_name,
            ],
        ]);
    }
}
