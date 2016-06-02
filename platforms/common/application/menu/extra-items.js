"use strict";
var $             = require('elements'),
    zen           = require('elements/zen'),
    ready         = require('elements/domready'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    indexOf       = require('mout/array/indexOf'),
    trim          = require('mout/string/trim'),
    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxURL    = require('../utils/get-ajax-url').global,
    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    flags         = require('../utils/flags-state'),
    deepEquals    = require('mout/lang/deepEquals');

var WordpressWidgetsCustomizer = require('../utils/wp-widgets-customizer');

var menumanager = null;

var randomID = function randomString(len, an) {
    an = an && an.toLowerCase();
    var str = "", i = 0, min = an == 'a' ? 10 : 0, max = an == 'n' ? 10 : 62;

    for (; i++ < len;) {
        var r = Math.random() * (max - min) + min << 0;
        str += String.fromCharCode(r += r > 9 ? r < 36 ? 55 : 61 : 48);
    }
    return str;
};

var StepOne = function(map, mode) { // mode [reorder, resize, evenResize]
    if (this.isNewParticle && mode !== 'reorder') { return; }
    this.resizer.updateItemSizes();

    menumanager = this;

    var save = $('[data-save]'),
        current = {
            settings: this.settings,
            ordering: this.ordering,
            items: this.items
        };

    if (!this.isNewParticle) {
        if (!deepEquals(map, current)) {
            save.showIndicator('fa fa-fw changes-indicator fa-circle-o');
            flags.set('pending', true);
        } else {
            save.hideIndicator();
            flags.set('pending', false);
        }
    }

    if (this.isParticle && this.isNewParticle) {
        var blocktype = this.block.data('mm-blocktype');
        this.block.attribute('data-mm-blocktype', null).addClass('g-menu-item-' + blocktype).data('mm-original-type', blocktype);
        zen('span.menu-item-type.badge').text(blocktype).after(this.block.find('.menu-item .title'));

        modal.open({
            content: 'Loading',
            method: 'post',
            //data: data,
            remote: parseAjaxURI($(this.block).find('.config-cog').attribute('href') + getAjaxSuffix()),
            remoteLoaded: function(response, modal) {
                var search = modal.elements.content.find('.search input'),
                    blocks = modal.elements.content.search('[data-mm-type]'),
                    filters = modal.elements.content.search('[data-mm-filter]');

                if (!search || !filters || !blocks) { return; }

                search.on('input', function() {
                    if (!this.value()) {
                        blocks.removeClass('hidden');
                        return;
                    }

                    blocks.addClass('hidden');

                    var found = [], value = this.value().toLowerCase(), text;

                    filters.forEach(function(filter) {
                        filter = $(filter);
                        text = trim(filter.data('mm-filter')).toLowerCase();
                        if (text.match(new RegExp("^" + value + '|\\s' + value, 'gi'))) {
                            found.push(filter.matches('[data-mm-type]') ? filter : filter.parent('[data-mm-type]'));
                        }
                    }, this);

                    if (found.length) { $(found).removeClass('hidden'); }
                });

                setTimeout(function(){
                    search[0].focus();
                }, 5);
            }
        });
    }

    this.type = undefined;
};

var StepTwo = function(data, content, button) {
    var uri = content.find('[data-mm-particle-stepone]').data('mm-particle-stepone'),
        picker = data.instancepicker,
        moduleType = {
            wordpress: 'widget',
            joomla: 'particle'
        };

    if (picker) {
        var item = JSON.parse(data.item);
        picker = JSON.parse(picker);
        delete(data.instancepicker);
        //uri = getAjaxURL(item.type + '/' + item[moduleType[GANTRY_PLATFORM]]);
        uri = getAjaxURL(item.type + '/' + item[item.type]);
    }

    request('post', parseAjaxURI(uri + getAjaxSuffix()), data, function(error, response) {
        if (!response.body.success) {
            modal.open({
                content: response.body.html || response.body,
                afterOpen: function(container) {
                    if (!response.body.html) { container.style({ width: '90%' }); }
                }
            });

            button.hideIndicator();

            return;
        }

        content.html(response.body.html);

        var selects = $('[data-selectize]');
        if (selects) { selects.selectize(); }

        var urlTemplate = content.find('.g-urltemplate');
        if (urlTemplate) { $('body').emit('input', { target: urlTemplate }); }

        var form = content.find('form'),
            submit = content.find('input[type="submit"], button[type="submit"]'),
            fakeDOM = zen('div').html(response.body.html).find('form'),
            dataString = [];

        if ((!form && !fakeDOM) || !submit) { return true; }

        var applyAndSave = content.search('[data-apply-and-save]');
        if (applyAndSave) { applyAndSave.remove(); }

        // Module / Particle Settings apply
        submit.on('click', function(e) {
            e.preventDefault();
            dataString = [];

            submit.showIndicator();

            $(fakeDOM[0].elements).forEach(function(input) {
                input = $(input);
                var name = input.attribute('name'),
                    type = input.attribute('type');
                if (!name || input.disabled() || (type == 'radio' && !input.checked())) { return; }

                input = content.find('[name="' + name + '"]' + (type == 'radio' ? ':checked' : ''));
                var value = input.type() == 'checkbox' ? Number(input.checked()) : input.value(),
                    parent = input.parent('.settings-param'),
                    override = parent ? parent.find('> input[type="checkbox"]') : null;

                override = override || $(input.data('override-target'));

                if (override && !override.checked()) { return; }
                if (input.type() != 'checkbox' || (input.type() == 'checkbox' && !!value)) {
                    dataString.push(name + '=' + encodeURIComponent(value));
                }
            });

            var title = content.find('[data-title-editable]');
            if (title) {
                dataString.push('title=' + encodeURIComponent(title.data('title-editable')));
            }

            request(fakeDOM.attribute('method'), parseAjaxURI(fakeDOM.attribute('action') + getAjaxSuffix()), dataString.join('&') || {}, function(error, response) {
                if (!response.body.success) {
                    modal.open({
                        content: response.body.html || response.body,
                        afterOpen: function(container) {
                            if (!response.body.html) { container.style({ width: '90%' }); }
                        }
                    });
                } else {
                    // it's menu
                    if (!picker) {
                        var element = menumanager.element,
                            path = element.data('mm-id') + '-',
                            id = randomID(5),
                            base = element.parent('[data-mm-base]').data('mm-base'),
                            col = (element.parent('[data-mm-id]').data('mm-id').match(/\d+$/) || [0])[0],
                            index = indexOf(element.parent().children('[data-mm-id]'), element[0]);

                        while (menumanager.items[path + id]) { id = randomID(5); }

                        menumanager.items[path + id] = response.body.item;
                        if (!menumanager.ordering[base]) menumanager.ordering[base] = [];
                        if (!menumanager.ordering[base][col]) menumanager.ordering[base][col] = [];
                        menumanager.ordering[base][col].splice(index, 1, path + id);
                        element.data('mm-id', path + id);

                        if (response.body.html) {
                            element.html(response.body.html);
                        }

                        menumanager.isNewParticle = false;
                        menumanager.emit('dragEnd', menumanager.map);
                        toastr.success('The Menu Item settings have been applied to the Main Menu. <br />Remember to click the Save button to store them.', 'Settings Applied');
                    } else { // it's field picker
                        var field = $('[name="' + picker.field + '"]'),
                            btnPicker = field.siblings('[data-g-instancepicker]'),
                            label = field.siblings('.g-instancepicker-title');

                        if (field) {
                            field.value(JSON.stringify(response.body.item));
                            $('body').emit('change', { target: field });
                        }
                        if (label) { label.text(response.body.item.title); }

                        if (item.type == 'particle') {
                            btnPicker.text(btnPicker.data('g-instancepicker-alttext'));
                        }
                    }
                }

                modal.close();
                submit.hideIndicator();
                WordpressWidgetsCustomizer(field);
            });
        });
    });
};


ready(function() {
    var body = $('body');

    body.delegate('click', '.menu-editor-extras [data-lm-blocktype], .menu-editor-extras [data-mm-module]', function(event, element) {
        var container = element.parent('.menu-editor-extras'),
            elements = container.search('[data-lm-blocktype], [data-mm-module]'),
            selectButton = container.find('[data-mm-select]');

        elements.removeClass('selected');
        element.addClass('selected');

        selectButton.attribute('disabled', null);
    });

    // second step
    body.delegate('click', '.menu-editor-extras [data-mm-select]', function(event, element) {
        event.preventDefault();

        if (element.hasClass('disabled') || element.attribute('disabled')) { return false; }

        var container = element.parent('.menu-editor-extras'),
            selected = container.find('[data-lm-blocktype].selected, [data-mm-module].selected'),
            type = selected.data('mm-type');

        data = { type: type };

        switch (type) {
            case 'particle':
                data['particle'] = selected.data('lm-subtype');
                break;

            case 'widget':
                data['widget'] = selected.data('lm-subtype');
                break;

            case 'module':
                data['particle'] = type;
                data['title'] = selected.find('[data-mm-title]').data('mm-title');
                data['options'] = { particle: { module_id: selected.data('mm-module') } };
                break;
        }

        element.showIndicator();


        var data, instancepicker = element.data('g-instancepicker');

        if (instancepicker && type == 'module') {
            data = JSON.parse(instancepicker);
            var field = $('[name="' + data.field + '"]');
            if (field) {
                field.value(selected.data('mm-module'));
                body.emit('input', { target: field });
            }

            element.hideIndicator();
            modal.close();

            return false;
        } else {
            var ip = instancepicker;
            element.data('g-instancepicker', null);
            StepTwo({
                item: JSON.stringify(data),
                instancepicker: ip ? ip : null
            }, element.parent('.g5-content'), element);
        }
    });
});

module.exports = StepOne;
