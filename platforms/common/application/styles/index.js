"use strict";
var ready = require('elements/domready'),
    $ = require('elements/attributes'),
    modal = require('../ui').modal,
    contains = require('mout/array/contains'),
    forEach = require('mout/collection/forEach');

require('../ui/popover');

var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1,
    FOCUSIN   = isFirefox ? 'focus' : 'focusin';

ready(function() {

    var body = $('body');

    body.delegate('click', '[data-g-styles]', function(event, element) {
        var target = $(event.target);
        if (event && event.preventDefault) { event.preventDefault(); }
        if (target.hasClass('swatch-preview') || target.parent('.swatch-preview')) { return true; }

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

    body.delegate('click', '[data-g-styles] .swatch-preview', function(event, element) {
        var image = element.parent('[data-g-styles]').find('img');
        if (!image) { return false; }

        modal.open({
            content: image[0].outerHTML,
            afterOpen: function(container) {
                var padding = parseInt(container.compute('padding-left'), 10) + parseInt(container.compute('padding-right'), 10);
                container.style({
                    maxWidth: '80%',
                    width: padding + (image[0].naturalWidth || image[0].width)
                });
            }
        });
    });

});

module.exports = {};
