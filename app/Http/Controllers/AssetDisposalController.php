<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDisposal;
use App\Services\Accounting\FixedAssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AssetDisposalController extends Controller
{
    public function __construct(
        private FixedAssetService $fixedAssetService
    ) {}

    /**
     * Display a listing of asset disposals
     */
    public function index()
    {
        $this->authorize('view', AssetDisposal::class);

        return view('assets.disposals.index');
    }

    /**
     * Get disposals data for DataTables
     */
    public function data(Request $request)
    {
        $this->authorize('view', AssetDisposal::class);

        $query = AssetDisposal::with(['asset.category', 'creator', 'poster'])
            ->select([
                'asset_disposals.*',
                'assets.code as asset_code',
                'assets.name as asset_name',
                'asset_categories.name as category_name'
            ])
            ->join('assets', 'asset_disposals.asset_id', '=', 'assets.id')
            ->join('asset_categories', 'assets.category_id', '=', 'asset_categories.id');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('asset_disposals.status', $request->status);
        }

        if ($request->filled('disposal_type')) {
            $query->where('asset_disposals.disposal_type', $request->disposal_type);
        }

        if ($request->filled('date_from')) {
            $query->where('asset_disposals.disposal_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('asset_disposals.disposal_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('assets.code', 'like', "%{$search}%")
                    ->orWhere('assets.name', 'like', "%{$search}%")
                    ->orWhere('asset_categories.name', 'like', "%{$search}%")
                    ->orWhere('asset_disposals.disposal_reference', 'like', "%{$search}%");
            });
        }

        $disposals = $query->orderBy('asset_disposals.disposal_date', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json([
            'data' => $disposals->items(),
            'total' => $disposals->total(),
            'per_page' => $disposals->perPage(),
            'current_page' => $disposals->currentPage(),
            'last_page' => $disposals->lastPage(),
        ]);
    }

    /**
     * Show the form for creating a new disposal
     */
    public function create(Request $request)
    {
        $this->authorize('create', AssetDisposal::class);

        $assetId = $request->get('asset_id');
        $asset = null;

        if ($assetId) {
            $asset = Asset::with(['category', 'fund', 'project', 'department'])
                ->findOrFail($assetId);

            if (!$asset->canBeDisposed()) {
                return redirect()->route('assets.index')
                    ->with('error', 'This asset cannot be disposed.');
            }
        }

        return view('assets.disposals.create', compact('asset'));
    }

    /**
     * Store a newly created disposal
     */
    public function store(Request $request)
    {
        $this->authorize('create', AssetDisposal::class);

        $request->validate([
            'asset_id' => 'required|exists:assets,id',
            'disposal_date' => 'required|date',
            'disposal_type' => 'required|in:sale,scrap,donation,trade_in,other',
            'disposal_proceeds' => 'nullable|numeric|min:0',
            'disposal_reason' => 'nullable|string|max:1000',
            'disposal_method' => 'nullable|string|max:255',
            'disposal_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $asset = Asset::findOrFail($request->asset_id);

        if (!$asset->canBeDisposed()) {
            return back()->with('error', 'This asset cannot be disposed.');
        }

        DB::transaction(function () use ($request, $asset) {
            $disposal = AssetDisposal::create([
                'asset_id' => $asset->id,
                'disposal_date' => $request->disposal_date,
                'disposal_type' => $request->disposal_type,
                'disposal_proceeds' => $request->disposal_proceeds,
                'book_value_at_disposal' => $asset->current_book_value,
                'disposal_reason' => $request->disposal_reason,
                'disposal_method' => $request->disposal_method,
                'disposal_reference' => $request->disposal_reference,
                'notes' => $request->notes,
                'created_by' => Auth::id(),
                'status' => 'draft',
            ]);

            // Update asset status to disposed
            $asset->update([
                'status' => 'disposed',
                'disposal_date' => $request->disposal_date,
            ]);
        });

        return redirect()->route('assets.disposals.index')
            ->with('success', 'Asset disposal created successfully.');
    }

    /**
     * Display the specified disposal
     */
    public function show(AssetDisposal $disposal)
    {
        $this->authorize('view', $disposal);

        $disposal->load(['asset.category', 'asset.fund', 'asset.project', 'asset.department', 'creator', 'poster', 'journal']);

        return view('assets.disposals.show', compact('disposal'));
    }

    /**
     * Show the form for editing the specified disposal
     */
    public function edit(AssetDisposal $disposal)
    {
        $this->authorize('update', $disposal);

        if (!$disposal->isDraft()) {
            return redirect()->route('assets.disposals.show', $disposal)
                ->with('error', 'Only draft disposals can be edited.');
        }

        $disposal->load(['asset.category', 'asset.fund', 'asset.project', 'asset.department']);

        return view('assets.disposals.edit', compact('disposal'));
    }

    /**
     * Update the specified disposal
     */
    public function update(Request $request, AssetDisposal $disposal)
    {
        $this->authorize('update', $disposal);

        if (!$disposal->isDraft()) {
            return back()->with('error', 'Only draft disposals can be updated.');
        }

        $request->validate([
            'disposal_date' => 'required|date',
            'disposal_type' => 'required|in:sale,scrap,donation,trade_in,other',
            'disposal_proceeds' => 'nullable|numeric|min:0',
            'disposal_reason' => 'nullable|string|max:1000',
            'disposal_method' => 'nullable|string|max:255',
            'disposal_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $disposal->update($request->only([
            'disposal_date',
            'disposal_type',
            'disposal_proceeds',
            'disposal_reason',
            'disposal_method',
            'disposal_reference',
            'notes',
        ]));

        // Update asset disposal date
        $disposal->asset->update([
            'disposal_date' => $request->disposal_date,
        ]);

        return redirect()->route('assets.disposals.show', $disposal)
            ->with('success', 'Asset disposal updated successfully.');
    }

    /**
     * Post the disposal to GL
     */
    public function post(AssetDisposal $disposal)
    {
        $this->authorize('update', $disposal);

        if (!$disposal->canBePosted()) {
            return back()->with('error', 'This disposal cannot be posted.');
        }

        try {
            $this->fixedAssetService->postAssetDisposal($disposal, Auth::id());

            return redirect()->route('assets.disposals.show', $disposal)
                ->with('success', 'Asset disposal posted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error posting disposal: ' . $e->getMessage());
        }
    }

    /**
     * Reverse a posted disposal
     */
    public function reverse(AssetDisposal $disposal)
    {
        $this->authorize('update', $disposal);

        if (!$disposal->canBeReversed()) {
            return back()->with('error', 'This disposal cannot be reversed.');
        }

        try {
            $this->fixedAssetService->reverseAssetDisposal($disposal, Auth::id());

            return redirect()->route('assets.disposals.show', $disposal)
                ->with('success', 'Asset disposal reversed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error reversing disposal: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified disposal
     */
    public function destroy(AssetDisposal $disposal)
    {
        $this->authorize('delete', $disposal);

        if (!$disposal->isDraft()) {
            return back()->with('error', 'Only draft disposals can be deleted.');
        }

        DB::transaction(function () use ($disposal) {
            // Restore asset status
            $disposal->asset->update([
                'status' => 'active',
                'disposal_date' => null,
            ]);

            $disposal->delete();
        });

        return redirect()->route('assets.disposals.index')
            ->with('success', 'Asset disposal deleted successfully.');
    }
}
