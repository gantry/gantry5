'use strict';
var $             = require('elements'),
    ready         = require('elements/domready'),

    zen           = require('elements/zen'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    /*lastItem      = require('mout/array/last'),
     indexOf       = require('mout/array/indexOf'),*/
    indexOf       = require('mout/array/indexOf'),
    simpleSort    = require('sortablejs'),

    trim          = require('mout/string/trim'),

    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    validateField = require('../utils/field-validation');

var AtomsField   = '[name="page[head][atoms][_json]"]',
    groupOptions = [
        { name: 'atoms', pull: 'clone', put: false },
        { name: 'atoms', pull: false, put: true }
    ];

var Atoms = {
    lists: {
        picker: null,
        items: null
    },

    serialize: function() {
        var output = [],
            list   = $('.atoms-list [data-atom-picked]');

        if (!list) { return false; }

        list.forEach(function(item) {
            item = $(item);
            output.push(JSON.parse(item.data('atom-picked')));
        });

        return JSON.stringify(output).replace(/\//g, '\\/');
    },

    createSortables: function(element) {
        var list, sort;
        groupOptions.forEach(function(groupOption, i) {
            list = $('.atoms-' + (!i ? 'picker' : 'list'));
            sort = simpleSort.create(list[0], {
                sort: i > 0,
                filter: '[data-atom-ignore]',
                group: groupOption,
                scroll: false,
                forceFallback: true,
                animation: 100,

                onStart: function(event) {
                    var item = $(event.item);
                    item.addClass('atom-dragging');
                },

                onEnd: function(event) {
                    var item = $(event.item);
                    item.removeClass('atom-dragging');
                },

                onSort: function() {
                    var serial = Atoms.serialize(),
                        field  = $(AtomsField);

                    if (!field) { throw new Error('Field "' + AtomsField + '" not found in the DOM.'); }

                    field.value(serial);
                    $('body').emit('change', { target: field });
                }
            });

            Atoms.lists[!i ? 'picker' : 'items'] = sort;
            if (i) {
                element.SimpleSort = sort;
            }
        });
    }
};

var AttachSettings = function() {
    var body = $('body');

    body.delegate('click', '.atoms-list [data-atom-picked] .config-cog', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        var list      = element.parent('ul'),
            dataField = $(AtomsField),
            data      = dataField.value(),
            items     = list.search('> [data-atom-picked]'),
            item      = element.parent('[data-atom-picked]'),
            itemData  = item.data('atom-picked');

        modal.open({
            content: 'Loading',
            method: 'post',
            data: { data: itemData },
            remote: element.attribute('href') + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var form       = content.elements.content.find('form'),
                    fakeDOM    = zen('div').html(response.body.html).find('form'),
                    submit     = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]'),
                    dataString = [],
                    invalid    = [],
                    dataValue  = JSON.parse(data);

                if (modal.getAll().length > 1) {
                    var applyAndSave = content.elements.content.search('[data-apply-and-save]');
                    if (applyAndSave) { applyAndSave.remove(); }
                }

                if ((!form && !fakeDOM) || !submit) {
                    return true;
                }

                // Atom Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();

                    var target = $(e.target);

                    dataString = [];
                    invalid = [];

                    target.hideIndicator();
                    target.showIndicator();

                    $(fakeDOM[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name');
                        if (!name || input.disabled()) { return; }

                        input = content.elements.content.find('[name="' + name + '"]');
                        var value    = input.type() == 'checkbox' ? Number(input.checked()) : input.value(),
                            parent   = input.parent('.settings-param'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        if (override && !override.checked()) { return; }
                        if (!validateField(input)) { invalid.push(input); }
                        dataString.push(name + '=' + encodeURIComponent(value));
                    });

                    var title = content.elements.content.find('h4 [data-title-editable]');
                    if (title) {
                        dataString.push('title=' + encodeURIComponent(title.data('title-editable')));
                    }

                    if (invalid.length) {
                        target.hideIndicator();
                        target.showIndicator('fa fa-fw fa-exclamation-triangle');
                        toastr.error('Please review the fields in the modal and ensure you correct any invalid one.', 'Invalid Fields');
                        return;
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
                            var index = indexOf(items, item[0]);
                            dataValue[index] = response.body.item;

                            dataField.value(JSON.stringify(dataValue).replace(/\//g, '\\/'));
                            item.find('.atom-title').text(dataValue[index].title);
                            item.data('atom-picked', JSON.stringify(dataValue[index]).replace(/\//g, '\\/'));

                            // toggle enabled/disabled status as needed
                            var enabled = Number(dataValue[index].attributes.enabled);
                            item[enabled ? 'removeClass' : 'addClass']('atom-disabled');
                            item.attribute('title', enabled ? null : 'This atom has been disabled and it won\'t be rendered on front-end. You can still configure, move and delete.');

                            body.emit('change', { target: dataField });

                            // if it's apply and save we also save the panel
                            if (target.data('apply-and-save') !== null) {
                                var save = $('body').find('.button-save');
                                if (save) { body.emit('click', { target: save }); }
                            }

                            modal.close();
                            toastr.success('Ato Item updated', 'Item Updated');
                        }

                        target.hideIndicator();
                    });
                });
            }
        });
    });
};

ready(function() {
    $('body').delegate('mouseover', '#atoms', function(event, element) {
        if (!element.SimpleSort) { Atoms.createSortables(element); }
    });

    AttachSettings();
});

module.exports = Atoms;