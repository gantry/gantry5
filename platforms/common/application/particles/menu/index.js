"use strict";
var $        = require('../../utils/elements.moofx'),
    domready = require('elements/domready');

domready(function() {
    $('body').delegate('click', '.g-main-nav .g-toplevel [data-g5-ajaxify]', function(event, element) {
        var items = $('.g-main-nav .g-toplevel [data-g5-ajaxify] !> li');
        if (items) { items.removeClass('active'); }
        element.parent('li').addClass('active');
    });
});