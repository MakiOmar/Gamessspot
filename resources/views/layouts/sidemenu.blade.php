<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark"> <!--begin::Sidebar Brand-->
    <div class="sidebar-brand"> <!--begin::Brand Link--> <a href="./index.html" class="brand-link"> <!--begin::Brand Image--> <img src="{{ asset('assets/img/AdminLTELogo.png') }}" alt="AdminLTE Logo" class="brand-image opacity-75 shadow"> <!--end::Brand Image--> <!--begin::Brand Text--> <span class="brand-text fw-light">AdminLTE 4</span> <!--end::Brand Text--> </a> <!--end::Brand Link--> </div> <!--end::Sidebar Brand--> <!--begin::Sidebar Wrapper-->
    <div class="sidebar-wrapper">
        <nav class="mt-2"> <!--begin::Sidebar Menu-->
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-item">
                    <a href="{{ route('manager.dashboard') }}" class="nav-link bg-light">
                        Dashboard <span class="float-end"><i class="nav-icon bi bi-palette"></i></span>
                    </a>
                </li>
                @if(Auth::guard('admin')->user()->roles->contains(function ($role) {
                    return in_array($role->name, ['admin', 'sales', 'account manager']);
                }))
                <li class="nav-item"> <a href="#" class="nav-link"> <i class="nav-icon bi bi-speedometer"></i>
                        <p>
                            Games
                            <i class="nav-arrow bi bi-chevron-right"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @if(Auth::user()->roles->contains('name', 'admin'))
                        <li class="nav-item">
                            <a href="{{ route('manager.games') }}" class="nav-link"><i class="nav-icon bi bi-circle"></i> <p>Edit/Add Games</p></a>
                        </li>                    
                        @endif

                        <li class="nav-item">
                            <a href="{{ route('manager.games.ps4') }}" class="nav-link"><i class="nav-icon bi bi-circle"></i>
                                <p>PS4 Games</p>
                            </a>
                        </li>
                        @if(Auth::user()->roles->contains('name', 'admin'))
                        <li class="nav-item">
                            <a href="{{ route('manager.games.ps4.woocommerce') }}" class="nav-link"><i class="nav-icon bi bi-circle"></i>
                                <p>WooCommerce eligible ps4</p>
                            </a>
                        </li>
                        @endif
                        <li class="nav-item">
                            <a href="{{ route('manager.games.ps5') }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                <p>PS5 Games</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                @if(Auth::guard('admin')->user()->roles->contains(function ($role) {
                    return in_array($role->name, ['admin', 'sales', 'account manager']);
                }))
                    @if(Auth::guard('admin')->user()->roles->contains(function ($role) {
                        return in_array($role->name, ['admin', 'sales']);
                    }))
                    <li class="nav-item"> <a href="#" class="nav-link"> <i class="nav-icon bi bi-box-seam-fill"></i>
                            <p>
                                Gift Cards
                                <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if ( Auth::user()->roles->contains('name', 'admin') )
                            <li class="nav-item">
                                <a href="{{ route( 'card-categories.index' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Categories</p>
                                </a> </li>
                            <li class="nav-item">
                                <a href="{{ route( 'cards.index' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Gift cards list/add</p>
                                </a> </li>
                            @endif
                            <li class="nav-item">
                                <a href="{{ route( 'manager.sell-cards' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Sell gift cards</p>
                                </a> </li>
                        </ul>
                    </li>
                    @endif
                    <li class="nav-item">
                        <a href="{{ route( 'manager.orders' ) }}" class="nav-link">
                            <i class="nav-icon bi bi-table"></i>
                            <p> Sell log </p>
                        </a>
                    </li>
                @endif
                @if(Auth::guard('admin')->user()->roles->contains(function ($role) {
                    return in_array($role->name, ['admin','account manager']);
                }))
                <li class="nav-item">
                    <a href="{{ route( 'manager.accounts' ) }}" class="nav-link">
                        <i class="nav-icon bi bi-ui-checks-grid"></i>
                        <p>Accounts</p>
                    </a>
                </li>
                @endif
                @php
                    // Get report roles dynamically from database
                    $reportRoleNames = ['admin', 'accountant'];
                    $reportRoles = array_filter($reportRoleNames, function($name) {
                        return \App\Models\Role::where('name', $name)->exists();
                    });
                    $userHasReportRole = Auth::user()->roles->contains(function($role) use ($reportRoles) {
                        return in_array($role->name, $reportRoles);
                    });
                @endphp
                @if($userHasReportRole)
                    <li class="nav-item"> <a href="#" class="nav-link"> <i class="nav-icon bi bi-clipboard-fill"></i>
                            <p>
                                Reports <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route( 'manager.orders.needs_return' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Needs return</p>
                                </a> </li>
                            <li class="nav-item">
                                <a href="{{ route( 'manager.orders.has_problem' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p> Reported issues</p>
                                </a> </li>
                            <li class="nav-item">
                                <a href="{{ route( 'manager.orders.solved' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Solved</p>
                                </a> </li>
                        </ul>
                    </li>
                    
                    <li class="nav-item"> <a href="#" class="nav-link"> <i class="nav-icon bi bi-clipboard-fill"></i>
                            <p>
                                Users <i class="nav-arrow bi bi-chevron-right"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="{{ route( 'manager.users.index' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>All</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route( 'manager.users.sales' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p> Sales</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route( 'manager.users.acc.managers' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Account Managers</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route( 'manager.users.accountants' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Accountants</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route( 'manager.users.admins' ) }}" class="nav-link"> <i class="nav-icon bi bi-circle"></i>
                                    <p>Admins</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route( 'manager.uniqueBuyers' ) }}" class="nav-link">
                            <i class="bi bi-people"></i>
                            <p>Customers</p>
                        </a>
                    </li>
                @endif
                @if ( !Auth::user()->roles->contains('name', 'account manager') && !Auth::user()->roles->contains('name', 'sales') )
                <li class="nav-item">
                    <a href="{{ route( 'manager.storeProfiles.index' ) }}" class="nav-link">
                        <p>Stores Profiles <i class="nav-icon bi bi-palette"></p></i>
                    </a>
                </li>
                @endif
            </ul> <!--end::Sidebar Menu-->
        </nav>
    </div> <!--end::Sidebar Wrapper-->
</aside> <!--end::Sidebar--> <!--begin::App Main-->