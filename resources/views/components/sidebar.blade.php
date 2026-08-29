@php
    $navClass = function (bool $active): string {
        return 'rounded-md px-3 py-2 text-sm font-medium '.($active
            ? 'bg-canvas text-ink'
            : 'text-muted hover:bg-canvas hover:text-ink');
    };

    $showDashboard = auth()->user()?->can('viewDashboard') === true;
    $showPos = auth()->user()?->can('accessPos') === true && Route::has('pos');
    $showTransactions = auth()->user()?->can('viewOwnTransactions') === true && Route::has('transactions.index');
    $showUsers = auth()->user()?->can('manageUsers') === true && Route::has('users.index');
    $showSettings = auth()->user()?->can('manageStoreSettings') === true && Route::has('settings.edit');
    $showAuditLog = auth()->user()?->can('viewAuditLog') === true && Route::has('audit-log.index');
    $showCategories = auth()->user()?->can('manageCategories') === true && Route::has('categories.index');
    $showProducts = auth()->user()?->can('manageProducts') === true && Route::has('products.index');
    $showInventory = auth()->user()?->can('viewInventory') === true && Route::has('inventory.index');
    $showStockMovements = auth()->user()?->can('viewStockMovements') === true && Route::has('inventory.movements.index');
    $showReports = auth()->user()?->can('viewReports') === true
        && (Route::has('reports.sales') || Route::has('reports.product-sales'));
    $showMain = $showDashboard || $showPos || $showTransactions;
    $showManagement = $showCategories || $showProducts || $showInventory || $showStockMovements;
    $showAdministration = $showUsers || $showSettings || $showAuditLog;
    $showReporting = $showReports;
@endphp

<nav class="flex flex-1 flex-col gap-1 p-3" aria-label="Application">
    @if ($showMain)
        <p class="px-3 pt-1 pb-1 text-[11px] font-medium uppercase tracking-wide text-muted">Main</p>

        @can('viewDashboard')
            <a href="{{ route('dashboard') }}" class="{{ $navClass(request()->routeIs('dashboard')) }}">
                Dashboard
            </a>
        @endcan

        @can('accessPos')
            @if (Route::has('pos'))
                <a href="{{ route('pos') }}" class="{{ $navClass(request()->routeIs('pos')) }}">
                    POS
                </a>
            @endif
        @endcan

        @can('viewOwnTransactions')
            @if (Route::has('transactions.index'))
                <a href="{{ route('transactions.index') }}" class="{{ $navClass(request()->routeIs('transactions.*')) }}">
                    Transactions
                </a>
            @endif
        @endcan
    @endif

    @if ($showManagement)
        <p class="px-3 pt-3 pb-1 text-[11px] font-medium uppercase tracking-wide text-muted">Management</p>

        @can('manageCategories')
            <a href="{{ route('categories.index') }}" class="{{ $navClass(request()->routeIs('categories.*')) }}">
                Categories
            </a>
        @endcan

        @can('manageProducts')
            <a href="{{ route('products.index') }}" class="{{ $navClass(request()->routeIs('products.*')) }}">
                Products
            </a>
        @endcan

        @can('viewInventory')
            @if (Route::has('inventory.index'))
                <a href="{{ route('inventory.index') }}" class="{{ $navClass(request()->routeIs('inventory.index')) }}">
                    Inventory
                </a>
            @endif
        @endcan

        @can('viewStockMovements')
            @if (Route::has('inventory.movements.index'))
                <a href="{{ route('inventory.movements.index') }}" class="{{ $navClass(request()->routeIs('inventory.movements.*')) }}">
                    Stock movements
                </a>
            @endif
        @endcan
    @endif

    @if ($showReporting)
        <p class="px-3 pt-3 pb-1 text-[11px] font-medium uppercase tracking-wide text-muted">Reports</p>

        @can('viewReports')
            @if (Route::has('reports.sales'))
                <a href="{{ route('reports.sales') }}" class="{{ $navClass(request()->routeIs('reports.sales')) }}">
                    Sales report
                </a>
            @endif

            @if (Route::has('reports.product-sales'))
                <a href="{{ route('reports.product-sales') }}" class="{{ $navClass(request()->routeIs('reports.product-sales')) }}">
                    Product sales
                </a>
            @endif
        @endcan
    @endif

    @if ($showAdministration)
        <p class="px-3 pt-3 pb-1 text-[11px] font-medium uppercase tracking-wide text-muted">Administration</p>

        @can('manageUsers')
            <a href="{{ route('users.index') }}" class="{{ $navClass(request()->routeIs('users.*')) }}">
                Users
            </a>
        @endcan

        @can('manageStoreSettings')
            <a href="{{ route('settings.edit') }}" class="{{ $navClass(request()->routeIs('settings.*')) }}">
                Settings
            </a>
        @endcan

        @can('viewAuditLog')
            @if (Route::has('audit-log.index'))
                <a href="{{ route('audit-log.index') }}" class="{{ $navClass(request()->routeIs('audit-log.*')) }}">
                    Activity log
                </a>
            @endif
        @endcan
    @endif
</nav>
