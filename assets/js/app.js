import '../css/app.css';

import $ from 'jquery';
$(function () {
    $.ajax({
        url: "http://cbr-rates.local/rates",
        context: document.body
    }).done(function() {
        console.log('Hello Webpack Encore');
    });
})
