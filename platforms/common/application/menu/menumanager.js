"use strict";
var prime    = require('prime'),
    $        = require('../utils/elements.moofx'),
    zen      = require('elements/zen'),
    Emitter  = require('prime/emitter'),
    Bound    = require('prime-util/prime/bound'),
    Options  = require('prime-util/prime/options'),
    DragDrop = require('../ui/drag.drop'),
    Resizer  = require('../ui/drag.resizer'),
    get      = require('mout/object/get'),

    every    = require('mout/array/every'),
    isArray  = require('mout/lang/isArray'),
    isObject = require('mout/lang/isObject'),
    equals   = require('mout/object/equals');


var MenuManager = new prime({

    mixin: [Bound, Options],

    inherits: Emitter,

    constructor: function(element, options) {
        this.dragdrop = new DragDrop(element, options);
        this.resizer = new Resizer(element, options);
        this.dragdrop
            .on('dragdrop:click', this.bound('click'))
            .on('dragdrop:start', this.bound('start'))
            .on('dragdrop:move:once', this.bound('moveOnce'))
            .on('dragdrop:location', this.bound('location'))
            .on('dragdrop:nolocation', this.bound('nolocation'))
            .on('dragdrop:stop', this.bound('stop'))
            .on('dragdrop:stop:animation', this.bound('stopAnimation'));
        /*
         .on('dragdrop:location', this.bound('location'))
         .on('dragdrop:nolocation', this.bound('nolocation'))
         .on('dragdrop:resize', this.bound('resize'))
         .on('dragdrop:stop:erase', this.bound('removeElement'))
         .on('dragdrop:stop', this.bound('stop'))
         .on('dragdrop:stop:animation', this.bound('stopAnimation'));*/
    },

    click: function(event, element) {
        element.addClass('active').siblings().removeClass('active');
        element.emit('click');
        var link = element.find('a');
        if (link) { link[0].click(); }
    },

    start: function(event, element) {
        var root = element.parent('.menu-selector') || element.parent('.submenu-column'),
            size = $(element).position();

        this.block = null;
        this.type = element.parent('.g-main-nav') || element.matches('.g-main-nav') ? 'main' : 'columns'

        root.addClass('moving');
        var type = $(element).data('mm-id'),
            clone = element[0].cloneNode(true);

        if (!this.placeholder) { this.placeholder = zen('li.block.placeholder[data-mm-placeholder]'); }
        this.placeholder.style({ display: 'none' });
        this.original = $(clone).after(element).style({
            display: 'block',
            opacity: 1
        }).addClass('original-placeholder').data('lm-dropzone', null);
        this.originalType = type;
        this.block = element;

        element.style({
            position: 'absolute',
            zIndex: 1000,
            width: Math.ceil(size.width),
            height: Math.ceil(size.height)
        });

        this.placeholder.before(element);
    },

    moveOnce: function(element) {
        this.original.style({opacity: 0.5})
    },

    location: function(event, location, target/*, element*/) {
        target = $(target);
        if (!this.placeholder) { this.placeholder = zen('li.block.placeholder[data-mm-placeholder]').style({ display: 'none' }); }

        var position,
            dataType = target.parent('.g-main-nav') || target.matches('.g-main-nav') ? 'main' : 'columns',
            dataID = target.data('mm-id'),
            originalID = this.block.data('mm-id');

        // Check for adjacents and avoid inserting any placeholder since it would be the same position
        var exclude = ':not(.placeholder):not([data-mm-id="' + this.original.data('mm-id') + '"])',
            adjacents = {
                before: this.original.previousSiblings(exclude),
                after: this.original.nextSiblings(exclude)
            };

        if (adjacents.before) { adjacents.before = $(adjacents.before[0]); }
        if (adjacents.after) { adjacents.after = $(adjacents.after[0]); }
        if (dataType === 'main' && ((adjacents.before === target && location.x === 'after') || (adjacents.after === target && location.x === 'before'))) {
            return;
        }
        if (dataType === 'columns' && ((adjacents.before === target && location.y === 'below') || (adjacents.after === target && location.y === 'above'))) {
            return;
        }

        // handles the types cases and normalizes the locations (x and y)
        var grid, block, method;

        switch (dataType) {
            case 'main':
                /*var empty = !target.children(':not(.placeholder)');
                // new particles cannot be dropped in existing grids, only empty ones
                if (originalType !== 'grid' && !empty) { return; }

                // grids cannot be dropped inside grids
                if (originalType === 'grid' && empty) { return; }

                // we are dropping a new particle into an empty grid, placeholder goes inside
                if (empty) { this.placeholder.bottom(target); }
                else {
                    // we are sorting grids ordering, placeholder goes above/below
                    method = (location.y === 'above' ? 'before' : 'after');
                    this.placeholder[method](target);
                }*/
                /*if (!dataID) {
                    target = target.find('> :last-child');
                }*/

                this.placeholder[location.x](target);
                break;
            case 'columns':
                method = (location.y === 'above' ? 'before' : 'after');
                this.placeholder[method](target);

                break;
        }

        // If it's not a block we don't want a small version of the placeholder
        this.placeholder.removeClass('in-between').removeClass('in-between-grids').removeClass('in-between-grids-first').removeClass('in-between-grids-last');
        this.placeholder.style({ display: 'block' })[dataType !== 'main' ? 'removeClass' : 'addClass']('in-between');
        /*if (originalType === 'grid' && dataType === 'grid') {
            var next = this.placeholder.nextSibling(),
                previous = this.placeholder.previousSibling();

            this.placeholder.addClass('in-between-grids');
            if (previous && !previous.data('lm-blocktype')) { this.placeholder.addClass('in-between-grids-first'); }
            if (!next || !next.data('lm-blocktype')) { this.placeholder.addClass('in-between-grids-last'); }
        }*/
    },

    nolocation: function(event) {
        if (this.placeholder) { this.placeholder.remove(); }
    },

    stop: function(event, target, element) {
        if (!this.dragdrop.matched) {
            if (this.placeholder) { this.placeholder.remove(); }

            return;
        }

        var placeholderParent = this.placeholder.parent();
        if (!placeholderParent) { return; }

        this.original.remove();
        this.block.after(this.placeholder);
        this.placeholder.remove();
    },

    stopAnimation: function(element) {
        (element.parent('.menu-selector') || element.parent('.submenu-column')).removeClass('moving');
        this.block.attribute('style', null);
        if (this.original) { this.original.remove(); }
    }
});


module.exports = MenuManager;
