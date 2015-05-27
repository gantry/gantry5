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
    var body = $('body');

    body.delegate('click', '[data-g-instancepicker]', function(event, element) {
        if (event) { event.preventDefault(); }

        var data = JSON.parse(element.data('g-instancepicker')),
            field = $('[name="' + data.field + '"]'),
            uri = 'particle' + ((data.type == 'module') ? '/module' : ''),
            value;

        if (!field) { return false; }

        value = field.value();

        if (data.type == 'particle' && value) {
            value = JSON.parse(value || {});
            uri = value.type + '/' + value[data.type];
        }


        modal.open({
            content: 'Loading',
            method: !value || data.type == 'module' ? 'get' : 'post',
            data: !value || data.type == 'module' ? {} : value,
            remote: getAjaxURL(uri) + getAjaxSuffix(),
            remoteLoaded: function(response, modalInstance) {
                var content = modalInstance.elements.content,
                    select = content.find('[data-mm-select]');

                if (select) { select.data('g-instancepicker', element.data('g-instancepicker')); }
                else {
                    var form = content.find('form'),
                        fakeDOM = zen('div').html(response.body.html).find('form'),
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
                            var name = input.attribute('name');
                            if (!name || input.disabled()) { return; }

                            input = content.find('[name="' + name + '"]');
                            var value = input.value(),
                                parent = input.parent('.settings-param'),
                                override = parent ? parent.find('> input[type="checkbox"]') : null;

                            if (override && !override.checked()) { return; }
                            dataString.push(name + '=' + encodeURIComponent(value));
                        });

                        var title = content.find('[data-title-editable]');
                        if (title) {
                            dataString.push('title=' + encodeURIComponent(title.data('title-editable')));
                        }

                        request(parseAjaxURI(fakeDOM.attribute('method'), fakeDOM.attribute('action') + getAjaxSuffix()), dataString.join('&') || {}, function(error, response) {
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