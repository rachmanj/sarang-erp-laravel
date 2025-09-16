<!-- jQuery -->
<script src="{{ asset('adminlte/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ asset('adminlte/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<!-- SweetAlert2 Global Configuration -->
<script src="{{ asset('js/sweetalert2-config.js') }}"></script>
<!-- Toastr -->
<script src="{{ asset('adminlte/plugins/toastr/toastr.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('adminlte/dist/js/adminlte.min.js') }}"></script>
<!-- DataTables -->
<script src="{{ asset('adminlte/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('adminlte/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('adminlte/plugins/select2/js/select2.full.min.js') }}"></script>

<script>
    const DISPLAY_TZ = 'Asia/Singapore';

    function formatTz(dateStr) {
        try {
            return new Date(dateStr + 'T00:00:00Z').toLocaleDateString('en-SG', {
                timeZone: DISPLAY_TZ
            });
        } catch (e) {
            return dateStr;
        }
    }
    // Logout confirmation function
    function confirmLogout() {
        Swal.fire({
            title: 'Are you sure?',
            text: "You will be logged out of the system.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, logout!',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('logout-form').submit();
            }
        });
    }

    // Simple queued PDF banner: checks for a session pdf_url and polls until available
    document.addEventListener('DOMContentLoaded', () => {
        toastr.options = {
            positionClass: 'toast-top-right',
            closeButton: true,
            progressBar: true,
            newestOnTop: true,
            timeOut: 4000,
        };
        @if (session('success'))
            toastr.success(@json(session('success')));
        @endif
        @if (session('error'))
            toastr.error(@json(session('error')));
        @endif
        @if (session('warning'))
            toastr.warning(@json(session('warning')));
        @endif
        @if (session('info'))
            toastr.info(@json(session('info')));
        @endif
        const urlMeta = document.querySelector('meta[name="pdf_url"]');
        const queuedUrl = urlMeta ? urlMeta.getAttribute('content') : null;
        if (queuedUrl) {
            toastr.info('Generating PDF... We\'ll open it when ready.');
            const maxTries = 20;
            let tries = 0;
            const interval = setInterval(async () => {
                tries++;
                try {
                    const res = await fetch(queuedUrl, {
                        method: 'HEAD'
                    });
                    if (res.ok) {
                        clearInterval(interval);
                        toastr.success('PDF is ready. Opening now.');
                        window.open(queuedUrl, '_blank');
                    }
                } catch (e) {}
                if (tries >= maxTries) {
                    clearInterval(interval);
                    toastr.warning('Still generating. Check Downloads page later.');
                }
            }, 2000);
        }
    });
</script>

@yield('scripts')
@stack('scripts')
