<div class="btn-group">
    <a href="{{ route('business_partners.show', $businessPartner) }}" class="btn btn-sm btn-info" title="View">
        <i class="fas fa-eye"></i>
    </a>
    @can('business_partners.manage')
        <a href="{{ route('business_partners.edit', $businessPartner) }}" class="btn btn-sm btn-warning" title="Edit">
            <i class="fas fa-edit"></i>
        </a>
        <button type="button" class="btn btn-sm btn-danger delete-partner" data-id="{{ $businessPartner->id }}"
            data-name="{{ $businessPartner->name }}" title="Delete">
            <i class="fas fa-trash"></i>
        </button>
    @endcan
</div>

@once
    @push('scripts')
        <script>
            $(function() {
                // Delete confirmation with SweetAlert2
                $('.delete-partner').on('click', function() {
                    const id = $(this).data('id');
                    const name = $(this).data('name');

                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to delete business partner "${name}"?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Create and submit form
                            const form = $('<form>', {
                                'method': 'POST',
                                'action': `/business_partners/${id}`
                            });

                            form.append('@csrf');
                            form.append('@method('DELETE')');
                            form.appendTo('body').submit();
                        }
                    });
                });
            });
        </script>
    @endpush
@endonce
