<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssetCategory;
use App\Models\Accounting\Account;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AssetCategoryController extends Controller
{
    public function index()
    {
        return view('asset-categories.index');
    }

    public function data()
    {
        $categories = AssetCategory::with([
            'assetAccount',
            'accumulatedDepreciationAccount',
            'depreciationExpenseAccount',
            'assets'
        ])->get();

        return DataTables::of($categories)
            ->addColumn('account_mappings', function ($category) {
                return sprintf(
                    'Asset: %s<br>Accum Dep: %s<br>Dep Exp: %s',
                    $category->assetAccount->code ?? 'N/A',
                    $category->accumulatedDepreciationAccount->code ?? 'N/A',
                    $category->depreciationExpenseAccount->code ?? 'N/A'
                );
            })
            ->addColumn('depreciation_info', function ($category) {
                if ($category->non_depreciable) {
                    return '<span class="badge badge-info">Non-Depreciable</span>';
                }

                return sprintf(
                    '%d months<br>%s',
                    $category->life_months_default ?? 'N/A',
                    ucfirst(str_replace('_', ' ', $category->method_default))
                );
            })
            ->addColumn('asset_count', function ($category) {
                return $category->assets->count();
            })
            ->addColumn('status', function ($category) {
                return $category->is_active
                    ? '<span class="badge badge-success">Active</span>'
                    : '<span class="badge badge-secondary">Inactive</span>';
            })
            ->addColumn('actions', function ($category) {
                $actions = '';

                if (auth()->user()->can('asset_categories.manage')) {
                    $actions .= sprintf(
                        '<a href="#" class="btn btn-sm btn-primary edit-category" data-id="%d" data-category=\'%s\'>Edit</a> ',
                        $category->id,
                        htmlspecialchars(json_encode([
                            'code' => $category->code,
                            'name' => $category->name,
                            'description' => $category->description,
                            'life_months_default' => $category->life_months_default,
                            'method_default' => $category->method_default,
                            'salvage_value_policy' => $category->salvage_value_policy,
                            'non_depreciable' => $category->non_depreciable,
                            'asset_account_id' => $category->asset_account_id,
                            'accumulated_depreciation_account_id' => $category->accumulated_depreciation_account_id,
                            'depreciation_expense_account_id' => $category->depreciation_expense_account_id,
                            'gain_on_disposal_account_id' => $category->gain_on_disposal_account_id,
                            'loss_on_disposal_account_id' => $category->loss_on_disposal_account_id,
                            'is_active' => $category->is_active,
                        ]))
                    );
                }

                if (auth()->user()->can('asset_categories.manage') && $category->canBeDeleted()) {
                    $actions .= sprintf(
                        '<a href="#" class="btn btn-sm btn-danger delete-category" data-id="%d" data-name="%s">Delete</a>',
                        $category->id,
                        htmlspecialchars($category->name)
                    );
                }

                return $actions;
            })
            ->rawColumns(['account_mappings', 'depreciation_info', 'status', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:asset_categories,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'life_months_default' => 'nullable|integer|min:1',
            'method_default' => 'required|in:straight_line,declining_balance',
            'salvage_value_policy' => 'required|numeric|min:0',
            'non_depreciable' => 'boolean',
            'asset_account_id' => 'required|exists:accounts,id',
            'accumulated_depreciation_account_id' => 'required|exists:accounts,id',
            'depreciation_expense_account_id' => 'required|exists:accounts,id',
            'gain_on_disposal_account_id' => 'nullable|exists:accounts,id',
            'loss_on_disposal_account_id' => 'nullable|exists:accounts,id',
            'is_active' => 'boolean',
        ]);

        $category = AssetCategory::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Asset category created successfully.',
            'data' => $category
        ]);
    }

    public function update(Request $request, AssetCategory $assetCategory)
    {
        $request->validate([
            'code' => 'required|string|max:50|unique:asset_categories,code,' . $assetCategory->id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'life_months_default' => 'nullable|integer|min:1',
            'method_default' => 'required|in:straight_line,declining_balance',
            'salvage_value_policy' => 'required|numeric|min:0',
            'non_depreciable' => 'boolean',
            'asset_account_id' => 'required|exists:accounts,id',
            'accumulated_depreciation_account_id' => 'required|exists:accounts,id',
            'depreciation_expense_account_id' => 'required|exists:accounts,id',
            'gain_on_disposal_account_id' => 'nullable|exists:accounts,id',
            'loss_on_disposal_account_id' => 'nullable|exists:accounts,id',
            'is_active' => 'boolean',
        ]);

        $assetCategory->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Asset category updated successfully.',
            'data' => $assetCategory
        ]);
    }

    public function destroy(AssetCategory $assetCategory)
    {
        if (!$assetCategory->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category with existing assets.'
            ], 422);
        }

        $assetCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Asset category deleted successfully.'
        ]);
    }

    public function getAccounts()
    {
        $accounts = Account::where('is_postable', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return response()->json($accounts);
    }
}
