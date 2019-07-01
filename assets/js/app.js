import '../css/app.css';
import '../../node_modules/bootstrap/dist/css/bootstrap.css';
import '../../node_modules/bootstrap-datepicker/dist/css/bootstrap-datepicker.css';
import '../../node_modules/flag-icon-css/css/flag-icon.css';
import '../../node_modules/font-awesome/css/font-awesome.css';

import 'bootstrap';
import 'bootstrap-datepicker';
import $ from 'jquery';
import './landkit';

$(function () {
    $.ajax({
        url: "http://cbr-rates.local",
        context: document.body
    }).done(function() {
        console.log('Hello Webpack Encore');
    });

    $('[data-behaviour=datepicker]').datepicker({
        weekStart: 1,
        format: 'yyyy-mm-dd',
        orientation: 'auto bottom',
        autoclose: true
    });

    var currencySort = $('[data-behaviour=currencySort]');
    var rateDateSort = $('[data-behaviour=rateDateSort]');
    var ratesFilterForm = $('#rates-filter form');

    function changeSort(input) {
        var clickableElement = input.closest('span.sort');
        var ord = input.val();
        var icon = clickableElement.find('i');
        icon.removeClass();
        icon.addClass('fa').addClass('fa-sort' + (ord ? '-' + ord.toLowerCase() : ''));
    }

    function request() {
        $.ajax({
            url: ratesFilterForm.attr('action'),
            data: $('form').serialize(),
            method: 'POST',
            success: function (response) {
                $('ul.list-group li:not(:first)').remove();
                var list = $(response).find('ul.list-group li:not(:first)');
                list.each(function () {
                    $('ul.list-group').append($(this));
                });
            }
        });
    }

    $('#submit').on('click', request);
    var initSortButton = function (input) {
        changeSort(input);
        var clickableElement = input.closest('span.sort');
        clickableElement.on('click', function () {
            var fields = [
                currencySort,
                rateDateSort
            ];
            var ord = input.val();
            fields.forEach(function (item) {
                $(item).val('');
                changeSort(item);
            });
            ord = ord ? (ord == 'ASC' ? 'DESC' : '') : 'ASC';
            input.val(ord);
            changeSort(input);
            request();
        });
    };

    initSortButton(currencySort);
    initSortButton(rateDateSort);
})
