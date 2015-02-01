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
            .on('dragdrop:beforestart', this.bound('beforestart'))
            .on('dragdrop:start', this.bound('start'))
            .on('dragdrop:move:once', this.bound('moveOnce'))
            .on('dragdrop:location', this.bound('location'))
            .on('dragdrop:nolocation', this.bound('nolocation'))
            .on('dragdrop:resize', this.bound('resize'))
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
        if (element.hasClass('g-block')) { this.stopAnimation(); return true; }

        var siblings = element.siblings();
        element.addClass('active');
        if (siblings) { siblings.removeClass('active'); }
        element.emit('click');
        var link = element.find('a');
        if (link) { link[0].click(); }
    },

    resize: function(event, element, siblings, offset) {
        this.resizer.start(event, element, siblings, offset);
    },

    beforestart: function(event, element){
        //if (element.hasClass('submenu-reorder')) { this.dragdrop.element = element.parent('.g-block'); }
    },

    start: function(event, element) {
        var root = element.parent('.menu-selector') || element.parent('.submenu-column') || element.parent('.submenu-selector'),
            size = $(element).position();

        this.block = null;
        this.type = element.parent('.g-main-nav') || element.matches('.g-main-nav') ? 'main' : (element.matches('.g-block') ? 'column' : 'columns_items');
        this.wasActive = element.hasClass('active');
        this.root = root;

        root.addClass('moving');

        var type = $(element).data('mm-id'),
            clone = element[0].cloneNode(true);

        if (!this.placeholder) { this.placeholder = zen((this.type == 'column' ? 'div' : 'li') + '.block.placeholder[data-mm-placeholder]'); }
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
        }).addClass('active');

        this.placeholder.before(element);

        if (this.type == 'column') {
            root.search('.g-block > *').style({'pointer-events': 'none'});
        }
    },

    moveOnce: function(element) {
        if (this.original) { this.original.style({opacity: 0.5}); }
    },

    location: function(event, location, target/*, element*/) {
        target = $(target);
        if (!this.placeholder) { this.placeholder = zen((this.type == 'column' ? 'div' : 'li') + '.block.placeholder[data-mm-placeholder]').style({ display: 'none' }); }

        var targetType = target.parent('.g-toplevel') || target.matches('.g-toplevel') ? 'main' : (target.matches('.g-block') ? 'column' : 'columns_items'),
            dataID = target.data('mm-id'),
            dataLevel = target.data('mm-level'),
            originalID = this.block.data('mm-id'),
            originalLevel = this.block.data('mm-level');

        // we only allow sorting between same level items
        if (this.type !== 'column' && originalLevel !== dataLevel) { this.dragdrop.matched = false; return; }

        // ensuring columns can only be dragged before/after other columns
        if (this.type == 'column' && dataLevel) { this.dragdrop.matched = false; return; }

        // for levels > 2 we only allow sorting within the same column
        if (dataLevel > 2 && target.parent('ul') != this.block.parent('ul')) { this.dragdrop.matched = false; return; }

        // Check for adjacents and avoid inserting any placeholder since it would be the same position
        var exclude = ':not(.placeholder):not([data-mm-id="' + this.original.data('mm-id') + '"])',
            adjacents = {
                before: this.original.previousSiblings(exclude),
                after: this.original.nextSiblings(exclude)
            };

        if (adjacents.before) { adjacents.before = $(adjacents.before[0]); }
        if (adjacents.after) { adjacents.after = $(adjacents.after[0]); }
        if (targetType === 'main' && ((adjacents.before === target && location.x === 'after') || (adjacents.after === target && location.x === 'before'))) {
            return;
        }
        if (targetType === 'column' && ((adjacents.before === target && location.x === 'after') || (adjacents.after === target && location.x === 'before'))) {
            return;
        }
        if (targetType === 'columns_items' && ((adjacents.before === target && location.y === 'below') || (adjacents.after === target && location.y === 'above'))) {
            return;
        }

        // handles the types cases and normalizes the locations (x and y)
        switch (targetType) {
            case 'main':
            case 'column':
                this.placeholder[location.x](target);
                break;
            case 'columns_items':
                this.placeholder[location.y === 'above' ? 'before' : 'after'](target);

                break;
        }

        // If it's not a block we don't want a small version of the placeholder
        this.placeholder.style({ display: 'block' })[targetType !== 'main' ? 'removeClass' : 'addClass']('in-between');

    },

    nolocation: function(event) {
        if (this.placeholder) { this.placeholder.remove(); }
    },

    stop: function(event, target, element) {
        if (target) { element.removeClass('active'); }
        if (this.type == 'column') {
            this.root.search('.g-block > *').attribute('style', null);
        }

        if (!this.dragdrop.matched) {
            if (this.placeholder) { this.placeholder.remove(); }

            return;
        }

        var placeholderParent = this.placeholder.parent();
        if (!placeholderParent) { return; }

        this.original.remove();
        this.block.after(this.placeholder);
        this.placeholder.remove();
        if (this.wasActive) { element.addClass('active'); }
    },

    stopAnimation: function(element) {
        var flex = null;
        if (this.type == 'column') { flex = this.block.compute('flex'); }
        if (this.root) { this.root.removeClass('moving'); }
        if (this.block) {
            this.block.attribute('style', null);
            if (flex) { this.block.style('flex', flex); }
        }

        if (this.original) { this.original.remove(); }
        if (!this.wasActive && this.block) { this.block.removeClass('active'); }
    }
});


module.exports = MenuManager;
