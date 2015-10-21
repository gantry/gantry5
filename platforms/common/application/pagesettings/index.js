'use strict';
var $             = require('elements'),
    ready         = require('elements/domready'),

    zen           = require('elements/zen'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    /*lastItem      = require('mout/array/last'),
     indexOf       = require('mout/array/indexOf'),*/
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

        return JSON.stringify(output);
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
                animation: 150,

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
                        field  = $('[name="' + AtomsField + '"]');

                    if (!field) { throw new Error('Field "' + AtomsField + '" not found in the DOM.'); }

                    field.value(serial);
                    // check if field value is different than original and trigger indicators change
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
    $('body').delegate('click', '.atoms-list [data-atom-picked] .config-cog', function(event, element) {
            if (event && event.preventDefault) { event.preventDefault(); }

            var parent    = element.parent('[data-atom-picked]'),
                dataField = $(AtomsField),
                data      = dataField.value(),
                itemData  = parent.data('atom-picked');

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

                        var titles = content.elements.content.search('[data-title-editable]'), key;
                        if (titles) {
                            titles.forEach(function(title) {
                                title = $(title);
                                key = title.data('collection-key') || 'title';
                                dataString.push(key + '=' + encodeURIComponent(title.data('title-editable')));
                            });
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
                                if (item) { // single editing
                                    dataValue[indexOf(items, item[0])] = response.body.data;
                                } else { // multi editing
                                    dataValue = response.body.data;
                                }

                                dataField.value(JSON.stringify(dataValue));
                                body.emit('change', { target: dataField });

                                element.parent('.settings-param-field').search('ul > [data-collection-item]').forEach(function(item, index) {
                                    item = $(item);
                                    var label = item.find('[data-title-editable]'),
                                        text  = dataValue[index][item.data('collection-item')];

                                    label.data('title-editable', text).text(text);
                                });

                                // if it's apply and save we also save the panel
                                if (target.data('apply-and-save') !== null) {
                                    var save = $('body').find('.button-save');
                                    if (save) { body.emit('click', { target: save }); }
                                }

                                modal.close();
                                toastr.success('Collection Item updated', 'Item Updated');
                            }

                            target.hideIndicator();
                        });
                    });
                }
            });
        }
    );
};

ready(function() {
    $('body').delegate('mouseover', '#atoms', function(event, element) {
        if (!element.SimpleSort) { Atoms.createSortables(element); }
    });

    AttachSettings();
});

module.exports = Atoms;