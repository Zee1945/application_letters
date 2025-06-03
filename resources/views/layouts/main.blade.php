<!doctype html>
<html lang="en" data-bs-theme="light">
@include('layouts.header')

<body>
    <!--wrapper-->
    <div class="wrapper">
        <!--sidebar wrapper -->
        @include('layouts.navbar')
        <div class="sidebar-wrapper" data-simplebar="true">
            @include('layouts.sidebar')
        </div>
        <!--end sidebar wrapper -->

        <!--start page wrapper -->
        <div class="page-wrapper">
            <div class="page-content container-xxl">
                <div class="">
                    <div class="">
                        @yield('content')
                    </div>
                </div>


            </div><!--end page content -->
        </div>
        <!--end page wrapper -->
        <!--start overlay-->
        <div class="overlay mobile-toggle-icon"></div>
        <!--end overlay-->
        <!--Start Back To Top Button-->
        <a href="javaScript:;" class="back-to-top"><i class='bx bxs-up-arrow-alt'></i></a>
        <!--End Back To Top Button-->

    </div>
    <!--end wrapper-->

    {{-- <footer class="page-footer">
        <p class="mb-0">Copyright © 2025. All right reserved.</p>
    </footer> --}}
    @include('layouts.footer')

    @livewireScripts
</body>

</html>
