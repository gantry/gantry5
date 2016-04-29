"use strict";
var $        = require('elements/attributes'),
    ready    = require('elements/domready'),
    remove   = require('mout/array/remove'),
    insert   = require('mout/array/insert'),
    contains = require('mout/array/contains');


ready(function() {
    var body = $('body');

    body.delegate('change', '.input-multicheckbox .input-group input[name][type="hidden"]', function(event, element) {
        var name = element.attribute('name'),
            values = element.value().split(','),
            fields = $('[data-multicheckbox-field="' + name +'"]');

        if (fields) {
            fields.forEach(function(field) {
                field = $(field);
                if (field.checked()) { insert(values, field.value()); }
                if (!field.checked()) { remove(values, field.value()); }
            });
        }

        element.value(values.filter(String).join(','));
    });

    body.delegate('change', '.input-multicheckbox .input-group input[data-multicheckbox-field][type="checkbox"]', function(event, element) {
        var field     = $('[name="' + element.data('multicheckbox-field') + '"]'),
            value     = element.value(),
            values    = field.value().split(','),
            isChecked = element.checked();

        if (isChecked) { insert(values, value); }
        if (!isChecked) { remove(values, value); }

        field.value(values.filter(String).join(','));

        body.emit('change', {target: field});
    });
});

