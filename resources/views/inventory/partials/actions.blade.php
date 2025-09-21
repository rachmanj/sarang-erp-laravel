<div class="btn-group" role="group">
    <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-sm btn-info" title="View">
        <i class="fas fa-eye"></i>
    </a>
    <a href="{{ route('inventory.edit', $item->id) }}" class="btn btn-sm btn-primary" title="Edit">
        <i class="fas fa-edit"></i>
    </a>
    <button class="btn btn-sm btn-warning btn-adjust-stock" data-item-id="{{ $item->id }}"
        data-item-name="{{ $item->name }}" title="Adjust Stock">
        <i class="fas fa-adjust"></i>
    </button>
    <button class="btn btn-sm btn-info btn-transfer-stock" data-item-id="{{ $item->id }}"
        data-item-name="{{ $item->name }}" title="Transfer Stock">
        <i class="fas fa-exchange-alt"></i>
    </button>
    <a href="{{ route('inventory-items.units.index', $item->id) }}" class="btn btn-sm btn-secondary"
        title="Manage Units">
        <i class="fas fa-cubes"></i>
    </a>
    @if ($item->transactions()->count() == 0)
        <button class="btn btn-sm btn-danger btn-delete-item" data-item-id="{{ $item->id }}"
            data-item-name="{{ $item->name }}" title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    @endif
</div>
