<!-- Item Selection Modal -->
<div class="modal fade" id="itemSelectionModal" tabindex="-1" role="dialog" aria-labelledby="itemSelectionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="itemSelectionModalLabel">
                    <i class="fas fa-search mr-2"></i>Select Item
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Search Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="searchCode">Item Code</label>
                            <input type="text" class="form-control form-control-sm" id="searchCode"
                                placeholder="Search by code...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="searchName">Item Name</label>
                            <input type="text" class="form-control form-control-sm" id="searchName"
                                placeholder="Search by name...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="searchCategory">Category</label>
                            <select class="form-control form-control-sm" id="searchCategory">
                                <option value="">All Categories</option>
                                @foreach (\App\Models\ProductCategory::with('parent')->active()->orderBy('name')->get() as $category)
                                    <option value="{{ $category->id }}">{{ $category->getHierarchicalName() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="searchType">Item Type</label>
                            <select class="form-control form-control-sm" id="searchType">
                                <option value="">All Types</option>
                                <option value="item">Physical Item</option>
                                <option value="service">Service</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row mb-3">
                    <div class="col-12">
                        <button type="button" class="btn btn-primary btn-sm" id="searchItems">
                            <i class="fas fa-search mr-1"></i>Search
                        </button>
                        <button type="button" class="btn btn-secondary btn-sm" id="clearSearch">
                            <i class="fas fa-times mr-1"></i>Clear
                        </button>
                        @can('inventory.create')
                            <button type="button" class="btn btn-success btn-sm ml-2" id="btnAddNewItem" title="Add new inventory item">
                                <i class="fas fa-plus mr-1"></i>Add New Item
                            </button>
                        @endcan
                        <span class="ml-3 text-muted" id="searchResultsCount"></span>
                    </div>
                </div>
                
                <!-- Stock Filters (only show for item type) -->
                <div class="row mb-2" id="stockFilters" style="display: none;">
                    <div class="col-12">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="filterInStock" value="1">
                            <label class="form-check-label" for="filterInStock">
                                <i class="fas fa-check-circle text-success"></i> Show Only In Stock
                            </label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" id="filterLowStock" value="1">
                            <label class="form-check-label" for="filterLowStock">
                                <i class="fas fa-exclamation-triangle text-warning"></i> Show Only Low Stock
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Items Table -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="itemsTable">
                        <thead class="thead-light">
                            <tr>
                                <th width="4%">#</th>
                                <th width="12%">Code</th>
                                <th width="20%">Name</th>
                                <th width="12%">Category</th>
                                <th width="8%">Type</th>
                                <th width="8%">UOM</th>
                                <th width="10%">Purchase Price</th>
                                <th width="10%">Selling Price</th>
                                <th width="12%">Available Qty</th>
                                <th width="4%">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Items pagination">
                    <ul class="pagination justify-content-center" id="itemsPagination">
                        <!-- Pagination will be generated via JavaScript -->
                    </ul>
                </nav>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Cancel
                </button>
            </div>
        </div>
    </div>
</div>

@can('inventory.create')
<!-- Quick Add Item Modal (nested) -->
<div class="modal fade" id="quickAddItemModal" tabindex="-1" role="dialog" aria-labelledby="quickAddItemModalLabel"
    aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="quickAddItemModalLabel">
                    <i class="fas fa-plus mr-2"></i>Add New Inventory Item
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="quickAddItemForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="quickItemCode">Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quickItemCode" name="code" required
                            placeholder="e.g. ITEM-001">
                    </div>
                    <div class="form-group">
                        <label for="quickItemName">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="quickItemName" name="name" required
                            placeholder="Item name">
                    </div>
                    <div class="form-group">
                        <label for="quickItemCategory">Category <span class="text-danger">*</span></label>
                        <select class="form-control" id="quickItemCategory" name="category_id" required>
                            <option value="">Select Category</option>
                            @foreach (\App\Models\ProductCategory::with('parent')->active()->orderBy('name')->get() as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->getHierarchicalName() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quickItemUnit">Unit of Measure <span class="text-danger">*</span></label>
                        <select class="form-control" id="quickItemUnit" name="base_unit_id" required>
                            <option value="">Select Unit</option>
                            @foreach (\App\Models\UnitOfMeasure::active()->orderBy('name')->get() as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quickItemPurchasePrice">Purchase Price</label>
                        <input type="number" class="form-control" id="quickItemPurchasePrice" name="purchase_price"
                            step="0.01" min="0" value="0" placeholder="0">
                    </div>
                    <div id="quickAddItemError" class="alert alert-danger" style="display: none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="quickAddItemSubmitBtn">
                        <i class="fas fa-save mr-1"></i>Save & Select
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(function() {
    $('#btnAddNewItem').on('click', function() {
        $('#quickAddItemModal').modal('show');
        $('#quickAddItemError').hide();
    });

    $('#quickAddItemForm').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var btn = $('#quickAddItemSubmitBtn');
        var errEl = $('#quickAddItemError');
        errEl.hide();

        btn.prop('disabled', true);
        $.ajax({
            url: '{{ route('inventory.api.quick-store') }}',
            method: 'POST',
            data: form.serialize(),
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            success: function(response) {
                if (response.success && response.item) {
                    $('#quickAddItemModal').modal('hide');
                    $('#itemSelectionModal').modal('hide');
                    form[0].reset();
                    if (typeof window.applySelectedItemToLine === 'function') {
                        window.applySelectedItemToLine(window.currentLineIndex, response.item);
                    } else if (typeof toastr !== 'undefined') {
                        toastr.success('Item created and selected');
                    }
                }
            },
            error: function(xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    var msgs = [];
                    $.each(xhr.responseJSON.errors, function(_, arr) { msgs.push(arr[0]); });
                    errEl.html(msgs.join('<br>')).show();
                } else {
                    errEl.html(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Failed to create item').show();
                }
            },
            complete: function() { btn.prop('disabled', false); }
        });
    });
});
</script>
@endpush
@endcan
