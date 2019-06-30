import '../css/app.css';
import '../../node_modules/bootstrap/dist/css/bootstrap.css';

import 'bootstrap';
import $ from 'jquery';
import './landkit';

$(function () {
    $.ajax({
        url: "http://cbr-rates.local",
        context: document.body
    }).done(function() {
        console.log('Hello Webpack Encore');
    });
})
