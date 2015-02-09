"use strict";
var ready       = require('elements/domready'),
    MenuManager = require('./menumanager'),
    zen         = require('elements/zen'),
    $           = require('elements');

var menumanager = new MenuManager('body', {
    delegate: '#menu-editor > section ul li, .submenu-column li, .column-container .g-block',
    droppables: '#menu-editor [data-mm-id]',
    exclude: '[data-lm-nodrag], .fa-cog, .config-cog',
    resize_handles: '.submenu-column li:not(:last-child)',
    catchClick: true
});

ready(function() {
    var body = $('body');

    // Menu Manager
    menumanager.setRoot();

    // Sub-navigation links
    body.delegate('statechangeAfter', '#main-header [data-g5-ajaxify]', function(event, element) {
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
});

module.exports = {
    menumanager: menumanager
};