<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@if (session('pdf_url'))
    <meta name="pdf_url" content="{{ session('pdf_url') }}">
@endif
<title>Sarang-ERP</title>

<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="{{ asset('adminlte/fontgoogle.css') }}">
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="{{ asset('adminlte/plugins/fontawesome-free/css/all.min.css') }}">
<!-- SweetAlert2 -->
<link rel="stylesheet" href="{{ asset('adminlte/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
<!-- Toastr -->
<link rel="stylesheet" href="{{ asset('adminlte/plugins/toastr/toastr.min.css') }}">
<!-- DataTables -->
<link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
<!-- Select2 -->
<link rel="stylesheet" href="{{ asset('adminlte/plugins/select2/css/select2.min.css') }}">
<link rel="stylesheet" href="{{ asset('adminlte/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
<!-- Theme style -->
<link rel="stylesheet" href="{{ asset('adminlte/dist/css/adminlte.min.css') }}">
<!-- Menu Search -->
<link rel="stylesheet" href="{{ asset('css/menu-search.css') }}">

@yield('styles')
@stack('styles')

<style>
    /* HELP launcher in main navbar — high visibility */
    .help-nav-launch {
        padding-left: 0.5rem !important;
        padding-right: 0.5rem !important;
    }

    .help-nav-icon-wrap {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background: linear-gradient(160deg, #ffc107 0%, #ff9800 55%, #f57c00 100%);
        color: #1a1a1a;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        font-size: 1.28rem;
        line-height: 1;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    .help-nav-launch:hover .help-nav-icon-wrap {
        transform: scale(1.08);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.4);
        color: #000;
    }

    /* Global page title alignment with content */
    .content-header {
        padding-left: 27.5px;
        padding-right: 7.5px;
    }

    .content-header .col-sm-6:first-child {
        padding-left: 0;
    }

    /* Enhanced User Dropdown Menu */
    .user-menu .dropdown-menu {
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border-radius: 0.5rem;
        min-width: 280px;
    }

    .user-menu .user-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        padding: 1.5rem 1rem;
        border-radius: 0.5rem 0.5rem 0 0;
    }

    .user-menu .user-avatar {
        margin-bottom: 0.5rem;
    }

    .user-menu .user-body {
        padding: 1rem;
        background: #fff;
        border-radius: 0 0 0.5rem 0.5rem;
    }

    .user-menu .btn-link {
        text-decoration: none;
        padding: 0.5rem;
        border-radius: 0.25rem;
        transition: all 0.2s ease;
    }

    .user-menu .btn-link:hover {
        background-color: #f8f9fa;
        text-decoration: none;
    }

    .user-menu .dropdown-toggle {
        display: flex;
        align-items: center;
        padding: 0.5rem 1rem;
        border-radius: 0.25rem;
        transition: all 0.2s ease;
    }

    .user-menu .dropdown-toggle:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .user-menu .dropdown-toggle .fas {
        transition: transform 0.2s ease;
    }

    .user-menu.show .dropdown-toggle .fa-chevron-down {
        transform: rotate(180deg);
    }
</style>
