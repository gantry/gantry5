"use strict";
var DragEvents = require('../ui/drag.events'),
    prime      = require('prime'),
    Emitter    = require('prime/emitter'),
    Bound      = require('prime-util/prime/bound'),
    Options    = require('prime-util/prime/options'),
    bind       = require('mout/function/bind'),
    isString   = require('mout/lang/isString'),
    nMap       = require('mout/math/map'),
    clamp      = require('mout/math/clamp'),
    precision  = require('mout/number/enforcePrecision'),
    get        = require('mout/object/get'),
    $          = require('../utils/elements.utils');

require('elements/events');
require('elements/delegation');

var Resizer = new prime({
    mixin: [Bound, Options],
    DRAG_EVENTS: DragEvents,
    options: {
        minSize: 5
    },
    constructor: function(container, options) {
        this.setOptions(options);
        this.history = this.options.history || {};
        this.builder = this.options.builder || {};
        this.origin = {
            x: 0,
            y: 0,
            transform: null,
            offset: {
                x: 0,
                y: 0
            }
        };
    },

    getBlock: function(element) {
        return get(this.builder.map, isString(element) ? element : $(element).data('lm-id') || '');
    },

    getAttribute: function(element, prop) {
        return this.getBlock(element).getAttribute(prop);
    },

    getSize: function(element) {
        return this.getAttribute($(element), 'size');
    },

    start: function(event, element, siblings, offset) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        
        if (element.LMTooltip) { element.LMTooltip.remove(); }
        if (event.which && event.which !== 1) { return true; }

        // Stops text selection
        event.preventDefault();

        this.element = $(element);
        this.siblings = {
            occupied: 0,
            elements: siblings,
            next: this.element.nextSibling(),
            prevs: this.element.previousSiblings(),
            sizeBefore: 0
        };

        if (this.siblings.elements.length > 1) {
            this.siblings.occupied -= this.getSize(this.siblings.next);
            this.siblings.elements.forEach(function(sibling) {
                this.siblings.occupied += this.getSize(sibling);
            }, this);
        }

        if (this.siblings.prevs) {
            this.siblings.prevs.forEach(function(sibling) {
                this.siblings.sizeBefore += this.getSize(sibling);
            }, this);
        }

        this.origin = {
            size: this.getSize(this.element),
            maxSize: this.getSize(this.element) + this.getSize(this.siblings.next),
            x: event.changedTouches ? event.changedTouches[0].pageX : event.pageX + 6,
            y: event.changedTouches ? event.changedTouches[0].pageY : event.pageY
        };

        var clientRect = this.element[0].getBoundingClientRect(),
            parentRect = this.element.parent()[0].getBoundingClientRect();

        this.origin.offset = {
            clientRect: clientRect,
            parentRect: {left: parentRect.left, right: parentRect.right},
            x: this.origin.x - clientRect.right,
            y: clientRect.top - this.origin.y,
            down: offset
        };

        this.origin.offset.parentRect.left = this.element.parent().find('> [data-lm-id]:first-child')[0].getBoundingClientRect().left;
        this.origin.offset.parentRect.right = this.element.parent().find('> [data-lm-id]:last-child')[0].getBoundingClientRect().right;

        this.DRAG_EVENTS.EVENTS.MOVE.forEach(bind(function(event) {
            $(document).on(event, this.bound('move'));
        }, this));

        this.DRAG_EVENTS.EVENTS.STOP.forEach(bind(function(event) {
            $(document).on(event, this.bound('stop'));
        }, this));
    },

    move: function(event) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }

        var clientX = event.clientX || event.touches[0].clientX || 0,
            clientY = event.clientY || event.touches[0].clientY || 0,
            parentRect = this.origin.offset.parentRect;

        var deltaX = (this.lastX || clientX) - clientX,
            deltaY = (this.lastY || clientY) - clientY;

        this.direction =
            Math.abs(deltaX) > Math.abs(deltaY) && deltaX > 0 && 'left' ||
            Math.abs(deltaX) > Math.abs(deltaY) && deltaX < 0 && 'right' ||
            Math.abs(deltaY) > Math.abs(deltaX) && deltaY > 0 && 'up' ||
                                                                 'down';
        var size,
            diff = 100 - this.siblings.occupied,
            value = clientX + (!this.siblings.prevs ? this.origin.offset.x - this.origin.offset.down : this.siblings.prevs.length),
            normalized = clamp(value, parentRect.left, parentRect.right);

        size = nMap(normalized, parentRect.left, parentRect.right, 0, 100);
        size = size - this.siblings.sizeBefore;
        size = precision(clamp(size, this.options.minSize, this.origin.maxSize - this.options.minSize), 0);

        //grids?
        //console.log((size / 12) * (100 / 12));

        diff = precision(diff - size, 0);

        this.getBlock(this.element).setSize(size, true);
        this.getBlock(this.siblings.next).setSize(diff, true);

        // Hack to handle cases where size is not an integer
        var siblings = this.element.siblings(),
            amount = siblings ? siblings.length + 1 : 1;
        if (amount == 3 || amount == 6 || amount == 7 || amount == 8 || amount == 9 || amount == 11 || amount == 12) {
            var total = 0, blocks;

            blocks = $([siblings, this.element]);
            blocks.forEach(function(block, index){
                block = this.getBlock(block);
                size = block.getSize();
                if (size % 1) {
                    size = precision(100 / amount, 0);
                    block.setSize(size, true);
                }

                total += size;

                if (blocks.length == index + 1 && total != 100) {
                    diff = 100 - total;
                    block.setSize(size + diff, true);
                }

            }, this);
        }

        this.lastX = clientX;
        this.lastY = clientY;
    },

    stop: function(event) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }

        this.DRAG_EVENTS.EVENTS.MOVE.forEach(bind(function(event) {
            $(document).off(event, this.bound('move'));
        }, this));

        this.DRAG_EVENTS.EVENTS.STOP.forEach(bind(function(event) {
            $(document).off(event, this.bound('move'));
        }, this));

        if (this.origin.size !== this.getSize(this.element)) { this.history.push(this.builder.serialize()); }
    },

    evenResize: function(elements, animated) {
        var total = elements.length,
            size = precision(100 / total, 4),
            block;

        if (typeof animated === 'undefined') { animated = true; }

        elements.forEach(function(element) {
            element = $(element);
            block = this.getBlock(element);
            if (block && block.hasAttribute('size')) {
                block[animated ? 'setAnimatedSize' : 'setSize'](size, size !== block.getSize());
            } else {
                if (element) { element[animated ? 'animate' : 'style']({ flex: '0 1 ' + size + '%' }); }
            }
        }, this);
    }
});

module.exports = Resizer;
