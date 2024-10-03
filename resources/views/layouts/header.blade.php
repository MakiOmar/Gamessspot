<nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
        <a class="btn btn-primary" id="menu-toggle"><i class="fa-solid fa-bars"></i></a>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('manager.dashboard') }}">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="{{ route('manager.logout') }}" 
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<form id="logout-form" action="{{ route('manager.logout') }}" method="POST" style="display: none;">
    @csrf
</form>
