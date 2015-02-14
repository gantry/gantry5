"use strict";
var ready         = require('elements/domready'),
    MenuManager   = require('./menumanager'),
    $             = require('elements'),
    zen           = require('elements/zen'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    deepEquals    = require('mout/lang/deepEquals'),
    getAjaxSuffix = require('../utils/get-ajax-suffix');

var menumanager, map;

ready(function() {
    var body = $('body');

    menumanager = new MenuManager('body', {
        delegate: '#menu-editor > section ul li, .submenu-column li, .column-container .g-block',
        droppables: '#menu-editor [data-mm-id]',
        exclude: '[data-lm-nodrag], .fa-cog, .config-cog',
        resize_handles: '.submenu-column li:not(:last-child)',
        catchClick: true
    });

    menumanager.on('dragEnd', function(map) {
        var save = $('[data-save]'),
            current = {
                settings: this.settings,
                ordering: this.ordering,
                items: this.items
        };

        if (!deepEquals(map, current)) {
            save.showIndicator('fa fa-fw changes-indicator fa-circle-o');
        } else {
            save.hideIndicator();
        }
    });

    module.exports.menumanager = menumanager;

    // Menu Manager
    menumanager.setRoot();

    // Refresh ordering/items on menu type change or Menu navigation link
    body.delegate('statechangeAfter', '#main-header [data-g5-ajaxify], select.menu-select-wrap', function(event, element) {
        menumanager.setRoot();
    });

    // New columns
    body.delegate('click', '.add-column', function(evet, element) {
        event.preventDefault();
        element = $(element);

        var container = element.parent('[data-g5-menu-columns]').find('.submenu-selector'),
            children = container.children(),
            last = container.find('> :last-child'),
            count = children ? children.length : 0;

        var block = $(last[0].cloneNode(true));
        block.data('mm-id', 'list-' + (count + 1));
        block.find('.submenu-items').empty();
        block.after(last);
    });

    // Attach events to pseudo (x) for deleting a column
    body.delegate('click', '[data-g5-menu-columns] .submenu-items:empty', function(event, element) {
        var bounding = element[0].getBoundingClientRect(),
            x = event.pageX, y = event.pageY,
            deleter = {
                width: 36,
                height: 36
            };

        if (x >= bounding.left + bounding.width - deleter.width && x <= bounding.left + bounding.width &&
            y - bounding.top < deleter.height) {
            element.parent('[data-mm-id]').remove();
        }
    });

    body.delegate('click', '#menu-editor .config-cog, #menu-editor .global-menu-settings', function(event, element) {
        event.preventDefault();

        var data = {};

        if (element.hasClass('global-menu-settings')) {
            data.settings = JSON.stringify(menumanager.settings);
        } else {
            data.item = JSON.stringify(menumanager.items[element.parent('[data-mm-id]').data('mm-id')]);
        }

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: $(element).attribute('href') + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) { return true; }

                // Particle Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showIndicator();

                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) { return; }
                        dataString.push(name + '=' + value);
                    });

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            if (response.body.path) {
                                menumanager.items[response.body.path] = response.body.item;
                            } else {
                                menumanager.settings = response.body.settings;
                            }

                            if (response.body.html) {
                                var parent = element.parent('[data-mm-id]');
                                if (parent) { parent.html(response.body.html); }
                            }

                            menumanager.emit('dragEnd', menumanager.map);
                            modal.close();
                            toastr.success('The Menu Item settings have been applied to the Main Menu. <br />Remember to click the Save button to store them.', 'Settings Applied');
                        }

                        submit.hideIndicator();
                    });
                });
            }
        });
    });
});

module.exports = {
    menumanager: menumanager
};