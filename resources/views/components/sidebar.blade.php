<div class="w-64 border-r border-gray-200 flex flex-col">
    @php($user = auth()->user())

    <!-- Logo -->
    <div class="h-16 flex items-center px-6 border-b border-gray-200">
        <span class="text-sm font-semibold text-gray-700 tracking-wide">
            Mini ERP
        </span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-4 py-6 space-y-1 text-sm">

        @if($user?->hasPermission('dashboard.view'))
            <x-sidebar-link href="/dashboard">
                Dashboard
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('products.view'))
            <x-sidebar-link href="/products">
                Products
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('orders.view'))
            <x-sidebar-link href="/orders">
                Orders
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('inventory.view'))
            <x-sidebar-link href="/inventory">
                Inventory
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('customers.view'))
            <x-sidebar-link href="/customers">
                Customers
            </x-sidebar-link>
        @endif

        @if($user?->isAdmin())
            <x-sidebar-link href="/suppliers">
                Suppliers
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('inventory.view'))
            <x-sidebar-link href="/stock-movements">
                Stock Movements
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('purchase_orders.view'))
            <x-sidebar-link href="/purchase-orders">
                Purchase Orders
            </x-sidebar-link>
        @endif

        @if($user?->isAdmin() || $user?->isWarehouse())
            <x-sidebar-link href="/reorder-suggestions">
                Reorder Suggestions
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('invoices.view'))
            <x-sidebar-link href="/invoices">
                Invoices
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('finance.view'))
            <x-sidebar-link href="/finance">
                Finance
            </x-sidebar-link>
        @endif

        @if($user?->hasPermission('inventory.transfer'))
            <x-sidebar-link href="/transfers">
                Transfer
            </x-sidebar-link>
        @endif
    </nav>

</div>
