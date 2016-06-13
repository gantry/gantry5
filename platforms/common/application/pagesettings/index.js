'use strict';
var $             = require('elements'),
    ready         = require('elements/domready'),

    zen           = require('elements/zen'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    Eraser        = require('../ui/eraser'),
    request       = require('agent'),
    indexOf       = require('mout/array/indexOf'),
    simpleSort    = require('sortablejs'),

    trim          = require('mout/string/trim'),

    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    validateField = require('../utils/field-validation');

var AtomsField   = '[name="page[head][atoms][_json]"]',
    groupOptions = [
        { name: 'atoms', pull: 'clone', put: false },
        { name: 'atoms', pull: true, put: true },
        { name: 'atoms', pull: false, put: false }
    ];

var Atoms = {
    eraser: null,
    lists: {
        picker: null,
        items: null
    },

    serialize: function() {
        var output = [],
            list   = $('.atoms-list'),
            atoms  = list.search('[data-atom-picked]');

        if (!atoms) {
            list.empty();
            return '[]';
        }

        atoms.forEach(function(item) {
            item = $(item);
            output.push(JSON.parse(item.data('atom-picked')));
        });

        return JSON.stringify(output).replace(/\//g, '\\/');
    },

    attachEraser: function() {
        if (Atoms.eraser) {
            Atoms.eraser.element = $('[data-atoms-erase]');
            return;
        }

        Atoms.eraser = new Eraser('[data-atoms-erase]');
    },

    createSortables: function(element) {
        var list, sort;

        Atoms.attachEraser();

        groupOptions.forEach(function(groupOption, i) {
            list = !i ? '.atoms-picker' : (i == 1 ? '.atoms-list' : '#trash');
            list = $(list);
            sort = simpleSort.create(list[0], {
                sort: i == 1,
                filter: '[data-atom-ignore]',
                group: groupOption,
                scroll: false,
                forceFallback: true,
                animation: 100,

                onStart: function(event) {
                    Atoms.attachEraser();

                    var item = $(event.item);
                    item.addClass('atom-dragging');

                    if ($(event.from).hasClass('atoms-list')) {
                        Atoms.eraser.show();
                    }
                },

                onEnd: function(event) {
                    var item = $(event.item),
                        trash = $('#trash'),
                        target = $(this.originalEvent.target),
                        touchTrash = false;

                    // workaround for touch devices
                    if (this.originalEvent.type === 'touchend') {
                        var trashSize = trash[0].getBoundingClientRect(),
                            oE        = this.originalEvent,
                            position  = (oE.pageY || oE.changedTouches[0].pageY) - window.scrollY;

                        touchTrash = position <= trashSize.height;
                    }

                    if (target.matches('#trash') || target.parent('#trash') || touchTrash) {
                        item.remove();
                        Atoms.eraser.hide();
                        this.options.onSort();
                        return;
                    }

                    item.removeClass('atom-dragging');

                    if ($(event.from).hasClass('atoms-list')) {
                        Atoms.eraser.hide();
                    }
                },

                onSort: function() {
                    var serial = Atoms.serialize(),
                        field  = $(AtomsField);

                    if (!field) { throw new Error('Field "' + AtomsField + '" not found in the DOM.'); }

                    field.value(serial);
                    $('body').emit('change', { target: field });
                },

                onOver: function(evt) {
                    if (!$(evt.from).matches('.atoms-list')) { return; }

                    var over = $(evt.newIndex);
                    if (over.matches('#trash') || over.parent('#trash')) {
                        Atoms.eraser.over();
                    } else {
                        Atoms.eraser.out();
                    }
                }
            });

            Atoms.lists[!i ? 'picker' : 'items'] = sort;
            if (i == 1) {
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
            overlayClickToClose: false,
            remote: parseAjaxURI(element.attribute('href') + getAjaxSuffix()),
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

                    // Refresh the form to collect fresh and dynamic fields
                    var formElements = content.elements.content.find('form')[0].elements;
                    $(formElements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            type = input.attribute('type');
                        if (!name || input.disabled() || (type == 'radio' && !input.checked())) { return; }

                        input = content.elements.content.find('[name="' + name + '"]' + (type == 'radio' ? ':checked' : ''));
                        var value    = input.type() == 'checkbox' ? Number(input.checked()) : input.value(),
                            parent   = input.parent('.settings-param'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        override = override || $(input.data('override-target'));

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
                            toastr.success('Atom Item updated', 'Item Updated');
                        }

                        target.hideIndicator();
                    });
                });
            }
        });
    });
};

var AttachSortableAtoms = function(atoms) {
    if (!atoms) { return; }
    if (!atoms.SimpleSort) { Atoms.createSortables(atoms); }
};

ready(function() {
    var atoms = $('#atoms');

    $('body').delegate('mouseover', '#atoms', function(event, element) {
        AttachSortableAtoms(element);
    });

    AttachSortableAtoms(atoms);
    AttachSettings();
});

module.exports = Atoms;