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

        <!-- Games Menu -->
        <a href="#gamesMenu" class="list-group-item list-group-item-action bg-light" data-bs-toggle="collapse">
            Games <span class="float-end"><i class="fas fa-chevron-down"></i></span>
        </a>
        <div class="collapse" id="gamesMenu">
            <a href="{{ route('manager.games') }}" class="list-group-item list-group-item-action">Edit/Add Games</a>
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
        <a href="{{ route( 'manager.accounts' ) }}" class="list-group-item bg-light">Accounts</a>
        <a href="{{ route( 'manager.orders' ) }}" class="list-group-item bg-light">Sell log</a>
        <a href="{{ route( 'manager.users.index' ) }}" class="list-group-item bg-light">Employees</a>
    </div>
</div>
