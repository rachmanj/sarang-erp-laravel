<!-- Navbar -->
<nav class="main-header navbar navbar-expand-md navbar-light navbar-dark fixed-top py-1">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">

        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown user-menu">
            <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                <i class="fas fa-user-circle mr-1"></i>
                <span class="d-none d-md-inline">
                    {{ Auth::user()->name }}
                    @if (Auth::user()->department_location_code)
                        <small class="text-light">({{ Auth::user()->department_location_code }})</small>
                    @endif
                </span>
                <i class="fas fa-chevron-down ml-1"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <!-- User Header -->
                <li class="user-header bg-primary">
                    <div class="text-center">
                        <div class="user-avatar mb-2">
                            <i class="fas fa-user-circle fa-3x text-white-50"></i>
                        </div>
                        <h6 class="text-white mb-1">{{ Auth::user()->name }}</h6>
                        @if (Auth::user()->department_location_code)
                            <small class="text-white-50">{{ Auth::user()->department_location_code }}</small><br>
                        @endif
                        <small class="text-white-50">{{ Auth::user()->email }}</small>
                    </div>
                </li>

                <!-- User Menu Items -->
                <li class="user-body">
                    <div class="row">
                        <div class="col-6 text-center">
                            <a href="{{ route('profile.change-password') }}" class="btn btn-link btn-sm">
                                <i class="fas fa-key text-primary"></i><br>
                                <small>Change Password</small>
                            </a>
                        </div>
                        <div class="col-6 text-center">
                            <a href="#" class="btn btn-link btn-sm" onclick="confirmLogout()">
                                <i class="fas fa-sign-out-alt text-danger"></i><br>
                                <small>Sign Out</small>
                            </a>
                        </div>
                    </div>
                </li>

                <!-- Hidden Logout Form -->
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </ul>
        </li>

    </ul>
</nav>
<!-- /.navbar -->
