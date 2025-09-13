<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\Accounting\Account;
use App\Models\Fund;
use App\Models\Project;
use App\Models\Department;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class AssetController extends Controller
{
    public function index()
    {
        return view('assets.index');
    }

    public function data(Request $request)
    {
        $query = Asset::with([
            'category',
            'fund',
            'project',
            'department',
            'vendor'
        ]);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fund_id')) {
            $query->where('fund_id', $request->fund_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $assets = $query->get();

        return DataTables::of($assets)
            ->addColumn('category_name', function ($asset) {
                return $asset->category->name ?? 'N/A';
            })
            ->addColumn('acquisition_cost_formatted', function ($asset) {
                return number_format($asset->acquisition_cost, 2);
            })
            ->addColumn('current_book_value_formatted', function ($asset) {
                return number_format($asset->current_book_value, 2);
            })
            ->addColumn('accumulated_depreciation_formatted', function ($asset) {
                return number_format($asset->accumulated_depreciation, 2);
            })
            ->addColumn('depreciation_info', function ($asset) {
                if ($asset->category && $asset->category->non_depreciable) {
                    return '<span class="badge badge-info">Non-Depreciable</span>';
                }

                $monthsRemaining = $asset->remaining_life_months;
                $depreciationRate = round($asset->depreciation_rate * 100, 2);

                return sprintf(
                    '%d months remaining<br>Rate: %s%%',
                    $monthsRemaining,
                    $depreciationRate
                );
            })
            ->addColumn('dimensions', function ($asset) {
                $dimensions = [];
                if ($asset->fund) $dimensions[] = "Fund: {$asset->fund->name}";
                if ($asset->project) $dimensions[] = "Project: {$asset->project->name}";
                if ($asset->department) $dimensions[] = "Dept: {$asset->department->name}";

                return $dimensions ? implode('<br>', $dimensions) : 'No dimensions';
            })
            ->addColumn('vendor_name', function ($asset) {
                return $asset->vendor->name ?? 'N/A';
            })
            ->addColumn('placed_in_service_formatted', function ($asset) {
                return $asset->placed_in_service_date->format('M d, Y');
            })
            ->addColumn('status_badge', function ($asset) {
                return match ($asset->status) {
                    'active' => '<span class="badge badge-success">Active</span>',
                    'retired' => '<span class="badge badge-warning">Retired</span>',
                    'disposed' => '<span class="badge badge-danger">Disposed</span>',
                    default => '<span class="badge badge-secondary">Unknown</span>',
                };
            })
            ->addColumn('actions', function ($asset) {
                $actions = '';

                if (auth()->user()->can('assets.view')) {
                    $actions .= sprintf(
                        '<a href="/assets/%d" class="btn btn-sm btn-info">View</a> ',
                        $asset->id
                    );
                }

                if (auth()->user()->can('assets.update') && $asset->status === 'active') {
                    $actions .= sprintf(
                        '<a href="#" class="btn btn-sm btn-primary edit-asset" data-id="%d" data-asset=\'%s\'>Edit</a> ',
                        $asset->id,
                        htmlspecialchars(json_encode([
                            'code' => $asset->code,
                            'name' => $asset->name,
                            'description' => $asset->description,
                            'serial_number' => $asset->serial_number,
                            'category_id' => $asset->category_id,
                            'acquisition_cost' => $asset->acquisition_cost,
                            'salvage_value' => $asset->salvage_value,
                            'method' => $asset->method,
                            'life_months' => $asset->life_months,
                            'placed_in_service_date' => $asset->placed_in_service_date->format('Y-m-d'),
                            'fund_id' => $asset->fund_id,
                            'project_id' => $asset->project_id,
                            'department_id' => $asset->department_id,
                            'vendor_id' => $asset->vendor_id,
                        ]))
                    );
                }

                if (auth()->user()->can('assets.delete') && $asset->canBeDeleted()) {
                    $actions .= sprintf(
                        '<a href="#" class="btn btn-sm btn-danger delete-asset" data-id="%d" data-name="%s">Delete</a>',
                        $asset->id,
                        htmlspecialchars($asset->name)
                    );
                }

                return $actions;
            })
            ->rawColumns(['depreciation_info', 'dimensions', 'status_badge', 'actions'])
            ->make(true);
    }

    public function show(Asset $asset)
    {
        $asset->load([
            'category',
            'fund',
            'project',
            'department',
            'vendor',
            'depreciationEntries.journal'
        ]);

        return view('assets.show', compact('asset'));
    }

    public function create()
    {
        $categories = AssetCategory::active()->get();
        $funds = Fund::all();
        $projects = Project::all();
        $departments = Department::all();
        $vendors = Vendor::all();

        return view('assets.create', compact('categories', 'funds', 'projects', 'departments', 'vendors'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:assets,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'required|exists:asset_categories,id',
            'acquisition_cost' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0',
            'method' => 'required|in:straight_line,declining_balance',
            'life_months' => 'required|integer|min:1',
            'placed_in_service_date' => 'required|date',
            'fund_id' => 'nullable|exists:funds,id',
            'project_id' => 'nullable|exists:projects,id',
            'department_id' => 'nullable|exists:departments,id',
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);

        $assetData = $request->all();
        $assetData['current_book_value'] = $request->acquisition_cost;
        $assetData['accumulated_depreciation'] = 0;

        $asset = Asset::create($assetData);

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    public function edit(Asset $asset)
    {
        $categories = AssetCategory::active()->get();
        $funds = Fund::all();
        $projects = Project::all();
        $departments = Department::all();
        $vendors = Vendor::all();

        return view('assets.edit', compact('asset', 'categories', 'funds', 'projects', 'departments', 'vendors'));
    }

    public function update(Request $request, Asset $asset)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:assets,code,' . $asset->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'serial_number' => 'nullable|string|max:255',
            'category_id' => 'required|exists:asset_categories,id',
            'acquisition_cost' => 'required|numeric|min:0',
            'salvage_value' => 'required|numeric|min:0',
            'method' => 'required|in:straight_line,declining_balance',
            'life_months' => 'required|integer|min:1',
            'placed_in_service_date' => 'required|date',
            'fund_id' => 'nullable|exists:funds,id',
            'project_id' => 'nullable|exists:projects,id',
            'department_id' => 'nullable|exists:departments,id',
            'vendor_id' => 'nullable|exists:vendors,id',
        ]);

        // Prevent updating acquisition cost if asset has depreciation entries
        if ($asset->depreciationEntries()->exists() && $request->acquisition_cost != $asset->acquisition_cost) {
            return back()->withErrors(['acquisition_cost' => 'Cannot change acquisition cost for assets with existing depreciation entries.']);
        }

        $asset->update($request->all());

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    public function destroy(Asset $asset)
    {
        if (!$asset->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete asset with existing depreciation entries.'
            ], 422);
        }

        $asset->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset deleted successfully.'
        ]);
    }

    public function getCategories()
    {
        $categories = AssetCategory::active()->get(['id', 'code', 'name']);
        return response()->json($categories);
    }

    public function getFunds()
    {
        $funds = Fund::all(['id', 'code', 'name']);
        return response()->json($funds);
    }

    public function getProjects()
    {
        $projects = Project::all(['id', 'code', 'name']);
        return response()->json($projects);
    }

    public function getDepartments()
    {
        $departments = Department::all(['id', 'code', 'name']);
        return response()->json($departments);
    }

    public function getVendors()
    {
        $vendors = Vendor::all(['id', 'code', 'name']);
        return response()->json($vendors);
    }

    public function bulkUpdateIndex()
    {
        $this->authorize('update', Asset::class);

        $funds = Fund::all(['id', 'code', 'name']);
        $projects = Project::all(['id', 'code', 'name']);
        $departments = Department::all(['id', 'code', 'name']);
        $vendors = Vendor::all(['id', 'code', 'name']);

        return view('assets.bulk-operations.index', compact('funds', 'projects', 'departments', 'vendors'));
    }

    public function bulkUpdate(Request $request)
    {
        $this->authorize('update', Asset::class);

        $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
            'updates' => 'required|array',
            'updates.fund_id' => 'nullable|exists:funds,id',
            'updates.project_id' => 'nullable|exists:projects,id',
            'updates.department_id' => 'nullable|exists:departments,id',
            'updates.vendor_id' => 'nullable|exists:vendors,id',
            'updates.status' => 'nullable|in:active,retired,disposed',
            'updates.description' => 'nullable|string|max:1000',
            'updates.serial_number' => 'nullable|string|max:100',
            'updates.salvage_value' => 'nullable|numeric|min:0',
            'updates.method' => 'nullable|in:straight_line,declining_balance,double_declining_balance',
            'updates.life_months' => 'nullable|integer|min:1|max:600',
            'updates.placed_in_service_date' => 'nullable|date'
        ]);

        $assetIds = $request->get('asset_ids');
        $updates = $request->get('updates');

        // Remove null values
        $updates = array_filter($updates, function ($value) {
            return $value !== null && $value !== '';
        });

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No valid updates provided'
            ], 400);
        }

        try {
            DB::beginTransaction();

            $updatedCount = Asset::whereIn('id', $assetIds)->update($updates);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} assets",
                'updated_count' => $updatedCount
            ]);
        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdateData(Request $request)
    {
        $this->authorize('view', Asset::class);

        $query = Asset::with(['category', 'fund', 'project', 'department', 'vendor'])
            ->select(['id', 'code', 'name', 'description', 'serial_number', 'category_id', 'fund_id', 'project_id', 'department_id', 'vendor_id', 'status', 'acquisition_cost', 'salvage_value', 'method', 'life_months', 'placed_in_service_date']);

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('fund_id')) {
            $query->where('fund_id', $request->fund_id);
        }

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%");
            });
        }

        return DataTables::of($query)
            ->addColumn('category_name', function ($asset) {
                return $asset->category ? $asset->category->name : '-';
            })
            ->addColumn('fund_name', function ($asset) {
                return $asset->fund ? $asset->fund->name : '-';
            })
            ->addColumn('project_name', function ($asset) {
                return $asset->project ? $asset->project->name : '-';
            })
            ->addColumn('department_name', function ($asset) {
                return $asset->department ? $asset->department->name : '-';
            })
            ->addColumn('vendor_name', function ($asset) {
                return $asset->vendor ? $asset->vendor->name : '-';
            })
            ->editColumn('acquisition_cost', function ($asset) {
                return 'Rp ' . number_format($asset->acquisition_cost, 0, ',', '.');
            })
            ->editColumn('placed_in_service_date', function ($asset) {
                return $asset->placed_in_service_date ? $asset->placed_in_service_date->format('d/m/Y') : '-';
            })
            ->addColumn('checkbox', function ($asset) {
                return '<input type="checkbox" class="asset-checkbox" value="' . $asset->id . '">';
            })
            ->rawColumns(['checkbox'])
            ->toJson();
    }

    public function bulkUpdatePreview(Request $request)
    {
        $this->authorize('view', Asset::class);

        $request->validate([
            'asset_ids' => 'required|array|min:1',
            'asset_ids.*' => 'exists:assets,id',
            'updates' => 'required|array'
        ]);

        $assetIds = $request->get('asset_ids');
        $updates = $request->get('updates');

        $assets = Asset::whereIn('id', $assetIds)
            ->with(['category', 'fund', 'project', 'department', 'vendor'])
            ->get();

        $preview = $assets->map(function ($asset) use ($updates) {
            $previewData = [
                'id' => $asset->id,
                'code' => $asset->code,
                'name' => $asset->name,
                'current_values' => [],
                'new_values' => []
            ];

            foreach ($updates as $field => $newValue) {
                if ($newValue !== null && $newValue !== '') {
                    $previewData['current_values'][$field] = $asset->$field;
                    $previewData['new_values'][$field] = $newValue;
                }
            }

            return $previewData;
        });

        return response()->json($preview);
    }
}
