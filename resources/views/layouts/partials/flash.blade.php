@if (session('success') || session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if (session('success'))
                toastr.success(@json(session('success')));
            @endif
            @if (session('error'))
                toastr.error(@json(session('error')));
            @endif
        });
    </script>
@endif