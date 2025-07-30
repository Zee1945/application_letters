        		<!--start header -->
                <header>
                    <div class="topbar">
                        <nav class="navbar navbar-expand gap-2 align-items-center">
                            <div class="mobile-toggle-menu d-flex"><i class='bx bx-menu'></i>
                            </div>

                              <div class="search-bar d-lg-block d-none" data-bs-toggle="modal" data-bs-target="#SearchModal">
                                 <a href="avascript:;" class="btn d-flex align-items-center"><i class="bx bx-search"></i>Search</a>
                              </div>

                              <div class="top-menu ms-auto">
                                <ul class="navbar-nav align-items-center gap-1">
                                    <li class="nav-item mobile-search-icon d-flex d-lg-none" data-bs-toggle="modal" data-bs-target="#SearchModal">
                                        <a class="nav-link" href="avascript:;"><i class='bx bx-search'></i>
                                        </a>
                                    </li>
                                    <li class="nav-item dropdown dropdown-large">
                                        <a class="nav-link dropdown-toggle dropdown-toggle-nocaret position-relative" href="#" data-bs-toggle="dropdown"><span class="alert-count">7</span>
                                            <i class='bx bx-bell'></i>
                                        </a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a href="javascript:;">
                                                <div class="msg-header">
                                                    <p class="msg-header-title">Notifications</p>
                                                    <p class="msg-header-badge">8 New</p>
                                                </div>
                                            </a>
                                            <div class="header-notifications-list">
                                                <a class="dropdown-item" href="javascript:;">
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-online">
                                                            <img src="{{ asset('assets/images/avatars/avatar-1.png')}}" class="msg-avatar" alt="user avatar">
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="msg-name">Daisy Anderson<span class="msg-time float-end">5 sec
                                                        ago</span></h6>
                                                            <p class="msg-info">The standard chunk of lorem</p>
                                                        </div>
                                                    </div>
                                                </a>
                                                <a class="dropdown-item" href="javascript:;">
                                                    <div class="d-flex align-items-center">
                                                        <div class="user-online">
                                                            <img src="{{ asset('assets/images/avatars/avatar-2.png')}}" class="msg-avatar" alt="user avatar">
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="msg-name">Althea Cabardo <span class="msg-time float-end">14
                                                        sec ago</span></h6>
                                                            <p class="msg-info">Many desktop publishing packages</p>
                                                        </div>
                                                    </div>
                                                </a>
                                                <a class="dropdown-item" href="javascript:;">
                                                    <div class="d-flex align-items-center">
                                                        <div class="notify bg-light-success text-success">
                                                            <img src="{{ asset('assets/images/app/outlook.png')}}" width="25" alt="user avatar">
                                                        </div>
                                                        <div class="flex-grow-1">
                                                            <h6 class="msg-name">Account Created<span class="msg-time float-end">28 min
                                                        ago</span></h6>
                                                            <p class="msg-info">Successfully created new email</p>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                            <a href="javascript:;">
                                                <div class="text-center msg-footer">
                                                    <button class="btn btn-primary w-100">View All Notifications</button>
                                                </div>
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                            <div class="user-box dropdown px-3">
                                <a class="d-flex align-items-center nav-link dropdown-toggle gap-3 dropdown-toggle-nocaret" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <div class="user-img d-flex align-items-center justify-content-center bg-primary text-white rounded-circle" style="width: 40px; height: 40px;">
                                        {{-- <i class="fas fa-user"></i> --}}
                                        <i class="fa-regular fa-user" style="font-size: 1.2rem;"></i>
                                    </div>

                                    <div class="user-info">
                                        <p class="user-name mb-0">{{viewHelper::currentAccess()['name']}}</p>
                                        <p class="designattion mb-0">{{viewHelper::currentAccess()['department']}}</p>
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item d-flex align-items-center" href="javascript:;"><i class="bx bx-user fs-5"></i><span>Profile</span></a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider mb-0"></div>
                                    </li>
                                    <li><a class="dropdown-item d-flex align-items-center" href="{{route('logout')}}"><i class="bx bx-log-out-circle"></i><span>Logout</span></a>
                                    </li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </header>
                <!--end header -->
