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
        $this->middleware('permission:view_unit_of_measure')->only(['index', 'show', 'data']);
        $this->middleware('permission:create_unit_of_measure')->only(['create', 'store']);
        $this->middleware('permission:update_unit_of_measure')->only(['edit', 'update']);
        $this->middleware('permission:delete_unit_of_measure')->only(['destroy']);
    }

    public function index()
    {
        return view('unit_of_measures.index');
    }

    public function data(Request $request)
    {
        $query = UnitOfMeasure::with(['fromConversions', 'toConversions']);

        // Apply search filter
        if ($request->filled('search') && $request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('code', 'like', '%' . $searchValue . '%')
                    ->orWhere('name', 'like', '%' . $searchValue . '%')
                    ->orWhere('description', 'like', '%' . $searchValue . '%')
                    ->orWhere('unit_type', 'like', '%' . $searchValue . '%');
            });
        }

        // Apply unit type filter
        if ($request->filled('unit_type')) {
            $query->where('unit_type', $request->unit_type);
        }

        // Apply status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Get total count before pagination
        $totalRecords = UnitOfMeasure::count();
        $filteredRecords = $query->count();

        // Apply sorting
        $order = $request->get('order', []);
        if (!empty($order) && isset($order[0])) {
            $orderColumn = $order[0]['column'] ?? 0;
            $orderDir = $order[0]['dir'] ?? 'asc';
            
            $columns = ['code', 'name', 'description', 'unit_type', 'is_base_unit', 'is_active', 'conversions'];
            $orderBy = $columns[$orderColumn] ?? 'name';
            
            if ($orderBy === 'conversions') {
                // For conversions, we'll sort by unit_type and name
                $query->orderBy('unit_type', $orderDir)->orderBy('name', $orderDir);
            } else {
                $query->orderBy($orderBy, $orderDir);
            }
        } else {
            // Default sorting
            $query->orderBy('unit_type', 'asc')->orderBy('name', 'asc');
        }

        // Apply DataTables pagination
        $start = $request->get('start', 0);
        $length = $request->get('length', 10);

        $units = $query->skip($start)->take($length)->get();

        return response()->json([
            'draw' => intval($request->get('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $units->map(function ($unit) {
                $conversionCount = $unit->fromConversions->count() + $unit->toConversions->count();
                
                return [
                    'id' => $unit->id,
                    'code' => $unit->code,
                    'name' => $unit->name,
                    'description' => $unit->description ?? '-',
                    'unit_type' => ucfirst($unit->unit_type),
                    'is_base_unit' => $unit->is_base_unit,
                    'is_active' => $unit->is_active,
                    'conversions' => $conversionCount,
                    'actions' => view('unit_of_measures.partials.actions', compact('unit'))->render(),
                ];
            })
        ]);
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
            'unit_type' => 'nullable|in:count,weight,length,volume,area,time',
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
            'unit_type' => 'nullable|in:count,weight,length,volume,area,time',
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
