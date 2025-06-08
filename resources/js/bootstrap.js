
import axios from 'axios';
window.axios = axios;
import $ from "jquery";
window.$ = $;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import * as bootstrap from "bootstrap";

window.bootstrap = bootstrap;

import Stepper from './bs-stepper/js/index'

$(document).ready(function () {
    window.stepper1 = new Stepper(document.querySelector('#stepper1'), {
        animation: true
    });

    // Ambil step dari query string (?step=2)
    const params = new URLSearchParams(window.location.search);
    const stepParam = params.get('step');
    console.log('isi stepParam',stepParam);

    if (stepParam && !isNaN(stepParam)) {
        // bs-stepper pakai index mulai dari 0
        window.stepper1.to(parseInt(stepParam));
    }

    $('#stepper1')[0].addEventListener('show.bs-stepper', function (event) {
        console.log('Step akan berubah ke:', event.detail.indexStep);
    });
});

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

