"use strict";

var ready         = require('elements/domready'),
    $             = require('elements'),
    zen           = require('elements/zen'),
    has           = require('mout/object/has'),
    some          = require('mout/array/some'),
    modal         = require('../../ui').modal,
    toastr        = require('../../ui').toastr,
    request       = require('agent'),
    indexOf       = require('mout/array/indexOf'),
    contains      = require('mout/array/contains'),
    lastItem      = require('mout/array/last'),
    simpleSort    = require('sortablejs'),
    escapeUnicode = require('mout/string/escapeUnicode'),

    trim          = require('mout/string/trim'),

    getAjaxSuffix = require('../../utils/get-ajax-suffix');

require('elements/insertion');

ready(function() {
    var body = $('body');

    var createSortables = function(list) {
        var lists = list || $('.g-keyvalue-field ul');
        if (!lists) { return; }
        lists.forEach(function(list) {
            list = $(list);
            list.SimpleSort = simpleSort.create(list[0], {
                handle: '.fa-reorder',
                filter: '[data-keyvalue-nosort]',
                scroll: false,
                animation: 150,
                onStart: function() {
                    $(this.el).addClass('keyvalue-sorting');
                },
                onEnd: function(evt) {
                    var element = $(this.el);
                    element.removeClass('keyvalue-sorting');

                    if (evt.oldIndex === evt.newIndex) { return; }

                    var dataField = element.parent('.settings-param').find('[data-keyvalue-data]'),
                        data      = dataField.value();

                    data = JSON.parse(data);

                    data.splice(evt.newIndex, 0, data.splice(evt.oldIndex, 1)[0]);
                    dataField.value(JSON.stringify(data));
                    body.emit('change', { target: dataField });
                }
            });
        });
    };

    createSortables();

    // delegate sortables collections for ajax support
    body.delegate('mouseover', '.g-keyvalue-field ul', function(event, element) {
        if (!element.SimpleSort) { createSortables(element); }
    });

    // Add new item
    body.delegate('click', '[data-keyvalue-addnew]', function(event, element) {
        var param = element.parent('.settings-param'),
            list  = param.find('ul'),
            tmpl  = param.find('[data-keyvalue-template]'),
            items = list.search('> [data-keyvalue-item]') || [],
            last  = $(lastItem(items));

        var clone = $(tmpl[0].cloneNode(true));

        if (last) { clone.after(last); }
        else { clone.top(list); }

        clone.attribute('style', null).data('keyvalue-item', clone.data('keyvalue-template'));
        clone.attribute('data-keyvalue-template', null);
        clone.attribute('data-keyvalue-nosort', null);
        clone.find('[data-keyvalue-key]')[0].focus();

        //body.emit('change', { target: dataField });
    });

    // Remove item
    body.delegate('click', '[data-keyvalue-remove]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var item      = element.parent('[data-keyvalue-item]'),
            key       = item.find('input[type="text"]').data('keyvalue-key'),
            dataField = element.parent('.settings-param').find('[data-keyvalue-data]'),
            items     = element.parent('ul').search('> [data-keyvalue-item]'),
            index     = indexOf(items, item[0]),
            data      = JSON.parse(dataField.value());

        data.splice(index, 1);
        dataField.value(escapeUnicode(JSON.stringify(data)));
        item.remove();

        body.emit('change', { target: dataField });
    });

    // Change values
    body.delegate('blur', '[data-keyvalue-item] input[type="text"]', function(event, element) {
        var parent     = element.parent('[data-keyvalue-item]'),
            wrapper    = parent.find('.g-keyvalue-wrapper'),
            keyElement = parent.find('[data-keyvalue-key]'),
            valElement = parent.find('[data-keyvalue-value]'),
            key        = keyElement.data('keyvalue-key'),
            keyValue   = trim(keyElement.value()),
            valValue   = trim(valElement.value()),
            items     = element.parent('ul').search('> [data-keyvalue-item]:not(.g-keyvalue-warning):not(.g-keyvalue-excluded)'),
            index     = indexOf(items, parent[0]),

            dataField  = element.parent('.settings-param').find('[data-keyvalue-data]'),
            data       = JSON.parse(dataField.value()),
            exclude    = JSON.parse(dataField.data('keyvalue-exclude')),
            excluded   = contains(exclude, keyValue),
            duplicate  = some(data, function(obj) { return has(obj, keyValue); }) && key !== keyValue;

        if (keyElement == element) {
            // renamed or cleared key, need to cleanup JSON
            if (key !== keyValue && !duplicate) {
                data.splice(index, 1);
                keyElement.data('keyvalue-key', keyValue || '');
            }

            parent[duplicate ? 'addClass' : 'removeClass']('g-keyvalue-warning');
            parent[excluded ? 'addClass' : 'removeClass']('g-keyvalue-excluded');

            wrapper[excluded || duplicate ? 'addClass' : 'removeClass']('g-tooltip');
            wrapper.data('title', duplicate ? 'The key "' + keyValue + '" is a duplicate' : (excluded ? 'The key "' + keyValue + '" has been excluded and cannot be used' : null));
        }

        if (keyValue && !excluded && !duplicate) {
            if (!data[index]) { data.splice(index, 0, {}); }
            data[index][keyValue] = valValue;
        }

        dataField.value(escapeUnicode(JSON.stringify(data)));
        body.emit('change', { target: dataField });

    }, true);

});

module.exports = {};
