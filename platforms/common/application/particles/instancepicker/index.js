"use strict";

var $             = require('elements'),
    zen           = require('elements/zen'),
    ready         = require('elements/domready'),
    modal         = require('../../ui').modal,
    request       = require('agent'),
    trim          = require('mout/string/trim'),
    parseAjaxURI  = require('../../utils/get-ajax-url').parse,
    getAjaxURL    = require('../../utils/get-ajax-url').global,
    getAjaxSuffix = require('../../utils/get-ajax-suffix');


ready(function() {
    var body = $('body'),
        particleField = $('[data-g-instancepicker] ~ input[type="hidden"]'),
        moduleType = {
            wordpress: 'widget',
            joomla: 'module'
        };

    //if (particleField) {
        //particleField.on('change', function(){
        body.delegate('input', '[data-g-instancepicker] ~ input[type="hidden"]', function(event, element){
            if (!element.value()) {
                var title = element.siblings('.g-instancepicker-title'),
                    label = element.siblings('[data-g-instancepicker]'),
                    reset = element.sibling('.g-reset-field');

                title.text('');
                label.text(label.data('g-instancepicker-text'));
                reset.style('display', 'none');
            }
        });
    //}


    body.delegate('click', '[data-g-instancepicker]', function(event, element) {
        if (event) { event.preventDefault(); }

        var data = JSON.parse(element.data('g-instancepicker')),
            field = $('[name="' + data.field + '"]'),
            value, uri; // = 'particle' + ((data.type == moduleType[GANTRY_PLATFORM]) ? '/' + moduleType[GANTRY_PLATFORM] : ''),

        if (data.type == moduleType[GANTRY_PLATFORM]) {
            uri = (data.type != 'widget' ? 'particle/' : '') + moduleType[GANTRY_PLATFORM];
        } else {
            uri = 'particle';
        }

        if (!field) { return false; }

        value = field.value();

        if ((data.type == 'particle' || data.type == 'widget') && value) {
            value = JSON.parse(value || {});
            uri = value.type + '/' + value[data.type];
        }

        if (data.modal_close) { return true; }

        modal.open({
            content: 'Loading',
            method: !value || data.type == 'module' ? 'get' : 'post', // data.type == moduleType[GANTRY_PLATFORM]
            data: !value || data.type == 'module' ? {} : value, // data.type == moduleType[GANTRY_PLATFORM]
            overlayClickToClose: false,
            remote: parseAjaxURI(getAjaxURL(uri) + getAjaxSuffix()),
            remoteLoaded: function(response, modalInstance) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }

                var content = modalInstance.elements.content,
                    select = content.find('[data-mm-select]');

                var search = content.find('.search input'),
                    blocks = content.search('[data-mm-type]'),
                    filters = content.search('[data-mm-filter]');

                if (search && filters && blocks) {
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

                    setTimeout(function() {
                        search[0].focus();
                    }, 5);
                }

                var elementData = JSON.parse(element.data('g-instancepicker'));
                if (elementData.type == moduleType[GANTRY_PLATFORM]) { elementData.modal_close = true; }
                if (select) { select.data('g-instancepicker', JSON.stringify(elementData)); }
                else {
                    var form = content.find('form'),
                        fakeDOM = zen('div').html(response.body.html || response.body).find('form'),
                        submit = content.find('input[type="submit"], button[type="submit"]'),
                        dataString = [];

                    if ((!form && !fakeDOM) || !submit) { return true; }

                    var applyAndSave = content.search('[data-apply-and-save]');
                    if (applyAndSave) { applyAndSave.remove(); }

                    submit.on('click', function(e) {
                        e.preventDefault();
                        dataString = [];

                        submit.showIndicator();

                        $(fakeDOM[0].elements).forEach(function(input) {
                            input = $(input);
                            var name = input.attribute('name'),
                                type = input.attribute('type');
                            if (!name || input.disabled() || (type == 'radio' && !input.checked())) { return; }

                            input = content.find('[name="' + name + '"]');
                            var value = value = input.type() == 'checkbox' ? Number(input.checked()) : input.value(),
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

                                var label = field.siblings('.g-instancepicker-title');

                                if (field) {
                                    field.value(JSON.stringify(response.body.item));
                                    $('body').emit('change', { target: field });
                                }

                                if (label) { label.text(response.body.item.title); }
                            }

                            modal.close();
                            submit.hideIndicator();
                        });
                    });

                }
            }
        });
    });
});

module.exports = {};
