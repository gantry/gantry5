"use strict";
var DragEvents = require('./drag.events'),
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
    $          = require('../utils/elements.moofx');

require('elements/events');
require('elements/delegation');

var Resizer = new prime({
    mixin: [Bound, Options],
    EVENTS: DragEvents,
    options: {},
    constructor: function(container, options) {
        this.setOptions(options);
        this.history = this.options.history;
        this.builder = this.options.builder;
        this.map = this.builder.map;
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
        return get(this.map, isString(element) ? element : $(element).data('lm-id') || '');
    },

    getAttribute: function(element, prop) {
        return this.getBlock(element).getAttribute(prop);
    },

    getSize: function(element) {
        return this.getAttribute($(element), 'size');
    },

    start: function(event, element, siblings) {
        this.map = this.builder.map;
        if (event.which && event.which !== 1) { return true; }

        // stops text selection
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
            x: event.changedTouches ? event.changedTouches[0].pageX : event.pageX,
            y: event.changedTouches ? event.changedTouches[0].pageY : event.pageY
        };

        var clientRect = this.element[0].getBoundingClientRect(),
            parentRect = this.element.parent()[0].getBoundingClientRect();

        this.origin.offset = {
            clientRect: clientRect,
            parentRect: parentRect,
            x: this.origin.x - clientRect.right,
            y: clientRect.top - this.origin.y
        };

        this.origin.offset.parentRect.left = this.element.parent().find('> [data-lm-id]:first-child')[0].getBoundingClientRect().left;
        this.origin.offset.parentRect.right = this.element.parent().find('> [data-lm-id]:last-child')[0].getBoundingClientRect().right;

        $(document).on(this.EVENTS.MOVE, this.bound('move'));
        $(document).on(this.EVENTS.STOP, this.bound('stop'));
    },

    move: function(event) {
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
            value = clientX + (!this.siblings.prevs ? this.origin.offset.x : this.siblings.prevs.length),
            normalized = clamp(value, parentRect.left, parentRect.right);

        size = nMap(normalized, parentRect.left, parentRect.right, 0, 100);
        size = size - this.siblings.sizeBefore;
        size = precision(clamp(size, 0, this.origin.maxSize), 4);
        diff = precision(diff - size, 4);

        this.getBlock(this.element).setSize(size, true);
        this.getBlock(this.siblings.next).setSize(diff, true);

        this.lastX = clientX;
        this.lastY = clientY;
    },

    stop: function(/*event*/) {
        $(document).off(this.EVENTS.MOVE, this.bound('move'));
        $(document).off(this.EVENTS.STOP, this.bound('stop'));

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
