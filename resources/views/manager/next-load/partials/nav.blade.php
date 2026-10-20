{{-- Section tabs shared by every Next Load page --}}
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('manager.next-load.index') ? 'active' : '' }}" href="{{ route('manager.next-load.index') }}">Overview</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('manager.next-load.products', 'manager.next-load.links.*') ? 'active' : '' }}" href="{{ route('manager.next-load.products') }}">Products &amp; links</a>
    </li>
    <li class="nav-item">
        <a class="nav-link {{ request()->routeIs('manager.next-load.orders.*') ? 'active' : '' }}" href="{{ route('manager.next-load.orders.index') }}">Supplier orders</a>
    </li>
</ul>
