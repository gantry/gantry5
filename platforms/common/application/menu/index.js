"use strict";
var ready         = require('elements/domready'),
    MenuManager   = require('./menumanager'),
    $             = require('elements'),
    zen           = require('elements/zen'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    extraItems    = require('./extra-items'),
    request       = require('agent'),
    trim          = require('mout/string/trim'),
    clamp         = require('mout/math/clamp'),
    contains      = require('mout/array/contains'),
    indexOf       = require('mout/array/indexOf'),
    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    validateField = require('../utils/field-validation');

var menumanager;

var isFirefox = navigator.userAgent.toLowerCase().indexOf('firefox') > -1;

var FOCUSIN  = isFirefox ? 'focus' : 'focusin',
    FOCUSOUT = isFirefox ? 'blur' : 'focusout';

ready(function() {
    var body = $('body');

    menumanager = new MenuManager('[data-mm-container]', {
        delegate: '.g5-mm-particles-picker ul li, #menu-editor > section ul li, .submenu-column, .submenu-column li[data-mm-id], .column-container .g-block',
        droppables: '#menu-editor [data-mm-id]',
        exclude: '[data-lm-nodrag], .fa-cog, .config-cog',
        resize_handles: '.submenu-column:not(:last-child)',
        catchClick: true
    });


    // Handles Modules / Particles items in the Menu
    menumanager.on('dragEnd', extraItems);

    module.exports.menumanager = menumanager;

    // Menu Manager
    menumanager.setRoot();

    // Refresh ordering/items on menu type change or Menu navigation link
    body.delegate('statechangeAfter', '#main-header [data-g5-ajaxify], select.menu-select-wrap', function(/*event, element*/) {
        menumanager.setRoot();
        menumanager.refresh();

        // refresh MM eraser
        if (menumanager.eraser) {
            menumanager.eraser.element = $('[data-mm-eraseparticle]');
            menumanager.eraser.hide();
        }
    });

    body.delegate(FOCUSIN, '.percentage input', function(event, element) {
        element = $(element);
        element.currentSize = Number(element.value());

        element[0].focus();
        element[0].select();
    }, true);

    body.delegate('keydown', '.percentage input', function(event/*, element*/) {
        if (contains([46, 8, 9, 27, 13, 110, 190], event.keyCode) ||
                // Allow: [Ctrl|Cmd]+A | [Ctrl|Cmd]+R
            (event.keyCode == 65 && (event.ctrlKey === true || event.ctrlKey === true)) ||
            (event.keyCode == 82 && (event.ctrlKey === true || event.metaKey === true)) ||
                // Allow: home, end, left, right, down, up
            (event.keyCode >= 35 && event.keyCode <= 40)) {
            // let it happen, don't do anything
            return;
        }
        // Ensure that it is a number and stop the keypress
        if ((event.shiftKey || (event.keyCode < 48 || event.keyCode > 57)) && (event.keyCode < 96 || event.keyCode > 105)) {
            event.preventDefault();
        }
    });

    body.delegate('keydown', '.percentage input', function(event, element) {
        element = $(element);
        var value  = Number(element.value()),
            min    = Number(element.attribute('min')),
            max    = Number(element.attribute('max')),
            upDown = event.keyCode == 38 || event.keyCode == 40;

        if (upDown) {
            value += event.keyCode == 38 ? +1 : -1;
            value = clamp(value, min, max);
            element.value(value);
            body.emit('keyup', { target: element });
        }
    });

    body.delegate('keyup', '.percentage input', function(event, element) {
        element = $(element);
        var value = Number(element.value()),
            min   = Number(element.attribute('min')),
            max   = Number(element.attribute('max'));

        var resizer = menumanager.resizer,
            parent  = element.parent('[data-mm-id]'),
            sibling = parent.nextSibling('[data-mm-id]') || parent.previousSibling('[data-mm-id]');

        if (!value || value < min || value > max) { return; }

        var sizes = {
            current: Number(element.currentSize),
            sibling: Number(resizer.getSize(sibling))
        };

        element.currentSize = value;

        sizes.total = sizes.current + sizes.sibling;
        sizes.diff = sizes.total - value;

        resizer.setSize(parent, value);
        resizer.setSize(sibling, sizes.diff);

        menumanager.resizer.updateItemSizes(parent.parent('.submenu-selector').search('> [data-mm-id]'));
        menumanager.emit('dragEnd', menumanager.map, 'inputChange');
    });

    body.delegate(FOCUSOUT, '.percentage input', function(event, element) {
        element = $(element);
        var value = Number(element.value());
        if (value < Number(element.attribute('min')) || value > Number(element.attribute('max'))) {
            element.value(element.currentSize);
        }
    }, true);

    // Add new columns
    body.delegate('click', '.add-column', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        element = $(element);

        var container = element.parent('[data-g5-menu-columns]').find('.submenu-selector'),
            children  = container.children(),
            last      = container.find('> :last-child'),
            count     = children ? children.length : 0,
            active    = $('.menu-selector .active'),
            path      = active ? active.data('mm-id') : null;

        // do not allow to create a new column if there's already one and it's empty
        if (count == 1 && !children.search('.submenu-items > [data-mm-id]')) { return false; }

        var block = $(last[0].cloneNode(true));
        block.data('mm-id', 'list-' + count);
        block.find('.submenu-items').empty();
        block.find('[data-mm-base-level]').data('mm-base-level', 1);
        block.find('.submenu-level').text('Level 1');
        block.after(last);

        if (!menumanager.ordering[path]) {
            menumanager.ordering[path] = [[]];
        }

        menumanager.ordering[path].push([]);
        menumanager.resizer.evenResize($('.submenu-selector > [data-mm-id]'));
    });

    // Attach events to pseudo (x) for deleting a column
    ['click', 'touchend'].forEach(function(evt) {
        body.delegate(evt, '[data-g5-menu-columns] .submenu-items:empty', function(event, element) {
            var bounding = element[0].getBoundingClientRect(),
                x        = event.pageX || event.changedTouches[0].pageX || 0, y = event.pageY || event.changedTouches[0].pageY || 0,
                siblings = $('.submenu-selector > [data-mm-id]'),
                deleter  = {
                    width: 36,
                    height: 36
                };

            if (siblings.length <= 1) {
                return false;
            }

            if (x >= bounding.left + bounding.width - deleter.width && x <= bounding.left + bounding.width &&
                Math.abs(window.scrollY - y) - bounding.top < deleter.height) {
                var parent    = element.parent('[data-mm-id]'),
                    container = parent.parent('.submenu-selector').children('[data-mm-id]'),
                    index     = indexOf(container, parent),
                    active    = $('.menu-selector .active'),
                    path      = active ? active.data('mm-id') : null;

                parent.remove();
                siblings = $('.submenu-selector > [data-mm-id]');
                menumanager.ordering[path].splice(index, 1);
                menumanager.resizer.evenResize(siblings);
            }
        });
    });

    // Menu Items settings
    body.delegate('click', '#menu-editor .config-cog, #menu-editor .global-menu-settings', function(event, element) {
        event.preventDefault();

        var data = {}, isRoot = element.hasClass('global-menu-settings');

        if (isRoot) {
            data.settings = JSON.stringify(menumanager.settings);
        } else {
            data.item = JSON.stringify(menumanager.items[element.parent('[data-mm-id]').data('mm-id')]);
        }

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            overlayClickToClose: false,
            remote: parseAjaxURI($(element).attribute('href') + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }
                
                var form       = content.elements.content.find('form'),
                    fakeDOM    = zen('div').html(response.body.html).find('form'),
                    submit     = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]'),
                    dataString = [], invalid = [],
                    path;

                var search      = content.elements.content.find('.search input'),
                    blocks      = content.elements.content.search('[data-mm-type]'),
                    filters     = content.elements.content.search('[data-mm-filter]'),
                    urlTemplate = content.elements.content.find('.g-urltemplate');

                if (urlTemplate) { body.emit('input', { target: urlTemplate }); }

                var editable = content.elements.content.find('[data-title-editable]');
                if (editable) {
                    editable.on('title-edit-end', function(title, original/*, canceled*/) {
                        title = trim(title);
                        if (!title) {
                            title = trim(original) || 'Title';
                            this.text(title).data('title-editable', title);

                            return true;
                        }
                    });
                }

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
                }

                if (search) {
                    setTimeout(function() {
                        search[0].focus();
                    }, 5);
                }

                if ((!form && !fakeDOM) || !submit) { return true; }

                // Menuitems Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();

                    var target = $(e.target);
                    target.disabled(true);

                    dataString = [];
                    invalid = [];

                    target.hideIndicator();
                    target.showIndicator();

                    $(fakeDOM[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name');
                        if (!name) { return; }

                        input = content.elements.content.find('[name="' + name + '"]');

                        if (!validateField(input)) { invalid.push(input); }
                        dataString.push(name + '=' + encodeURIComponent(input.value()));
                    });

                    var title = content.elements.content.find('[data-title-editable]');
                    if (title) {
                        dataString.push((isRoot ? 'settings[title]' : 'title') + '=' + encodeURIComponent(title.data('title-editable')));
                    }

                    if (invalid.length) {
                        target.disabled(false);
                        target.hideIndicator();
                        target.showIndicator('fa fa-fw fa-exclamation-triangle');
                        toastr.error('Please review the fields in the modal and ensure you correct any invalid one.', 'Invalid Fields');
                        return;
                    }

                    request(fakeDOM.attribute('method'), parseAjaxURI(fakeDOM.attribute('action') + getAjaxSuffix()), dataString.join('&'), function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            if (response.body.path || (response.body.item && response.body.item.type == 'particle')) {
                                path = response.body.path || element.parent('[data-mm-id]').data('mm-id');
                                menumanager.items[path] = response.body.item;
                            } else if (response.body.item && response.body.item.type == 'particle') {

                            } else {
                                menumanager.settings = response.body.settings;
                            }

                            if (response.body.html) {
                                var parent = element.parent('[data-mm-id]');
                                if (parent) { parent.html(response.body.html); }
                            }

                            menumanager.emit('dragEnd', menumanager.map);

                            // if it's apply and save we also save the panel
                            if (target.data('apply-and-save') !== null) {
                                var save = $('body').find('.button-save');
                                if (save) { body.emit('click', { target: save }); }
                            }

                            modal.close();
                            toastr.success('The Menu Item settings have been applied to the Main Menu. <br />Remember to click the Save button to store them.', 'Settings Applied');
                        }

                        target.hideIndicator();
                    });
                });
            }
        });
    });
});

module.exports = {
    menumanager: menumanager
};
