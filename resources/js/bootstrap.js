
import axios from 'axios';
window.axios = axios;
import $ from "jquery";
window.$ = $;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import * as bootstrap from "bootstrap";

window.bootstrap = bootstrap;


import 'perfect-scrollbar';
import 'simplebar';
import 'metismenu';
import 'apexcharts';
import "peity/jquery.peity.min.js";

$(".data-attributes span").peity("donut")


import select2 from "select2";
select2();
