<!DOCTYPE html>

<html lang="en">

<head>

    @include('layouts.partials.head')

    <style>
        /* Fixed navbar adjustments */
        body {
            padding-top: 57px;
            /* Height of the navbar */
        }

        .main-header.navbar.fixed-top {
            z-index: 1030;
        }

        /* Ensure sidebar stays below fixed navbar */
        .main-sidebar {
            top: 57px;
        }

        /* Adjust content wrapper for fixed navbar */
        .content-wrapper {
            margin-top: 0;
        }
    </style>

    @stack('css')

</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <!-- Navbar -->
        @include('layouts.partials.navbar')
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        @include('layouts.partials.sidebar')
        <!-- End Main Sidebar Container -->

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">

            <!-- start breadcrumb -->
            @include('layouts.partials.breadcrumb')
            <!-- end breadcrumb -->

            <!-- Main content -->
            <div class="content">

                <div class="container-fluid">

                    @yield('content')

                </div><!-- /.container-fluid -->

            </div> <!-- /.content -->


        </div> <!-- /.content-wrapper -->


        <!-- start footer -->
        @include('layouts.partials.footer')
        <!-- /.end footer -->

    </div>
    <!-- ./wrapper -->

    <!-- REQUIRED SCRIPTS -->

    @include('layouts.partials.scripts')

</body>

</html>
