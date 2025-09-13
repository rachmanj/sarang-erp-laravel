@can('reports.view')
    @php $reportsActive = request()->is('reports/*'); @endphp
    <li class="nav-header">REPORTS</li>
    <li class="nav-item {{ $reportsActive ? 'menu-is-opening menu-open' : '' }}">
        <a href="#" class="nav-link {{ $reportsActive ? 'active' : '' }}">
            <i class="nav-icon fas fa-chart-bar"></i>
            <p>Reports <i class="right fas fa-angle-left"></i></p>
        </a>
        <ul class="nav nav-treeview">
            <li class="nav-item">
                <a href="{{ route('reports.trial-balance') }}"
                    class="nav-link {{ request()->routeIs('reports.trial-balance') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Trial Balance</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.gl-detail') }}"
                    class="nav-link {{ request()->routeIs('reports.gl-detail') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>GL Detail</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.ar-aging') }}"
                    class="nav-link {{ request()->routeIs('reports.ar-aging') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>AR Aging</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.ap-aging') }}"
                    class="nav-link {{ request()->routeIs('reports.ap-aging') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>AP Aging</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.cash-ledger') }}"
                    class="nav-link {{ request()->routeIs('reports.cash-ledger') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Cash Ledger</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.ar-balances') }}"
                    class="nav-link {{ request()->routeIs('reports.ar-balances') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>AR Party Balances</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.ap-balances') }}"
                    class="nav-link {{ request()->routeIs('reports.ap-balances') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>AP Party Balances</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('downloads.index') }}"
                    class="nav-link {{ request()->routeIs('downloads.*') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Downloads</p>
                </a>
            </li>
            <li class="nav-item">
                <a href="{{ route('reports.withholding-recap') }}"
                    class="nav-link {{ request()->routeIs('reports.withholding-recap') ? 'active' : '' }}">
                    <i class="far fa-circle nav-icon"></i>
                    <p>Withholding Recap</p>
                </a>
            </li>

            <!-- Asset Reports -->
            @canany(['assets.view', 'assets.disposal.view', 'assets.movement.view'])
                <li class="nav-header">ASSET REPORTS</li>
                <li class="nav-item">
                    <a href="{{ route('reports.assets.index') }}"
                        class="nav-link {{ request()->routeIs('reports.assets.*') ? 'active' : '' }}">
                        <i class="far fa-circle nav-icon"></i>
                        <p>Asset Reports</p>
                    </a>
                </li>
            @endcanany
        </ul>
    </li>
@endcan
