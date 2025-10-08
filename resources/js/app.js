require('./bootstrap');

// for charts
import Chart from 'chart.js/auto';
window.Chart = Chart;

// jQuery + DataTables
import $ from 'jquery';
window.$ = window.jQuery = $;

import 'datatables.net';
import 'datatables.net-responsive';
import 'datatables.net-dt/css/dataTables.dataTables.css';
