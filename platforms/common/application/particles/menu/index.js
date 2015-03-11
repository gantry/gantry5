"use strict";
var $             = require('../../utils/elements.utils'),
    domready      = require('elements/domready');

domready(function() {
    var body = $('body');
    body.delegate('click', '[data-g5-content] .g-main-nav .g-toplevel [data-g5-ajaxify]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var items = $('[data-g5-content] .g-main-nav .g-toplevel [data-g5-ajaxify] !> li');
        if (items) { items.removeClass('active'); }
        element.parent('li').addClass('active');
    });
});

module.exports = {};