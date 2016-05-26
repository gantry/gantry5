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
    block: { panel: 'g-settings-block-attributes', tab: 'g-settings-block' },
    inheritance: 'g-inherit-particle'
};

ready(function() {
    var body             = $('body'),
        currentSelection = {};

    body.delegate('change', '[name="inherit[outline]"]', function(event, element) {
        var label          = element.parent('.settings-param').find('.settings-param-title'),
            value          = element.value(),
            name           = $('[name="inherit[section]"]').value(),
            form           = element.parent('[data-g-inheritance-settings]'),
            hasChanged     = currentSelection[name] !== value,
            includesFields = $('[data-multicheckbox-field="inherit[include]"]:checked'),
            particle       = {
                list: $('#g-inherit-particle'),
                radios: $('[name="inherit[particle]"]'),
                checked: $('[name="inherit[particle]"]:checked')
            };

        if (hasChanged && !value) {
            includesFields.forEach(function(include) {
                $(include).checked(false);
                body.emit('change', { target: include });
            });
        }

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

        if (particle.radios && particle.checked) {
            if (!hasChanged) {
                data.selected = particle.checked.value();
                data.id = particle.checked.value();
                particle.list = false;
            }
        }

        var URI = particle.list ? 'layouts/list' : 'layouts';
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
                available = form.search('[data-multicheckbox-field="inherit[include]"]').map(function(item) { return $(item).value(); }),
                container = modal.getByID(modal.getLast()),
                element;

            // refresh field values based on settings and ajax response
            forEach(IDsMap, function(id, option) {
                id = id.panel || id;
                var shouldRefresh = contains(includes, option),
                    isAvailable   = contains(available, option);

                if ((shouldRefresh || !isAvailable) && data.html[id] && (element = container.find('#' + id))) {
                    element.html(data.html[id]);
                    var selects = element.search('[data-selectize]');
                    if (selects) { selects.selectize(); }
                }
            });

            if (hasChanged && includesFields && currentSelection[name] === '') {
                includesFields.forEach(function(include) { body.emit('change', { target: include }); });
            }

            currentSelection[name] = value;
        });
    });

    body.delegate('change', '[data-multicheckbox-field]', function(event, element) {
        var value     = element.value(),
            isChecked = element.checked(),
            panel     = $('#' + (IDsMap[value] && IDsMap[value].panel || IDsMap[value])),
            tab       = $('#' + (IDsMap[value] && IDsMap[value].tab || IDsMap[value]) + '-tab'),
            outline   = $('[name="inherit[outline]"]').value(),
            particle  = {
                radios: $('[name="inherit[particle]"]'),
                checked: $('[name="inherit[particle]"]:checked')
            };

        if (!panel || !tab) { return true; }

        var inherit = panel.find('.g-inherit');

        // do not try to refresh attributes/block inheritance when there's no particle selected
        /*if (particle.radios && !particle.checked) {
         return false;
         }*/


        // if inherit overlay doesn't exist, we could be in a set
        /*if (!inherit) {
         inherit = panel.parent('.settings-block').find('.g-inherit');
         inherit.after(panel);
         }*/

        if (!isChecked || !outline) {
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

    body.delegate('change', '[name="inherit[particle]"]', function(event, element) {
        var container = modal.getByID(modal.getLast()),
            outline   = container.find('[name="inherit[outline]"]');

        body.emit('change', { target: outline });
    });

    body.delegate('click', '#g-inherit-particle .fa-info-circle', function(event, element) {
        event.preventDefault();

        var container = modal.getByID(modal.getLast()),
            outline   = container.find('[name="inherit[outline]"]'),
            id        = element.siblings('input[name="inherit[particle]"]');

        if (!id || !outline) { return false; }

        modal.open({
            content: 'Loading',
            method: 'post',
            data: { id: id.value(), outline: outline.value() },
            remote: parseAjaxURI(getAjaxURL('layouts/particle') + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }
            }
        });

        return false;
    });

    body.delegate('mouseup', '.g-tabs .fa-lock, .g-tabs .fa-unlock', function(event, element) {
        if (!element.parent('li').hasClass('active')) { return false; }

        var container = modal.getByID(modal.getLast()),
            isLocked  = element.hasClass('fa-lock'),
            id        = element.parent('a').id().replace(/\-tab$/, ''),
            prop      = keys(filter(IDsMap, function(value) { return value === id || value.tab === id; }) || []).shift(),
            input     = container.find('[data-multicheckbox-field][value="' + prop + '"]'),
            particle  = {
                radios: $('[name="inherit[particle]"]'),
                checked: $('[name="inherit[particle]"]:checked')
            };

        if (input) {
            // do not try to refresh attributes/block inheritance when there's no particle selected
            if (particle.radios && !particle.checked) {
                return false;
            }

            input.checked(!isLocked);
            body.emit('change', { target: input });
        }
    });
});