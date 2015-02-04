"use strict";
var ready       = require('elements/domready'),
    MenuManager = require('./menumanager'),
    zen         = require('elements/zen'),
    $           = require('elements');

var menumanager;

ready(function() {
    // Menu Manager
    menumanager = new MenuManager('body', {
        delegate: '#menu-editor > section ul li, .submenu-column li, .column-container .g-block',
        droppables: '#menu-editor [data-mm-id]',
        exclude: '[data-lm-nodrag], .fa-cog, .config-cog',
        resize_handles: '.submenu-column li:not(:last-child)',
        catchClick: true
    });

    // New columns
    $('body').delegate('click', '.add-column', function(evet, element) {
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

});

module.exports = {
    menumanager: menumanager
};