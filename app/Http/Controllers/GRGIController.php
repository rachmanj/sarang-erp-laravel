<?php

namespace App\Http\Controllers;

use App\Models\GRGIHeader;
use App\Models\GRGILine;
use App\Models\GRGIPurpose;
use App\Models\GRGIAccountMapping;
use App\Models\InventoryItem;
use App\Models\Warehouse;
use App\Models\ProductCategory;
use App\Models\Accounting\Account;
use App\Services\GRGIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GRGIController extends Controller
{
    protected $grgiService;

    public function __construct(GRGIService $grgiService)
    {
        $this->grgiService = $grgiService;
    }

    /**
     * Display a listing of GR/GI documents
     */
    public function index(Request $request)
    {
        $this->authorize('gr-gi.view');

        $query = GRGIHeader::with(['purpose', 'warehouse', 'creator'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('document_type')) {
            $query->where('document_type', $request->document_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        $headers = $query->paginate(20);

        $warehouses = Warehouse::active()->get();
        $purposes = GRGIPurpose::active()->get();

        return view('gr-gi.index', compact('headers', 'warehouses', 'purposes'));
    }

    /**
     * Show the form for creating a new GR/GI document
     */
    public function create(Request $request)
    {
        $this->authorize('gr-gi.create');

        $documentType = $request->get('type', 'goods_receipt');

        $purposes = $this->grgiService->getPurposes($documentType);
        $warehouses = Warehouse::active()->get();
        $items = InventoryItem::active()->get();
        $categories = ProductCategory::with('parent')->active()->get();
        $accounts = Account::postable()->get();

        return view('gr-gi.create', compact(
            'documentType',
            'purposes',
            'warehouses',
            'items',
            'categories',
            'accounts'
        ));
    }

    /**
     * Store a newly created GR/GI document
     */
    public function store(Request $request)
    {
        $this->authorize('gr-gi.create');

        $data = $request->validate([
            'document_type' => ['required', 'in:goods_receipt,goods_issue'],
            'purpose_id' => ['required', 'exists:gr_gi_purposes,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            $header = $this->grgiService->createHeader($data);

            // Add lines
            foreach ($data['lines'] as $lineData) {
                $this->grgiService->addLine($header->id, $lineData);
            }

            return redirect()->route('gr-gi.show', $header)
                ->with('success', 'GR/GI document created successfully');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error creating GR/GI document: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified GR/GI document
     */
    public function show(GRGIHeader $grGi)
    {
        $this->authorize('gr-gi.view');

        $grGi->load(['lines.item', 'purpose', 'warehouse', 'creator', 'approver', 'journalEntries.journalEntry']);

        return view('gr-gi.show', compact('grGi'));
    }

    /**
     * Show the form for editing the specified GR/GI document
     */
    public function edit(GRGIHeader $grGi)
    {
        $this->authorize('gr-gi.update');

        if (!$grGi->canBeEdited()) {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Document cannot be edited');
        }

        $purposes = $this->grgiService->getPurposes($grGi->document_type);
        $warehouses = Warehouse::active()->get();
        $items = InventoryItem::active()->get();
        $categories = ProductCategory::active()->get();
        $accounts = Account::postable()->get();

        $grGi->load(['lines.item']);

        return view('gr-gi.edit', compact(
            'grGi',
            'purposes',
            'warehouses',
            'items',
            'categories',
            'accounts'
        ));
    }

    /**
     * Update the specified GR/GI document
     */
    public function update(Request $request, GRGIHeader $grGi)
    {
        $this->authorize('gr-gi.update');

        if (!$grGi->canBeEdited()) {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Document cannot be edited');
        }

        $data = $request->validate([
            'purpose_id' => ['required', 'exists:gr_gi_purposes,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'transaction_date' => ['required', 'date'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'exists:gr_gi_lines,id'],
            'lines.*.item_id' => ['required', 'exists:inventory_items,id'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        try {
            // Update header
            $grGi->update([
                'purpose_id' => $data['purpose_id'],
                'warehouse_id' => $data['warehouse_id'],
                'transaction_date' => $data['transaction_date'],
                'reference_number' => $data['reference_number'],
                'notes' => $data['notes'],
            ]);

            // Update lines
            $existingLineIds = [];
            foreach ($data['lines'] as $lineData) {
                if (isset($lineData['id'])) {
                    // Update existing line
                    $line = GRGILine::findOrFail($lineData['id']);
                    $line->update([
                        'item_id' => $lineData['item_id'],
                        'quantity' => $lineData['quantity'],
                        'unit_price' => $lineData['unit_price'],
                        'total_amount' => $lineData['quantity'] * $lineData['unit_price'],
                        'notes' => $lineData['notes'],
                    ]);
                    $existingLineIds[] = $line->id;
                } else {
                    // Add new line
                    $this->grgiService->addLine($grGi->id, $lineData);
                }
            }

            // Remove deleted lines
            $grGi->lines()->whereNotIn('id', $existingLineIds)->delete();

            // Update total
            $this->grgiService->updateHeaderTotal($grGi->id);

            return redirect()->route('gr-gi.show', $grGi)
                ->with('success', 'GR/GI document updated successfully');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Error updating GR/GI document: ' . $e->getMessage());
        }
    }

    /**
     * Submit GR/GI document for approval
     */
    public function submit(Request $request, GRGIHeader $grGi)
    {
        $this->authorize('gr-gi.update');

        if ($grGi->status !== 'draft') {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Only draft documents can be submitted for approval');
        }

        try {
            $grGi->update(['status' => 'pending_approval']);

            return redirect()->route('gr-gi.show', $grGi)
                ->with('success', 'GR/GI document submitted for approval successfully');
        } catch (\Exception $e) {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Error submitting document: ' . $e->getMessage());
        }
    }

    /**
     * Approve the specified GR/GI document
     */
    public function approve(Request $request, GRGIHeader $grGi)
    {
        $this->authorize('gr-gi.approve');

        if (!$grGi->canBeApproved()) {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Document cannot be approved');
        }

        try {
            $this->grgiService->approve($grGi->id, Auth::id());

            return redirect()->route('gr-gi.show', $grGi)
                ->with('success', 'GR/GI document approved successfully');
        } catch (\Exception $e) {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Error approving document: ' . $e->getMessage());
        }
    }

    /**
     * Cancel the specified GR/GI document
     */
    public function cancel(GRGIHeader $grGi)
    {
        $this->authorize('gr-gi.delete');

        if (!$grGi->canBeCancelled()) {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Document cannot be cancelled');
        }

        try {
            $this->grgiService->cancel($grGi->id, Auth::id());

            return redirect()->route('gr-gi.show', $grGi)
                ->with('success', 'GR/GI document cancelled successfully');
        } catch (\Exception $e) {
            return redirect()->route('gr-gi.show', $grGi)
                ->with('error', 'Error cancelling document: ' . $e->getMessage());
        }
    }

    /**
     * Get GR/GI purposes by type
     */
    public function getPurposes(Request $request)
    {
        $type = $request->get('type');
        $purposes = $this->grgiService->getPurposes($type);

        return response()->json($purposes);
    }

    /**
     * Get account mappings
     */
    public function getAccountMappings(Request $request)
    {
        $purposeId = $request->get('purpose_id');
        $categoryId = $request->get('category_id');

        $mapping = GRGIAccountMapping::where('purpose_id', $purposeId)
            ->where('item_category_id', $categoryId)
            ->with(['debitAccount', 'creditAccount'])
            ->first();

        return response()->json($mapping);
    }

    /**
     * Calculate GI valuation
     */
    public function calculateValuation(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:inventory_items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'method' => ['nullable', 'in:FIFO,LIFO,AVERAGE'],
        ]);

        try {
            $valuation = $this->grgiService->calculateGIValuation(
                $data['item_id'],
                $data['warehouse_id'],
                $data['quantity'],
                $data['method'] ?? 'FIFO'
            );

            return response()->json($valuation);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get items for dropdown
     */
    public function getItems()
    {
        $items = InventoryItem::active()
            ->select('id', 'code', 'name', 'unit_of_measure', 'standard_cost')
            ->orderBy('name')
            ->get();

        return response()->json($items);
    }

    /**
     * Get warehouses for dropdown
     */
    public function getWarehouses()
    {
        $warehouses = Warehouse::active()
            ->select('id', 'code', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($warehouses);
    }
}
