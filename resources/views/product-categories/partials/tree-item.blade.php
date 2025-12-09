@php
    $children = $categories->where('parent_id', $category->id);
@endphp

<div class="category-tree-item mb-2" style="margin-left: {{ $level * 30 }}px;">
    <div class="card card-outline card-{{ $level == 0 ? 'primary' : ($level == 1 ? 'info' : 'secondary') }}">
        <div class="card-body p-2">
            <div class="d-flex justify-content-between align-items-center">
                <div class="flex-grow-1">
                    <strong>
                        @if ($level > 0)
                            <i class="fas fa-arrow-right text-muted mr-1"></i>
                        @endif
                        {{ $category->name }}
                    </strong>
                    <span class="badge badge-info ml-2">{{ $category->code }}</span>
                    @if (!$category->is_active)
                        <span class="badge badge-secondary ml-1">Inactive</span>
                    @endif
                    @if ($category->parent)
                        <br><small class="text-muted">Parent: {{ $category->parent->name }}</small>
                    @else
                        <span class="badge badge-success ml-1">Root</span>
                    @endif
                    <br>
                    <small class="text-muted">
                        Items: {{ $category->items()->count() }}
                        @if ($children->count() > 0)
                            | Subcategories: {{ $children->count() }}
                        @endif
                    </small>
                </div>
                <div class="btn-group" role="group">
                    <a href="{{ route('product-categories.show', $category->id) }}" 
                       class="btn btn-sm btn-info" title="View Details">
                        <i class="fas fa-eye"></i>
                    </a>
                    @can('inventory.update')
                        <a href="{{ route('product-categories.edit', $category->id) }}" 
                           class="btn btn-sm btn-warning" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    @endcan
                    @can('inventory.delete')
                        <button type="button" class="btn btn-sm btn-danger"
                            onclick="deleteCategory({{ $category->id }}, '{{ $category->name }}')"
                            title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>

@foreach ($children as $child)
    @include('product-categories.partials.tree-item', ['category' => $child, 'level' => $level + 1, 'categories' => $categories])
@endforeach

