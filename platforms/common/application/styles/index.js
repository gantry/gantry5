"use strict";
var ready = require('elements/domready'),
    $ = require('elements/attributes'),
    contains = require('mout/array/contains'),
    forEach = require('mout/collection/forEach');

require('../ui/popover');

var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1,
    FOCUSIN   = isFirefox ? 'focus' : 'focusin';

ready(function() {

    var body = $('body');

    body.delegate('click', '[data-g-styles]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var data = JSON.parse(element.data('g-styles')), input, value, type, evt;
        forEach(data, function(preset, name) {
            input = $('[name="' + name + '"]');
            value = input ? input.value() : false;

            if (!input || value === preset) { return; }

            evt = {
                target: input,
                forceOverride: true
            };

            type = (input.tag() == 'select' || contains(['hidden', 'checkbox'], input.type())) ? 'change' : 'input';

            input.value(preset);
            body.emit(type, evt);
            body.emit('keyup', evt);
        });
    });

});

module.exports = {};
