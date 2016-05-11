"use strict";

var $                  = require('elements'),
    ready              = require('elements/domready'),
    request            = require('agent'),
    modal              = require('../../ui').modal,

    forEach            = require('mout/collection/forEach'),
    filter             = require('mout/object/filter'),
    keys               = require('mout/object/keys'),
    contains           = require('mout/collection/contains'),

    getAjaxSuffix      = require('../../utils/get-ajax-suffix'),
    parseAjaxURI       = require('../../utils/get-ajax-url').parse,
    getAjaxURL         = require('../../utils/get-ajax-url').global,
    getOutlineNameById = require('../../utils/get-outline').getOutlineNameById,
    getCurrentOutline  = require('../../utils/get-outline').getCurrentOutline;


var IDsMap = {
    attributes: 'g-settings-particle',
    block: { panel: 'g-settings-block-attributes', tab: 'g-settings-block' }
};

ready(function() {
    var body = $('body');

    body.delegate('change', '[name="inherit[outline]"]', function(event, element) {
        var label    = element.parent('.settings-param').find('.settings-param-title'),
            value    = element.value(),
            form     = element.parent('[data-g-inheritance-settings]');

        var formData = JSON.parse(form.data('g-inheritance-settings')),
            data     = {
                outline: value || getCurrentOutline(),
                type: formData.type || '',
                subtype: formData.subtype || '',
                inherit: !!value ? '1' : '0'
            };

        data.id = formData.id;

        label.showIndicator();
        element.selectizeInstance.blur();

        var URI = $('[name="inherit[particle]"]') ? 'layouts/list' : 'layouts';
        request('POST', parseAjaxURI(getAjaxURL(URI) + getAjaxSuffix()), data, function(error, response) {
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
                includes  = form.find('[name="inherit[include]"]').value().split(','),
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

    body.delegate('change', '[data-multicheckbox-field]', function(event, element) {
        var value     = element.value(),
            isChecked = element.checked(),
            panel     = $('#' + (IDsMap[value] && IDsMap[value].panel || IDsMap[value])),
            tab       = $('#' + (IDsMap[value] && IDsMap[value].tab || IDsMap[value]) + '-tab');

        if (!panel || !tab) { return true; }

        var inherit = panel.find('.g-inherit');

        // if inherit overlay doesn't exist, we could be in a set
        /*if (!inherit) {
         inherit = panel.parent('.settings-block').find('.g-inherit');
         inherit.after(panel);
         }*/

        if (!isChecked) {
            var lock = tab.find('.fa-lock');

            if (lock) { lock.removeClass('fa-lock').addClass('fa-unlock'); }
            if (inherit) { inherit.hide(); }
        } else {
            var unlock = tab.find('.fa-unlock');

            if (unlock) { unlock.removeClass('fa-unlock').addClass('fa-lock'); }
            if (inherit) { inherit.show(); }

            body.emit('change', { target: element.parent('.settings-block').find('[name="inherit[outline]"]') });
        }
    });

    body.delegate('mouseup', '.g-tabs .fa-lock, .g-tabs .fa-unlock', function(event, element) {
        if (!element.parent('li').hasClass('active')) { return false; }

        var container = modal.getByID(modal.getLast()),
            isLocked  = element.hasClass('fa-lock'),
            id        = element.parent('a').id().replace(/\-tab$/, ''),
            prop      = keys(filter(IDsMap, function(value) { return value === id || value.tab === id; }) || []).shift(),
            input     = container.find('[data-multicheckbox-field][value="' + prop + '"]');

        if (input) {
            input.checked(!isLocked);
            body.emit('change', { target: input });
        }
    });
});