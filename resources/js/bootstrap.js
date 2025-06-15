
import axios from 'axios';
window.axios = axios;
import $ from "jquery";
window.$ = $;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import * as bootstrap from "bootstrap";

window.bootstrap = bootstrap;

import Stepper from './bs-stepper/js/index'


window.initStepperFromQuery = () => {
    const el = document.querySelector('#stepper1');
    if (!el) return; // Jangan lanjut jika elemen tidak ada

    // window.stepper1 = new Stepper(el, { animation: true });

}

// Untuk Livewire v3:
// document.addEventListener('livewire:navigated', initStepperFromQuery);
// Untuk Livewire v2 (jika perlu):
// document.addEventListener('livewire:update', initStepperFromQuery);
// Untuk pertama kali load:
// document.addEventListener('DOMContentLoaded', initStepperFromQuery);

//     $(document).ready(function () {
//     window.stepper1 = new Stepper(document.querySelector('#stepper1'), {
//         animation: true
//     });
//     window.stepper1.to(query params step);
// });

// Ambil nilai step dari query string (?step=2)
import 'simplebar';
import 'metismenu';
import 'apexcharts';
// import "peity/jquery.peity";

// $(".data-attributes span").peity("donut")


import select2 from "select2";
select2();

$('.select2').select2();
$('.select2-multiple').select2({
    placeholder: "Select an option",
    allowClear: true
});

