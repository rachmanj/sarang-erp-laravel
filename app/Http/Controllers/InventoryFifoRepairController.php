<?php

namespace App\Http\Controllers;

use App\Models\InventoryItem;
use App\Services\InventoryFifoRepairService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryFifoRepairController extends Controller
{
    public function __construct(private InventoryFifoRepairService $repairService) {}

    public function index(Request $request)
    {
        $search = $request->string('q')->trim()->toString();
        $issues = $this->repairService->findItemsNeedingRepair(
            $search !== '' ? $search : null,
            300
        );

        return view('inventory.fifo-repair.index', [
            'issues' => $issues,
            'search' => $search,
        ]);
    }

    public function show(InventoryItem $inventoryItem)
    {
        $diagnosis = $this->repairService->diagnose($inventoryItem);

        return view('inventory.fifo-repair.show', [
            'item' => $inventoryItem,
            'diagnosis' => $diagnosis,
        ]);
    }

    public function repair(InventoryItem $inventoryItem)
    {
        try {
            $result = $this->repairService->repair($inventoryItem, (int) Auth::id());
        } catch (\Throwable $exception) {
            return back()->with('error', 'FIFO repair failed: '.$exception->getMessage());
        }

        $message = $result['adjustments_created'] > 0
            ? 'FIFO repair completed. Created '.$result['adjustments_created'].' adjustment(s).'
            : $result['messages'][0] ?? 'FIFO repair completed.';

        return redirect()
            ->route('inventory.fifo-repair.show', $inventoryItem->id)
            ->with('success', $message)
            ->with('repair_messages', $result['messages']);
    }
}
