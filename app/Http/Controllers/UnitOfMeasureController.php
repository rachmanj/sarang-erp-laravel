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
        $unitTypes = $this->unitConversionService->getUnitTypes();
        return view('unit_of_measures.create', compact('unitTypes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units_of_measure',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'unit_type' => 'required|in:count,weight,volume,length,area,time,temperature,custom',
            'is_base_unit' => 'boolean',
        ]);

        UnitOfMeasure::create($request->all());

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
        $unitTypes = $this->unitConversionService->getUnitTypes();
        return view('unit_of_measures.edit', compact('unitOfMeasure', 'unitTypes'));
    }

    public function update(Request $request, UnitOfMeasure $unitOfMeasure)
    {
        $request->validate([
            'code' => 'required|string|max:20|unique:units_of_measure,code,' . $unitOfMeasure->id,
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'unit_type' => 'required|in:count,weight,volume,length,area,time,temperature,custom',
            'is_base_unit' => 'boolean',
        ]);

        $unitOfMeasure->update($request->all());

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
    public function getUnitsByType(Request $request): JsonResponse
    {
        $type = $request->get('type');
        $units = $this->unitConversionService->getUnitsByType($type);

        return response()->json($units);
    }

    public function getConversionFactor(Request $request): JsonResponse
    {
        $fromUnitId = $request->get('from_unit_id');
        $toUnitId = $request->get('to_unit_id');

        $factor = $this->unitConversionService->getConversionFactor($fromUnitId, $toUnitId);

        return response()->json([
            'factor' => $factor,
            'valid' => $factor !== null
        ]);
    }

    public function getConversionPreview(Request $request): JsonResponse
    {
        $fromUnitId = $request->get('from_unit_id');
        $toUnitId = $request->get('to_unit_id');
        $quantity = $request->get('quantity', 1);

        $preview = $this->unitConversionService->getConversionPreview($fromUnitId, $toUnitId, $quantity);

        return response()->json([
            'preview' => $preview,
            'valid' => $preview !== null
        ]);
    }

    public function validateConversion(Request $request): JsonResponse
    {
        $fromUnitId = $request->get('from_unit_id');
        $toUnitId = $request->get('to_unit_id');

        $valid = $this->unitConversionService->validateConversion($fromUnitId, $toUnitId);

        return response()->json(['valid' => $valid]);
    }

    public function getItemUnits(Request $request): JsonResponse
    {
        $itemId = $request->get('item_id');
        $units = $this->unitConversionService->getAvailableUnitsForItem($itemId);

        return response()->json($units);
    }
}
