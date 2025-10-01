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

    {{-- tambahkan toastnya disini --}}

    {{-- {{toast disini}} --}}
@if (session('success') || $errors->has('error'))
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
        <div id="liveToast" class="toast align-items-center text-white {{ session('success') ? 'bg-success' : 'bg-danger' }} border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">
                    {{ session('success') ?? $errors->first('error') }}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var toastEl = document.getElementById('liveToast');
            if (toastEl) {
                var toast = new bootstrap.Toast(toastEl, { delay: 3000 });
                toast.show();
            }
        });
    </script>
@endif
    {{-- tambahkan toastnya disini --}}
    @include('layouts.footer')


    @livewireScripts
    <script src="{{ asset('assets/lib/tinymce/js/tinymce/tinymce.min.js') }}"></script>
    @stack('scripts')

</body>

</html>
