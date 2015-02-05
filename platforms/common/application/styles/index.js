"use strict";
var ready         = require('elements/domready'),
    $             = require('elements/attributes'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
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

    body.delegate('click', '[data-g5-compile-css]', function(event, element) {
        event.stopPropagation();
        event.preventDefault();

        element.showSpinner();
        request('post', element.attribute('href') + getAjaxSuffix(), function(error, response) {
            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });

                return false;
            } else {
                toastr.success('The CSS was successfully compiled!', 'CSS Compiled');
            }

            element.hideSpinner();
        })
    });

});

module.exports = {};
