<div class="sidebar-header d-flex justify-content-center">
    <div>
        <img src="{{asset('assets/images/logo-icon1.png')}}" class="logo-icon" alt="logo icon">
    </div>
    <div>
        <h4 class="logo-text">SIRAPEL</h4>
    </div>
    <div class="mobile-toggle-icon ms-auto"><i class='bx bx-x'></i>
    </div>
 </div>
<!--navigation-->
<ul class="metismenu" id="menu">
    <li>
        <a href="{{route('dashboard')}}" class="">
            <div class="parent-icon"><i class='bx bx-home-alt'></i>
            </div>
            <div class="menu-title">Dashboard</div>
        </a>
    </li>
    <li>
        <a href="{{route('applications.index')}}" >
            <div class="parent-icon"><i class="bx bx-category"></i>
            </div>
            <div class="menu-title">Surat</div>
        </a>

    </li>
    <li>
        <a href="{{route('report.index')}}" >
            <div class="parent-icon"><i class="bx bx-category"></i>
            </div>
            <div class="menu-title">LPJ</div>
        </a>

    </li>
    {{-- <li>
        <a href="javascript:;" class="has-arrow">
            <div class="parent-icon"><i class='bx bx-lock'></i>
            </div>
            <div class="menu-title">Admin Akses</div>
        </a>
        <ul>
            <li> <a href="ecommerce-products.html"><i class='bx bx-radio-circle'></i>Products</a>
            </li>
            <li> <a href="ecommerce-products-details.html"><i class='bx bx-radio-circle'></i>Product Details</a>
            </li>
            <li> <a href="ecommerce-add-new-products.html"><i class='bx bx-radio-circle'></i>Add New Products</a>
            </li>
            <li> <a href="ecommerce-orders.html"><i class='bx bx-radio-circle'></i>Orders</a>
            </li>
        </ul>
    </li>
    <li>
        <a class="has-arrow" href="javascript:;">
            <div class="parent-icon"><i class='bx bx-cog'></i>
            </div>
            <div class="menu-title">Settings</div>
        </a>
        <ul>
            <li> <a href="component-alerts.html"><i class='bx bx-radio-circle'></i>Alerts</a>
            </li>
            <li> <a href="component-accordions.html"><i class='bx bx-radio-circle'></i>Accordions</a>
            </li>
            <li> <a href="component-badges.html"><i class='bx bx-radio-circle'></i>Badges</a>
            </li>
            <li> <a href="component-buttons.html"><i class='bx bx-radio-circle'></i>Buttons</a>
            </li>

        </ul>
    </li> --}}
</ul>
<!--end navigation-->
