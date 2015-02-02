"use strict";
var ready       = require('elements/domready'),
    MenuManager = require('./menumanager'),
    zen         = require('elements/zen'),
    $           = require('elements');


var menumanager;


ready(function() {

    // menumanager
    menumanager = new MenuManager('body', {
        delegate: '#menu-editor > section ul li, .submenu-column li, .column-container .g-block',
        droppables: '#menu-editor [data-mm-id]',
        exclude: '[data-lm-nodrag], .fa-cog, .config-cog',
        resize_handles: '.submenu-column li:not(:last-child)',
        catchClick: true
        /*delegate: '[data-lm-root] .g-grid > .g-block > [data-lm-blocktype]:not([data-lm-nodrag]) !> .g-block, .g5-lm-particles-picker [data-lm-blocktype], [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag]), [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag])',
         droppables: '[data-lm-dropzone]',
         exclude: '.section-header .button, .lm-newblocks .float-right .button, [data-lm-nodrag]',
         resize_handles: '[data-lm-root] .g-grid > .g-block:not(:last-child)'*/
    });

    // new columns
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