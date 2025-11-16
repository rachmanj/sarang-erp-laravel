<?php

namespace App\Http\Controllers;

use App\Models\ProductCategory;
use App\Models\Accounting\Account;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ProductCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:inventory.view');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = ProductCategory::with(['inventoryAccount', 'cogsAccount', 'salesAccount'])
            ->orderBy('name')
            ->paginate(15);

        return view('product-categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('inventory.create');
        
        // Get accounts for dropdowns
        $inventoryAccounts = Account::where('type', 'asset')
            ->where('code', 'like', '1.1.3.%')
            ->orderBy('code')
            ->get();
            
        $cogsAccounts = Account::where('type', 'expense')
            ->where('code', 'like', '5.1.%')
            ->orderBy('code')
            ->get();
            
        $salesAccounts = Account::where('type', 'income')
            ->where('code', 'like', '4.1.%')
            ->orderBy('code')
            ->get();

        return view('product-categories.create', compact('inventoryAccounts', 'cogsAccounts', 'salesAccounts'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('inventory.create');
        
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_categories,code'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'inventory_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'cogs_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'sales_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_active' => ['nullable', 'accepted'],
        ]);

        // Validate that accounts are either set or can be inherited from parent
        $parent = null;
        if ($data['parent_id']) {
            $parent = ProductCategory::find($data['parent_id']);
        }

        // For root categories, COGS and Sales are required
        if (!$parent) {
            if (empty($data['cogs_account_id'])) {
                return back()->withErrors(['cogs_account_id' => 'COGS Account is required for root categories.'])->withInput();
            }
            if (empty($data['sales_account_id'])) {
                return back()->withErrors(['sales_account_id' => 'Sales Account is required for root categories.'])->withInput();
            }
        } else {
            // For child categories, validate that accounts are either set or can be inherited
            if (empty($data['cogs_account_id'])) {
                $effectiveCogs = $parent->getEffectiveCogsAccount();
                if (!$effectiveCogs) {
                    return back()->withErrors(['cogs_account_id' => 'COGS Account is required. Either set it for this category or ensure the parent category has a COGS account.'])->withInput();
                }
            }
            if (empty($data['sales_account_id'])) {
                $effectiveSales = $parent->getEffectiveSalesAccount();
                if (!$effectiveSales) {
                    return back()->withErrors(['sales_account_id' => 'Sales Account is required. Either set it for this category or ensure the parent category has a Sales account.'])->withInput();
                }
            }
        }

        $data['is_active'] = $request->has('is_active');

        return DB::transaction(function () use ($data) {
            $category = ProductCategory::create($data);

            // Log the creation
            app(\App\Services\AuditLogService::class)->log(
                'created',
                'product_category',
                $category->id,
                null,
                $category->getAttributes(),
                "Product category '{$category->name}' created with account mappings"
            );

            return redirect()->route('product-categories.show', $category->id)
                ->with('success', 'Product category created successfully.');
        });
    }

    /**
     * Display the specified resource.
     */
    public function show(ProductCategory $productCategory)
    {
        $category = $productCategory->load(['inventoryAccount', 'cogsAccount', 'salesAccount', 'parent']);
        
        // Get effective accounts (with inheritance information)
        $inventorySource = $category->getAccountSource('inventory');
        $cogsSource = $category->getAccountSource('cogs');
        $salesSource = $category->getAccountSource('sales');
        
        // Get audit trail
        $auditTrail = app(\App\Services\AuditLogService::class)->getAuditTrail('product_category', $category->id);
        
        // Get child categories
        $childCategories = ProductCategory::where('parent_id', $category->id)
            ->with(['inventoryAccount', 'cogsAccount', 'salesAccount', 'parent'])
            ->get();
            
        // Get items in this category
        $items = $category->items()
            ->with(['defaultWarehouse'])
            ->paginate(10);

        return view('product-categories.show', compact(
            'category', 
            'auditTrail', 
            'childCategories', 
            'items',
            'inventorySource',
            'cogsSource',
            'salesSource'
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ProductCategory $productCategory)
    {
        $this->authorize('inventory.update');
        
        // Get accounts for dropdowns
        $inventoryAccounts = Account::where('type', 'asset')
            ->where('code', 'like', '1.1.3.%')
            ->orderBy('code')
            ->get();
            
        $cogsAccounts = Account::where('type', 'expense')
            ->where('code', 'like', '5.1.%')
            ->orderBy('code')
            ->get();
            
        $salesAccounts = Account::where('type', 'income')
            ->where('code', 'like', '4.1.%')
            ->orderBy('code')
            ->get();
            
        // Get parent categories (excluding current category and its children)
        $parentCategories = ProductCategory::where('id', '!=', $productCategory->id)
            ->where('parent_id', '!=', $productCategory->id)
            ->orderBy('name')
            ->get();

        return view('product-categories.edit', compact('productCategory', 'inventoryAccounts', 'cogsAccounts', 'salesAccounts', 'parentCategories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ProductCategory $productCategory)
    {
        $this->authorize('inventory.update');
        
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:product_categories,code,' . $productCategory->id],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'inventory_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'cogs_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'sales_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'is_active' => ['nullable', 'accepted'],
        ]);

        // Validate that accounts are either set or can be inherited from parent
        $parent = null;
        if ($data['parent_id'] && $data['parent_id'] != $productCategory->id) {
            $parent = ProductCategory::find($data['parent_id']);
        }

        // For root categories, COGS and Sales are required
        if (!$parent) {
            if (empty($data['cogs_account_id'])) {
                return back()->withErrors(['cogs_account_id' => 'COGS Account is required for root categories.'])->withInput();
            }
            if (empty($data['sales_account_id'])) {
                return back()->withErrors(['sales_account_id' => 'Sales Account is required for root categories.'])->withInput();
            }
        } else {
            // For child categories, validate that accounts are either set or can be inherited
            if (empty($data['cogs_account_id'])) {
                $effectiveCogs = $parent->getEffectiveCogsAccount();
                if (!$effectiveCogs) {
                    return back()->withErrors(['cogs_account_id' => 'COGS Account is required. Either set it for this category or ensure the parent category has a COGS account.'])->withInput();
                }
            }
            if (empty($data['sales_account_id'])) {
                $effectiveSales = $parent->getEffectiveSalesAccount();
                if (!$effectiveSales) {
                    return back()->withErrors(['sales_account_id' => 'Sales Account is required. Either set it for this category or ensure the parent category has a Sales account.'])->withInput();
                }
            }
        }

        $data['is_active'] = $request->has('is_active');

        return DB::transaction(function () use ($productCategory, $data) {
            $oldValues = $productCategory->getOriginal();
            $productCategory->update($data);

            // Log the update
            app(\App\Services\AuditLogService::class)->log(
                'updated',
                'product_category',
                $productCategory->id,
                $oldValues,
                $productCategory->getAttributes(),
                "Product category '{$productCategory->name}' updated with new account mappings"
            );

            return redirect()->route('product-categories.show', $productCategory->id)
                ->with('success', 'Product category updated successfully.');
        });
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ProductCategory $productCategory)
    {
        $this->authorize('inventory.delete');
        
        // Check if category has items
        if ($productCategory->items()->count() > 0) {
            return redirect()->route('product-categories.index')
                ->withErrors(['error' => 'Cannot delete category that has inventory items. Please move or delete the items first.']);
        }
        
        // Check if category has child categories
        if ($productCategory->children()->count() > 0) {
            return redirect()->route('product-categories.index')
                ->withErrors(['error' => 'Cannot delete category that has subcategories. Please delete the subcategories first.']);
        }

        return DB::transaction(function () use ($productCategory) {
            // Log the deletion
            app(\App\Services\AuditLogService::class)->log(
                'product_category',
                $productCategory->id,
                'deleted',
                $productCategory->getAttributes(),
                null,
                "Product category '{$productCategory->name}' deleted"
            );

            $productCategory->delete();

            return redirect()->route('product-categories.index')
                ->with('success', 'Product category deleted successfully.');
        });
    }

    /**
     * Get categories for AJAX requests
     */
    public function getCategories(Request $request)
    {
        $search = $request->get('q');
        $categories = ProductCategory::where('is_active', true)
            ->where('name', 'like', "%{$search}%")
            ->orWhere('code', 'like', "%{$search}%")
            ->limit(10)
            ->get(['id', 'name', 'code']);

        return response()->json($categories);
    }

    /**
     * Get account mapping summary for a category (returns effective accounts with inheritance info)
     */
    public function getAccountMapping(ProductCategory $productCategory)
    {
        $category = $productCategory->load(['inventoryAccount', 'cogsAccount', 'salesAccount', 'parent']);
        
        $inventorySource = $category->getAccountSource('inventory');
        $cogsSource = $category->getAccountSource('cogs');
        $salesSource = $category->getAccountSource('sales');
        
        return response()->json([
            'category_id' => $category->id,
            'category_name' => $category->name,
            'inventory_account' => $inventorySource['account'] ? [
                'id' => $inventorySource['account']->id,
                'code' => $inventorySource['account']->code,
                'name' => $inventorySource['account']->name,
                'is_inherited' => $inventorySource['is_inherited'],
                'source_category' => $inventorySource['source_category'] ? [
                    'id' => $inventorySource['source_category']->id,
                    'name' => $inventorySource['source_category']->name,
                ] : null,
            ] : null,
            'cogs_account' => $cogsSource['account'] ? [
                'id' => $cogsSource['account']->id,
                'code' => $cogsSource['account']->code,
                'name' => $cogsSource['account']->name,
                'is_inherited' => $cogsSource['is_inherited'],
                'source_category' => $cogsSource['source_category'] ? [
                    'id' => $cogsSource['source_category']->id,
                    'name' => $cogsSource['source_category']->name,
                ] : null,
            ] : null,
            'sales_account' => $salesSource['account'] ? [
                'id' => $salesSource['account']->id,
                'code' => $salesSource['account']->code,
                'name' => $salesSource['account']->name,
                'is_inherited' => $salesSource['is_inherited'],
                'source_category' => $salesSource['source_category'] ? [
                    'id' => $salesSource['source_category']->id,
                    'name' => $salesSource['source_category']->name,
                ] : null,
            ] : null,
        ]);
    }
}
