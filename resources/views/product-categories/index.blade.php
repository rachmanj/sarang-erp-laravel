@extends('layouts.main')

@section('title_page')
    Product Categories
@endsection

@section('breadcrumb_title')
    <li class="breadcrumb-item"><a href="/dashboard">Dashboard</a></li>
    <li class="breadcrumb-item active">Product Categories</li>
@endsection

@section('content')

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <!-- Header Actions -->
            <div class="row mb-3">
                <div class="col-12">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-tags mr-2"></i>
                                Product Category Management
                            </h3>
                            <div class="card-tools">
                                @can('inventory.create')
                                    <a href="{{ route('product-categories.create') }}" class="btn btn-primary btn-sm">
                                        <i class="fas fa-plus mr-1"></i>
                                        Add Category
                                    </a>
                                @endcan
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div class="row">
                <div class="col-12">
                    <div class="card card-outline">
                        <div class="card-header">
                            <h3 class="card-title">
                                <i class="fas fa-list mr-2"></i>
                                All Categories
                            </h3>
                        </div>
                        <div class="card-body">
                            @if ($categories->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="thead-dark">
                                            <tr>
                                                <th>Code</th>
                                                <th>Name</th>
                                                <th>Description</th>
                                                <th>Parent Category</th>
                                                <th>Inventory Account</th>
                                                <th>COGS Account</th>
                                                <th>Sales Account</th>
                                                <th>Status</th>
                                                <th>Items Count</th>
                                                <th width="120">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($categories as $category)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-info">{{ $category->code }}</span>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $category->name }}</strong>
                                                    </td>
                                                    <td>{{ $category->description ?? '-' }}</td>
                                                    <td>
                                                        @if ($category->parent)
                                                            <span
                                                                class="badge badge-secondary">{{ $category->parent->name }}</span>
                                                        @else
                                                            <span class="text-muted">Root Category</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($category->inventoryAccount)
                                                            <small
                                                                class="text-muted">{{ $category->inventoryAccount->code }}</small><br>
                                                            <strong>{{ $category->inventoryAccount->name }}</strong>
                                                        @else
                                                            <span class="text-muted">Not Set</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($category->cogsAccount)
                                                            <small
                                                                class="text-muted">{{ $category->cogsAccount->code }}</small><br>
                                                            <strong>{{ $category->cogsAccount->name }}</strong>
                                                        @else
                                                            <span class="text-muted">Not Set</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($category->salesAccount)
                                                            <small
                                                                class="text-muted">{{ $category->salesAccount->code }}</small><br>
                                                            <strong>{{ $category->salesAccount->name }}</strong>
                                                        @else
                                                            <span class="text-muted">Not Set</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @if ($category->is_active)
                                                            <span class="badge badge-success">Active</span>
                                                        @else
                                                            <span class="badge badge-secondary">Inactive</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge badge-primary">{{ $category->items()->count() }}</span>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <a href="{{ route('product-categories.show', $category->id) }}"
                                                                class="btn btn-info btn-sm" title="View Details">
                                                                <i class="fas fa-eye"></i>
                                                            </a>
                                                            @can('inventory.update')
                                                                <a href="{{ route('product-categories.edit', $category->id) }}"
                                                                    class="btn btn-warning btn-sm" title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endcan
                                                            @can('inventory.delete')
                                                                <button type="button" class="btn btn-danger btn-sm"
                                                                    onclick="deleteCategory({{ $category->id }}, '{{ $category->name }}')"
                                                                    title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            @endcan
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Pagination -->
                                <div class="d-flex justify-content-center">
                                    {{ $categories->links() }}
                                </div>
                            @else
                                <div class="text-center py-4">
                                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No Product Categories Found</h5>
                                    <p class="text-muted">Start by creating your first product category.</p>
                                    @can('inventory.create')
                                        <a href="{{ route('product-categories.create') }}" class="btn btn-primary">
                                            <i class="fas fa-plus mr-1"></i>
                                            Create First Category
                                        </a>
                                    @endcan
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the category <strong id="categoryName"></strong>?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <form id="deleteForm" method="POST" style="display: inline;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function deleteCategory(categoryId, categoryName) {
            document.getElementById('categoryName').textContent = categoryName;
            document.getElementById('deleteForm').action = '/product-categories/' + categoryId;
            $('#deleteModal').modal('show');
        }
    </script>
@endpush
