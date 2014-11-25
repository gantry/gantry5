"use strict";
var ready         = require('elements/domready'),
    json          = require('./json_test'),
    $             = require('elements/attributes'),
    modal         = require('../ui').modal,
    request       = require('agent'),
    zen           = require('elements/zen'),

    AjaxURL       = require('../utils/ajax-uri'),

    Builder       = require('./builder'),
    History       = require('../utils/History'),
    LMHistory     = require('./history'),
    LayoutManager = require('./layoutmanager');

require('../ui/popover');

var builder, layoutmanager, lmhistory;


builder = new Builder(json);
lmhistory = new LMHistory(builder.serialize());

var particlesPopover = function(){
    $('[data-lm-addparticle]').popover({
        type: 'async',
        placement: 'left-bottom',
        width: '200',
        style: 'particles, inverse, fixed, nooverflow',
        url: AjaxURL('particles')
    });
};

ready(function() {
    var body = $('body');
    // test
    if ($('[data-lm-root]')) {
        builder.load();
        particlesPopover();
    }

    // attach events
    // Picker
    body.delegate('statechangeBefore', '[data-g5-lm-picker]', function() {
        modal.close();
    });

    body.delegate('statechangeAfter', '[data-g5-lm-picker]', function(event, element) {
        var data = JSON.parse(element.data('g5-lm-picker'));
        $('[data-g5-content]').find('.title').text(data.name);
        builder = new Builder(data.layout);
        builder.load();

        // -!- Popovers
        // particles picker
        particlesPopover();

        // refresh LM eraser
        layoutmanager.eraser.element = $('[data-lm-eraseblock]');
        layoutmanager.eraser.hide();
    });

    body.delegate('click', '[data-g5-lm-add]', function(event, element) {
        event.preventDefault();
        modal.open({
            content: 'Loading',
            remote: AjaxURL('layouts')
        });
    });

    // layoutmanager
    layoutmanager = new LayoutManager('body', {
        delegate: '[data-lm-root] .section .grid .block > [data-lm-blocktype]:not([data-lm-nodrag]) !> .block, .g5-lm-particles-picker [data-lm-blocktype]',
        droppables: '[data-lm-dropzone]',
        exclude: '.section-header .button, .lm-newblocks .float-right .button',
        resize_handles: '[data-lm-root] .grid > .block:not(:last-child)',
        builder: builder,
        history: lmhistory
    });

});

module.exports = {
    $: $,
    builder: builder,
    layoutmanager: layoutmanager,
    history: lmhistory
};