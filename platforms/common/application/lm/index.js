"use strict";
var ready         = require('elements/domready'),
    json          = require('./json_test'),
    $             = require('elements/attributes'),
    modal         = require('../ui').modal,
    request       = require('agent'),
    zen           = require('elements/zen'),

    getAjaxSuffix = require('../utils/get-ajax-suffix'),

    Builder       = require('./builder'),
    History       = require('../utils/History'),
    LMHistory     = require('./history'),
    LayoutManager = require('./layoutmanager');

require('../ui/popover');

var builder, layoutmanager, lmhistory;


builder = new Builder(json);
lmhistory = new LMHistory(builder.serialize());

var particlesPopover = function() {
    var particles = $('[data-lm-addparticle]');
    particles.popover({
        type: 'async',
        placement: 'left-bottom',
        width: '200',
        style: 'particles, inverse, fixed, nooverflow',
        url: particles.attribute('href') + getAjaxSuffix()
    }).on('shown.popover', function(popover) {
        if (popover.$target.particleFilter) { return false; }

        var search = popover.$target.find('input[type=text]'),
            list = popover.$target.search('[data-lm-blocktype]');
        if (!search) { return false; }

        popover.$target.particleFilter = true;
        search.on('input', function(e) {
            list.style({ display: 'none' }).forEach(function(blocktype) {
                var value = this.value().toLowerCase();
                blocktype = $(blocktype);
                if (blocktype.data('lm-blocktype').toLowerCase().match(value) || blocktype.text().toLowerCase().match(value)) {
                    blocktype.style({ display: 'block' });
                }
            }, this);
        });
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
        builder.setStructure(data.layout);
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
            remote: $(element).attribute('href') + getAjaxSuffix()
        });
    });

    // layoutmanager
    layoutmanager = new LayoutManager('body', {
        delegate: '[data-lm-root] .g-grid .g-block > [data-lm-blocktype]:not([data-lm-nodrag]) !> .g-block, .g5-lm-particles-picker [data-lm-blocktype], [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag])',
        droppables: '[data-lm-dropzone]',
        exclude: '.section-header .button, .lm-newblocks .float-right .button, [data-lm-nodrag]',
        resize_handles: '[data-lm-root] .g-grid > .g-block:not(:last-child)',
        builder: builder,
        history: lmhistory
    });

    // Particles settings
    body.delegate('click', '[data-lm-settings]', function(event, element) {
        element = $(element);

        // grid is a special case, since relies on pseudo elements for sorting and settings
        // we need to check where the user clicked.
        if (element.data('lm-blocktype') === 'grid') {
            var clientX = event.clientX || (event.touches && event.touches[0].clientX) || 0,
                boundings = element[0].getBoundingClientRect();

            if (clientX + 4 - boundings.left < boundings.width) {
                return false;
            }
        }

        modal.open({
            content: 'Loading',
            remote: $(element).data('lm-settings') + getAjaxSuffix()
        });
    });

});

module.exports = {
    $: $,
    builder: builder,
    layoutmanager: layoutmanager,
    history: lmhistory
};