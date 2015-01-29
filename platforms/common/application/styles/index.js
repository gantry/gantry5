"use strict";
var ready         = require('elements/domready'),
    $             = require('elements/attributes'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    zen           = require('elements/zen'),
    contains      = require('mout/array/contains'),
    size          = require('mout/collection/size'),

    getAjaxURL    = require('../utils/get-ajax-url').config,
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

    body.delegate('click', '[data-g5-resetcache]', function(event, element) {
        event.stopPropagation();
        event.preventDefault();

        var currentConfig = $('#configuration-selector').value();

        //request('post', getAjaxURL(currentConfig + '/styles/resetcache'), function() {
        request('post', window.location.href + '/resetcache', function() {
           // toastr;
          toastr.success('The CSS Compiled has been successfully reset!', 'CSS Compiled Reset');
        })
    });

});

module.exports = {};