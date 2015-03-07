"use strict";

var ready         = require('elements/domready'),
    $             = require('elements'),
    zen           = require('elements/zen'),
    modal         = require('../../ui').modal,
    toastr        = require('../../ui').toastr,
    request       = require('agent'),
    lastItem      = require('mout/array/last'),
    indexOf       = require('mout/array/indexOf'),

    trim          = require('mout/string/trim'),

    getAjaxSuffix = require('../../utils/get-ajax-suffix');

require('elements/insertion');

ready(function() {
    var body = $('body');

    var addNewByEnter = function(title, key) {
        if (key == 'enter' && this.CollectionNew) {
            this.CollectionNew = false;
            body.emit('click', { target: this.parent('ul').find('[data-collection-addnew]') });
        }
    };

    var updateTitle = function() {

    };

    // Add new item
    body.delegate('click', '[data-collection-addnew]', function(event, element) {
        var list      = element.parent('ul'),
            dataField = element.parent('.settings-param').find('[data-collection-data]'),
            tmpl      = list.find('[data-collection-template]'),
            items     = list.search('> [data-collection-item]') || [],
            last      = $(lastItem(items));

        var clone = $(tmpl[0].cloneNode(true)), title, editable;

        if (last) { clone.after(last); }
        else { clone.top(list); }

        if (!items.length) { list.find('[data-collection-editall]').style('display', 'inline-block'); }

        title = clone.find('a');
        editable = title.find('[data-title-editable]');

        title.href(title.href() + items.length);

        clone.attribute('style', null).data('collection-item', clone.data('collection-template')).attribute('data-collection-template', null);
        editable.CollectionNew = true;
        body.emit('click', { target: title.siblings('[data-title-edit]') });

        editable.on('title-edit-exit', addNewByEnter);
        body.emit('change', { target: dataField });
    });

    // Edit Title
    body.delegate('blur', '[data-collection-item] [data-title-editable]', function(event, element) {
        var text      = trim(element.text()),
            item      = element.parent('[data-collection-item]'),
            key       = item.data('collection-item'),
            items     = element.parent('ul').search('> [data-collection-item]'),
            dataField = element.parent('.settings-param').find('[data-collection-data]'),
            data      = dataField.value(),
            index     = indexOf(items, item[0]);

        if (index == -1) { return; }

        data = JSON.parse(data);
        if (!data[index]) { data.splice(index, 0, {}); }
        data[index][key] = text;
        dataField.value(JSON.stringify(data));
        body.emit('change', { target: dataField });
    }, true);

    // Remove item
    body.delegate('click', '[data-collection-remove]', function(event, element) {
        var item      = element.parent('[data-collection-item]'),
            list      = element.parent('ul'),
            items     = list.search('> [data-collection-item]'),
            index     = indexOf(items, item[0]),
            dataField = element.parent('.settings-param').find('[data-collection-data]'),
            data      = dataField.value();

        data = JSON.parse(data);
        data.splice(index, 1);
        dataField.value(JSON.stringify(data));
        item.remove();
        if (items.length == 1) { list.find('[data-collection-editall]').style('display', 'none'); }
        body.emit('change', { target: dataField });
    });

    // Preventing click of links when title is being edited
    body.delegate('click', '[data-collection-item] a', function(event, element) {
        if (element.find('[contenteditable]')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    // Load item settings
    body.delegate('click', '[data-collection-item] .config-cog', function(event, element) {
        event.preventDefault();

        var data = {};

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: element.attribute('href') + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var form       = content.elements.content.find('form'),
                    submit     = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) {
                    return true;
                }

                // Particle Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showIndicator();

                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name     = input.attribute('name'),
                            value    = input.value(),
                            parent   = input.parent('.settings-param'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        if (!name || input.disabled() || (override && !override.checked())) { return; }
                        dataString.push(name + '=' + value);
                    });

                    var title = content.elements.content.find('[data-title-editable]');
                    if (title) {
                        //todo title= should be hooked up to the field.value see parent [data-collection-item] value
                        dataString.push('title=' + title.data('title-editable'));
                    }

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&') || {}, function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            modal.close();
                            toastr.success('Collection Item updated', 'Item Updated');
                        }

                        submit.hideIndicator();
                    });
                });
            }
        });
    });
});

module.exports = {};