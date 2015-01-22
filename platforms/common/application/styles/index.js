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

    body.delegate('mouseover', 'a.swatch', function(event, element) {
        element = $(element);
        event.preventDefault();

        element.getPopover({
            trigger: 'mouse',
            placement: 'auto',
            targetEvents: false,
            delay: 1,
            content: element.html()
        }).show();
    });

});

module.exports = {};