<!-- Divider -->
<li class="nav-header">ADMIN</li>

<!-- Users -->
<li class="nav-item">
    <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-users"></i>
        <p>Users</p>
    </a>
</li>

<!-- Roles & Permissions -->
<li
    class="nav-item {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'menu-open' : '' }}">
    <a href="#"
        class="nav-link {{ request()->routeIs('admin.roles.*') || request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-user-shield"></i>
        <p>
            Roles & Permissions
            <i class="right fas fa-angle-left"></i>
        </p>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a href="{{ route('admin.roles.index') }}"
                class="nav-link {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Roles</p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('admin.permissions.index') }}"
                class="nav-link {{ request()->routeIs('admin.permissions.*') ? 'active' : '' }}">
                <i class="far fa-circle nav-icon"></i>
                <p>Permissions</p>
            </a>
        </li>
    </ul>
</li>

<!-- ERP Parameters -->
<li class="nav-item">
    <a href="{{ route('erp-parameters.index') }}"
        class="nav-link {{ request()->routeIs('erp-parameters.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-cogs"></i>
        <p>ERP Parameters</p>
    </a>
</li>

<!-- Audit Logs -->
<li class="nav-item">
    <a href="{{ route('audit-logs.index') }}" 
       class="nav-link {{ request()->routeIs('audit-logs.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-clipboard-list"></i>
        <p>
            Audit Logs
            @php
                $todayCount = \App\Models\AuditLog::whereDate('created_at', today())->count();
            @endphp
            @if($todayCount > 0)
                <span class="badge badge-info right" id="audit-logs-count">
                    {{ $todayCount }}
                </span>
            @endif
        </p>
    </a>
</li>

<!-- Activity Dashboard -->
<li class="nav-item">
    <a href="{{ route('activity-dashboard.index') }}" 
       class="nav-link {{ request()->routeIs('activity-dashboard.*') ? 'active' : '' }}">
        <i class="nav-icon fas fa-chart-line"></i>
        <p>Activity Dashboard</p>
    </a>
</li>
