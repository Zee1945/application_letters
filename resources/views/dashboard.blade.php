

@extends('layouts.main')

@section('content')
<div class="row g-2">
    <div class="col-12 col-xl-4 d-flex">
        <div class="card rounded-4 mb-0 w-100">
            <div class="card-body">
                <div class="hstack align-items-center gap-3">
                    <div
                        class="mb-0 widgets-icons bg-light-success text-success rounded-ci d-flex align-items-center justify-content-center">
                        <i class='bx bx-printer'></i>
                    </div>
                    <hr class="vr">
                    <div class="">
                        <h4 class="mb-0 d-flex align-items-center gap-2">$84,256 <span
                                class="dash-lable d-flex align-items-center gap-1 rounded mb-0 bg-light-danger text-danger bg-opacity-10"><i
                                    class='bx bx-up-arrow-alt'></i>8.6%</span></h4>
                        <p class="mb-0">Total Income</p>
                    </div>
                </div>

                <div id="chart1"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-2 d-flex">
        <div class="card rounded-4 mb-0 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-0">
                    <div class="">
                        <h6 class="mb-0">Active Users</h6>
                    </div>
                    <div class="dropdown">
                        <a href="javascript:;"
                            class="dropdown-toggle-nocaret more-options dropdown-toggle"
                            data-bs-toggle="dropdown">
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:;">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:;">Another action</a>
                            </li>
                            <li><a class="dropdown-item" href="javascript:;">Something else
                                    here</a></li>
                        </ul>
                    </div>
                </div>
                <div class="chart-container2">
                    <div id="chart2"></div>
                </div>
                <div class="text-center">
                    <div class="">
                        <h4 class="mb-1">42.5K</h4>
                    </div>
                    <p class="mb-0"><span class="text-success">+37.5K</span> from last month
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-4 d-flex">
        <div class="card rounded-4 mb-0 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-3">
                    <div class="">
                        <h6 class="mb-0">Sales & Views</h6>
                    </div>
                    <div class="dropdown">
                        <a href="javascript:;"
                            class="dropdown-toggle-nocaret more-options dropdown-toggle"
                            data-bs-toggle="dropdown">
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:;">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:;">Another action</a>
                            </li>
                            <li><a class="dropdown-item" href="javascript:;">Something else
                                    here</a></li>
                        </ul>
                    </div>
                </div>
                <div id="chart3"></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-xl-2 d-flex">
        <div class="card rounded-4 mb-0 overflow-hidden mb-0 w-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between mb-1">
                    <div class="">
                        <h4 class="mb-0">87.4K</h4>
                        <p class="mb-0">Total Clicks</p>
                    </div>
                    <div class="dropdown">
                        <a href="javascript:;"
                            class="dropdown-toggle-nocaret more-options dropdown-toggle"
                            data-bs-toggle="dropdown">
                            <i class='bx bx-dots-vertical-rounded'></i>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="javascript:;">Action</a></li>
                            <li><a class="dropdown-item" href="javascript:;">Another action</a>
                            </li>
                            <li><a class="dropdown-item" href="javascript:;">Something else
                                    here</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="chart-container">
                <div id="chart4"></div>
            </div>
        </div>
    </div>
</div><!--end row-->

@endsection
