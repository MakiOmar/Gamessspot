<!-- Sidebar -->
<div class="bg-light border-right" id="sidebar-wrapper">
    <div class="sidebar-heading gms-bg">Games Spot</div>
    <!-- Dashboard Section with Divider -->
    <div class="list-group list-group-flush">
        <a href="{{ route('manager.dashboard') }}" class="list-group-item list-group-item-action bg-light">
            Dashboard <span class="float-end"><i class="fas fa-tachometer-alt"></i></span>
        </a>
    </div>

    <div class="list-group list-group-flush">
        @if(Auth::user()->roles->contains('name', 'admin') || Auth::user()->roles->contains('name', 'sales'))
        <!-- Games Menu -->
        <a href="#gamesMenu" class="list-group-item list-group-item-action bg-light" data-bs-toggle="collapse">
            Games <span class="float-end"><i class="fas fa-chevron-down"></i></span>
        </a>
        <div class="collapse" id="gamesMenu">
            @if(Auth::user()->roles->contains('name', 'admin'))
                <a href="{{ route('manager.games') }}" class="list-group-item list-group-item-action">Edit/Add Games</a>
            @endif
            <a href="{{ route('manager.games.ps4') }}" class="list-group-item list-group-item-action">PS4 Games</a>
            <a href="{{ route('manager.games.ps5') }}" class="list-group-item list-group-item-action">PS5 Games</a>
        </div>

        <!-- Cards Menu -->
        <a href="#cardsMenu" class="list-group-item list-group-item-action bg-light" data-bs-toggle="collapse">
            Cards <span class="float-end"><i class="fas fa-chevron-down"></i></span>
        </a>
        <div class="collapse" id="cardsMenu">
            <a href="#" class="list-group-item list-group-item-action">Card 1</a>
            <a href="#" class="list-group-item list-group-item-action">Card 2</a>
        </div>
        <a href="{{ route( 'manager.orders' ) }}" class="list-group-item bg-light">Sell log</a>
        @endif
        @if(Auth::guard('admin')->user()->roles->contains(function ($role) {
            return in_array($role->name, ['admin', 'sales', 'accountant', 'account manager']);
        }))
                @if(Auth::guard('admin')->user()->roles->contains(function ($role) {
                    return in_array($role->name, ['admin','account manager']);
                }))
                    <a href="{{ route( 'manager.accounts' ) }}" class="list-group-item bg-light">Accounts</a>
                @endif
                @if( Auth::user()->roles->contains('name', 'admin') )
                <a href="#ordersReports" class="list-group-item list-group-item-action bg-light" data-bs-toggle="collapse">
                    Reports <span class="float-end"><i class="fas fa-chevron-down"></i></span>
                </a>
                <div class="collapse" id="ordersReports">
                    <a href="{{ route( 'manager.orders.needs_return' ) }}" class="list-group-item bg-light">Needs return</a>
                    <a href="{{ route( 'manager.orders.has_problem' ) }}" class="list-group-item bg-light">Reported issues</a>
                    <a href="{{ route( 'manager.orders.solved' ) }}" class="list-group-item bg-light">Solved</a>
                </div>
                <a href="#usersList" class="list-group-item list-group-item-action bg-light" data-bs-toggle="collapse">
                    Users <span class="float-end"><i class="fas fa-chevron-down"></i></span>
                </a>
                <div class="collapse" id="usersList">
                    <a href="{{ route( 'manager.users.index' ) }}" class="list-group-item bg-light">All</a>
                    <a href="{{ route( 'manager.users.sales' ) }}" class="list-group-item bg-light">Sales</a>
                    <a href="{{ route( 'manager.users.accountants' ) }}" class="list-group-item bg-light">Accountants</a>
                    <a href="{{ route( 'manager.users.admins' ) }}" class="list-group-item bg-light">Admins</a>
                </div>
                
                @endif
            @if ( !Auth::user()->roles->contains('name', 'account manager') )
                <a href="{{ route( 'manager.storeProfiles.index' ) }}" class="list-group-item bg-light">Stores Profiles</a>
            @endif
        @endif
    </div>
</div>
