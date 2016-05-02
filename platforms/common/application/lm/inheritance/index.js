"use strict";

var $                  = require('elements'),
    ready              = require('elements/domready'),
    request            = require('agent'),
    modal              = require('../../ui').modal,

    forEach            = require('mout/collection/forEach'),
    contains           = require('mout/array/contains'),

    getAjaxSuffix      = require('../../utils/get-ajax-suffix'),
    parseAjaxURI       = require('../../utils/get-ajax-url').parse,
    getAjaxURL         = require('../../utils/get-ajax-url').global,
    getOutlineNameById = require('../../utils/get-outline-by-id');


var IDsMap = {
    attributes: 'g-settings-particle',
    block: 'g-settings-block'
};

ready(function() {
    var body = $('body');
    body.delegate('change', '[name="inherit[outline]"]', function(event, element) {
        var label   = element.parent('.settings-param').find('.settings-param-title'),
            value   = element.value(),
            section = element.parent('[data-g-settings-id]'),
            data    = {
                outline: value,
                section: section ? section.data('g-settings-id') : ''
            };

        label.showIndicator();

        request('POST', parseAjaxURI(getAjaxURL('layouts') + getAjaxSuffix()), data, function(error, response) {
            label.hideIndicator();

            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });

                return;
            }

            var data      = response.body,
                includes  = section.find('[name="inherit[include]"]').value().split(','),
                container = modal.getByID(modal.getLast()),
                element;

            // refresh field values based on settings and ajax response
            forEach(IDsMap, function(id, option) {
                if (contains(includes, option) && data.html[id] && (element = container.find('#' + id))) {
                    element.html(data.html[id]);
                    var selects = element.search('[data-selectize]');
                    if (selects) { selects.selectize(); }
                }
            });
        });
    });
});