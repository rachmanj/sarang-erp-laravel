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
