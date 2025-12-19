<div class="btn-group" role="group">
    <a href="{{ route('unit-of-measures.show', $unit->id) }}"
        class="btn btn-info btn-sm" title="View Details">
        <i class="fas fa-eye"></i>
    </a>
    @can('update_unit_of_measure')
        <a href="{{ route('unit-of-measures.edit', $unit->id) }}"
            class="btn btn-warning btn-sm" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
    @endcan
    @can('delete_unit_of_measure')
        <button type="button" class="btn btn-danger btn-sm"
            onclick="deleteUnit({{ $unit->id }}, '{{ addslashes($unit->name) }}')"
            title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    @endcan
</div>
