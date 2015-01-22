"use strict";
var ready         = require('elements/domready'),
    $             = require('elements/attributes'),
    modal         = require('../ui').modal,
    request       = require('agent'),
    zen           = require('elements/zen'),
    contains      = require('mout/array/contains'),
    size          = require('mout/collection/size'),

    getAjaxSuffix = require('../utils/get-ajax-suffix');

require('../ui/popover');

ready(function() {

    var body = $('body');

    body.delegate('mouseenter', 'a.swatch', function(event, element) {
        element = $(element);
        event.preventDefault();
        element.popover({
            trigger: 'mouse',
            placement: 'top-left',
            style: 'styles, inverse, fixed, nooverflow',
            content: element.('.swatch-image')
        });
    });

});