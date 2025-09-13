<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AssetMovementController extends Controller
{
    /**
     * Display a listing of asset movements
     */
    public function index()
    {
        $this->authorize('view', AssetMovement::class);

        return view('assets.movements.index');
    }

    /**
     * Get movements data for DataTables
     */
    public function data(Request $request)
    {
        $this->authorize('view', AssetMovement::class);

        $query = AssetMovement::with(['asset.category', 'creator', 'approver'])
            ->select([
                'asset_movements.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name'
            ])
            ->join('assets', 'asset_movements.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('asset_movements.status', $request->status);
        }

        if ($request->filled('movement_type')) {
            $query->where('asset_movements.movement_type', $request->movement_type);
        }

        if ($request->filled('date_from')) {
            $query->where('asset_movements.movement_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('asset_movements.movement_date', '<=', $request->date_to);
        }

        if ($request->filled('asset_id')) {
            $query->where('asset_movements.asset_id', $request->asset_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('assets.code', 'like', "%{$search}%")
                    ->orWhere('assets.name', 'like', "%{$search}%")
                    ->orWhere('asset_categories.name', 'like', "%{$search}%")
                    ->orWhere('asset_movements.reference_number', 'like', "%{$search}%")
                    ->orWhere('asset_movements.from_location', 'like', "%{$search}%")
                    ->orWhere('asset_movements.to_location', 'like', "%{$search}%");
            });
        }

        $movements = $query->orderBy('asset_movements.movement_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $movements->items(),
            'total' => $movements->total(),
            'per_page' => $movements->perPage(),
            'current_page' => $movements->currentPage(),
            'last_page' => $movements->lastPage(),
        ]);
    }

    /**
     * Show the form for creating a new movement
     */
    public function create(Request $request)
    {
        $this->authorize('create', AssetMovement::class);

        $assetId = $request->get('asset_id');
        $asset = null;

        if ($assetId) {
            $asset = Asset::with(['category', 'fund', 'project', 'department'])
                ->findOrFail($assetId);

            if ($asset->status !== 'active') {
                return redirect()->route('assets.index')
                    ->with('error', 'Only active assets can be moved.');
            }
        }

        return view('assets.movements.create', compact('asset'));
    }

    /**
     * Store a newly created movement
     */
    public function store(Request $request)
    {
        $this->authorize('create', AssetMovement::class);

        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'movement_date' => 'required|date',
            'movement_type' => 'required|in:transfer,relocation,custodian_change,maintenance,other',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'from_custodian' => 'nullable|string|max:255',
            'to_custodian' => 'nullable|string|max:255',
            'movement_reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
        ]);

        $asset = Asset::findOrFail($request->asset_id);

        if ($asset->status !== 'active') {
            return back()->with('error', 'Only active assets can be moved.');
        }

        AssetMovement::create([
            'asset_id' => $asset->id,
            'movement_date' => $request->movement_date,
            'movement_type' => $request->movement_type,
            'from_location' => $request->from_location,
            'to_location' => $request->to_location,
            'from_custodian' => $request->from_custodian,
            'to_custodian' => $request->to_custodian,
            'movement_reason' => $request->movement_reason,
            'notes' => $request->notes,
            'reference_number' => $request->reference_number,
            'created_by' => Auth::id(),
            'status' => 'draft',
        ]);

        return redirect()->route('assets.movements.index')
            ->with('success', 'Asset movement created successfully.');
    }

    /**
     * Display the specified movement
     */
    public function show(AssetMovement $movement)
    {
        $this->authorize('view', $movement);

        $movement->load(['asset.category', 'asset.fund', 'asset.project', 'asset.department', 'creator', 'approver']);

        return view('assets.movements.show', compact('movement'));
    }

    /**
     * Show the form for editing the specified movement
     */
    public function edit(AssetMovement $movement)
    {
        $this->authorize('update', $movement);

        if (!$movement->isDraft()) {
            return redirect()->route('assets.movements.show', $movement)
                ->with('error', 'Only draft movements can be edited.');
        }

        $movement->load(['asset.category', 'asset.fund', 'asset.project', 'asset.department']);

        return view('assets.movements.edit', compact('movement'));
    }

    /**
     * Update the specified movement
     */
    public function update(Request $request, AssetMovement $movement)
    {
        $this->authorize('update', $movement);

        if (!$movement->isDraft()) {
            return back()->with('error', 'Only draft movements can be updated.');
        }

        $request->validate([
            'movement_date' => 'required|date',
            'movement_type' => 'required|in:transfer,relocation,custodian_change,maintenance,other',
            'from_location' => 'nullable|string|max:255',
            'to_location' => 'nullable|string|max:255',
            'from_custodian' => 'nullable|string|max:255',
            'to_custodian' => 'nullable|string|max:255',
            'movement_reason' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:1000',
            'reference_number' => 'nullable|string|max:255',
        ]);

        $movement->update($request->only([
            'movement_date',
            'movement_type',
            'from_location',
            'to_location',
            'from_custodian',
            'to_custodian',
            'movement_reason',
            'notes',
            'reference_number',
        ]));

        return redirect()->route('assets.movements.show', $movement)
            ->with('success', 'Asset movement updated successfully.');
    }

    /**
     * Approve the movement
     */
    public function approve(AssetMovement $movement)
    {
        $this->authorize('update', $movement);

        if (!$movement->canBeApproved()) {
            return back()->with('error', 'This movement cannot be approved.');
        }

        try {
            $movement->approve(Auth::id());

            return redirect()->route('assets.movements.show', $movement)
                ->with('success', 'Asset movement approved successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error approving movement: ' . $e->getMessage());
        }
    }

    /**
     * Complete the movement
     */
    public function complete(AssetMovement $movement)
    {
        $this->authorize('update', $movement);

        if (!$movement->canBeCompleted()) {
            return back()->with('error', 'This movement cannot be completed.');
        }

        try {
            $movement->complete();

            return redirect()->route('assets.movements.show', $movement)
                ->with('success', 'Asset movement completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error completing movement: ' . $e->getMessage());
        }
    }

    /**
     * Cancel the movement
     */
    public function cancel(AssetMovement $movement)
    {
        $this->authorize('update', $movement);

        if (!$movement->canBeCancelled()) {
            return back()->with('error', 'This movement cannot be cancelled.');
        }

        try {
            $movement->cancel();

            return redirect()->route('assets.movements.show', $movement)
                ->with('success', 'Asset movement cancelled successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error cancelling movement: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified movement
     */
    public function destroy(AssetMovement $movement)
    {
        $this->authorize('delete', $movement);

        if (!$movement->isDraft()) {
            return back()->with('error', 'Only draft movements can be deleted.');
        }

        $movement->delete();

        return redirect()->route('assets.movements.index')
            ->with('success', 'Asset movement deleted successfully.');
    }

    /**
     * Get movements for a specific asset
     */
    public function assetMovements(Asset $asset)
    {
        $this->authorize('view', AssetMovement::class);

        $movements = $asset->movements()
            ->with(['creator', 'approver'])
            ->orderBy('movement_date', 'desc')
            ->get();

        return view('assets.movements.asset-history', compact('asset', 'movements'));
    }
}
