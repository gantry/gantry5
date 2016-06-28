"use strict";

var $                  = require('elements'),
    ready              = require('elements/domready'),
    request            = require('agent'),
    modal              = require('../../ui').modal,

    isArray            = require('mout/lang/isArray'),
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
    attributes: ['g-settings-particle', 'g-settings-atom'],
    block: { panel: 'g-settings-block-attributes', tab: 'g-settings-block' },
    particles: 'g-inherit-particle',
    atoms: 'g-inherit-atom'
};

ready(function() {
    var body             = $('body'),
        currentSelection = {},
        currentMode      = {};

    body.delegate('change', '[name="inherit[outline]"]', function(event, element) {
        var label          = element.parent('.settings-param').find('.settings-param-title'),
            value          = element.value(),
            name           = $('[name="inherit[section]"]') ? $('[name="inherit[section]"]').value() : '',
            form           = element.parent('[data-g-inheritance-settings]'),
            includesFields = $('[data-multicheckbox-field="inherit[include]"]:checked') || [],
            particle       = {
                list: $('#g-inherit-particle, #g-inherit-atom'),
                mode: $('[name="inherit[mode]"]:checked'),
                radios: $('[name="inherit[particle]"], [name="inherit[atom]"]'),
                checked: $('[name="inherit[particle]"]:checked, [name="inherit[atom]"]:checked')
            };

        var hasChanged = currentSelection[name] !== value || currentMode[name] !== particle.mode.value();

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
                mode: particle.mode.value(),
                inherit: !!value && particle.mode.value() === 'inherit' ? '1' : '0'
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

        var URI_mode = data.type === 'atom' ? 'atoms' : 'layouts',
            URI      = particle.list ? URI_mode + '/list' : URI_mode;

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
                id = !isArray(id) ? [id] : id;

                id.forEach(function(currentID) {
                    var shouldRefresh = contains(includes, option),
                        isAvailable   = contains(available, option);

                    if ((shouldRefresh || !isAvailable) && data.html[currentID] && (element = container.find('#' + currentID))) {
                        element.html(data.html[currentID]);
                        var selects = element.search('[data-selectize]');
                        if (selects) { selects.selectize(); }
                    }
                });
            });

            if (hasChanged && includesFields && currentSelection[name] === '') {
                includesFields.forEach(function(include) { body.emit('change', { target: include }); });
            }

            currentSelection[name] = value;
            currentMode[name] = particle.mode.value();
        });
    });

    body.delegate('change', '[data-multicheckbox-field]', function(event, element) {
        var value     = element.value(),
            isChecked = element.checked(),
            noRefresh = event.noRefresh,
            outline   = $('[name="inherit[outline]"]').value(),
            particle  = {
                mode: $('[name="inherit[mode]"]:checked'),
                radios: $('[name="inherit[particle]"], [name="inherit[atom]"]'),
                checked: $('[name="inherit[particle]"]:checked, [name="inherit[atom]"]:checked')
            };

        var IDs = {
            panel: (IDsMap[value] && IDsMap[value].panel || IDsMap[value]),
            tab: (IDsMap[value] && IDsMap[value].tab || IDsMap[value])
        };

        if (!isArray(IDs.panel)) {
            IDs.panel = [IDs.panel];
            IDs.tab = [IDs.tab];
        }

        IDs.panel.forEach(function(currentPanel, index) {
            var panel = $('#' + currentPanel),
                tab   = $('#' + IDs.tab[index] + '-tab');

            if (!panel || !tab) { return true; }

            var inherit = panel.find('.g-inherit'),
                isClone = particle.mode.value() === 'clone',
                refresh = function(noRefresh) {
                    if (!noRefresh) {
                        body.emit('change', { target: element.parent('.settings-block').find('[name="inherit[outline]"]') });
                    }
                };

            if (!isChecked || !outline || isClone) {
                var lock = tab.find('.fa-lock');

                if (lock) { lock.removeClass('fa-lock').addClass('fa-unlock'); }
                if (inherit) { inherit.hide(); }
                if (isClone) { refresh(noRefresh); }
            } else {
                var unlock = tab.find('.fa-unlock');

                if (unlock) { unlock.removeClass('fa-unlock').addClass('fa-lock'); }
                if (inherit) { inherit.show(); }

                refresh(noRefresh);
            }
        });
    });


    body.delegate('change', '[name="inherit[mode]"], [name="inherit[particle]"], [name="inherit[atom]"]', function(event, element) {
        var container  = modal.getByID(modal.getLast()),
            outline    = container.find('[name="inherit[outline]"]'),
            checkboxes = container.search('[data-multicheckbox-field]') || [],
            noRefresh  = false;

        if (element.attribute('name') === 'inherit[mode]') {
            noRefresh = true;
        }

        body.emit('change', { target: outline, noRefresh: noRefresh });
        checkboxes.forEach(function(checkbox) {
            body.emit('change', { target: checkbox, noRefresh: noRefresh });
        });
    });

    body.delegate('click', '#g-inherit-particle .fa-info-circle, #g-inherit-atom .fa-info-circle', function(event, element) {
        event.preventDefault();

        var container = modal.getByID(modal.getLast()),
            outline   = container.find('[name="inherit[outline]"]'),
            id        = element.siblings('input[name="inherit[particle]"], input[name="inherit[atom]"]');

        if (!id || !outline) { return false; }

        var URI = id.name() === 'inherit[atom]' ? 'atoms/instance' : 'layouts/particle';
        modal.open({
            content: 'Loading',
            method: 'post',
            data: { id: id.value(), outline: outline.value() || getCurrentOutline() },
            remote: parseAjaxURI(getAjaxURL(URI) + getAjaxSuffix()),
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
            prop      = keys(filter(IDsMap, function(value) { return value === id || value.tab === id || contains(value, id); }) || []).shift(),
            input     = container.find('[data-multicheckbox-field][value="' + prop + '"]'),
            particle  = {
                mode: $('[name="inherit[mode]"]:checked'),
                radios: $('[name="inherit[particle]"], [name="inherit[atom]"]'),
                checked: $('[name="inherit[particle]"]:checked, [name="inherit[atom]"]:checked')
            };

        if (input) {
            // do not try to refresh attributes/block inheritance when there's no particle selected
            // or if we are in clone mode
            if (particle.mode.value() === 'clone' || (particle.radios && !particle.checked)) {
                return false;
            }

            input.checked(!isLocked);
            body.emit('change', { target: input });
        }
    });
});