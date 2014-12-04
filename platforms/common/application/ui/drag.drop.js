"use strict";

var prime      = require('prime'),
    Emitter    = require('prime/emitter'),
    Bound      = require('prime-util/prime/bound'),
    Options    = require('prime-util/prime/options'),
    bind       = require('mout/function/bind'),
    contains   = require('mout/array/contains'),
    DragEvents = require('./drag.events'),
    $          = require('../utils/elements.moofx');
// $ utils
require('elements/events');
require('elements/delegation');
//require('elements/insertion');
//require('elements/attributes');

var isIE = (navigator.appName === "Microsoft Internet Explorer");

var DragDrop = new prime({

    mixin: [Bound, Options],

    inherits: Emitter,

    options: {
        delegate: null,
        droppables: false
    },

    EVENTS: DragEvents,

    constructor: function (container, options) {
        this.container = $(container);
        if (!this.container) { return; }
        this.setOptions(options);

        this.element = null;
        this.origin = {
            x: 0,
            y: 0,
            transform: null,
            offset: {
                x: 0,
                y: 0
            }
        };

        this.matched = false;
        this.lastMatched = false;
        this.lastOvered = null;

        this.attach();
    },

    attach: function () {
        this.container.delegate(this.EVENTS.START, this.options.delegate, this.bound('start'));
    },

    detach: function () {
        this.container.undelegate(this.EVENTS.START, this.options.delegate, this.bound('start'));
    },

    start: function (event, element) {
        if (event.which && event.which !== 1 || $(event.target).matches(this.options.exclude)) { return true; }
        this.element = $(element);
        this.matched = false;

        this.emit('dragdrop:beforestart', event, this.element);

        // stops default MS touch actions since preventDefault doesn't work
        if (isIE) {
            this.element.style({
                '-ms-touch-action': 'none',
                'touch-action': 'none'
            });
        }

        // stops text selection
        event.preventDefault();

        this.origin = {
            x: event.changedTouches ? event.changedTouches[0].pageX : event.pageX,
            y: event.changedTouches ? event.changedTouches[0].pageY : event.pageY,
            transform: this.element.compute('transform')
        };

        var clientRect = this.element[0].getBoundingClientRect();
        this.origin.offset = {
            clientRect: clientRect,
            x: this.origin.x - clientRect.right,
            y: clientRect.top - this.origin.y
        };

        if (Math.abs(this.origin.offset.x) < 4) {
            this.emit('dragdrop:resize', event, this.element, this.element.siblings(':not(.placeholder)'));
            return false;
        }

        this.element.style({
            'pointer-events': 'none',
            opacity: 0.5,
            zIndex: 100
        });

        $(document).on(this.EVENTS.MOVE, this.bound('move'));
        $(document).on(this.EVENTS.STOP, this.bound('stop'));
        this.emit('dragdrop:start', event, this.element);

        return this.element;
    },

    stopANIMATED: function (event) {
        var settings = { duration: '250ms' };

        if (this.removeElement) { return this.emit('dragdrop:stop:erase', event, this.element); }

        if (this.element) {

            this.emit('dragdrop:stop', event, this.matched, this.element);

            /*this.element.style({
             position: 'absolute',
             width: 'auto',
             height: 'auto'
             });*/

            if (this.matched) {
                this.element.style({
                    opacity: 0,
                    transform: 'translate(0, 0)'
                });
            }
            settings.callback = bind(function (element) {
                this._removeStyleAttribute(element);
                this.emit('dragdrop:stop:animation', element);
            }, this, this.element);

            this.element.animate({
                transform: this.origin.transform || 'translate(0, 0)',
                opacity: 1
            }, settings);
        }

        $(document).off(this.EVENTS.MOVE, this.bound('move'));
        $(document).off(this.EVENTS.STOP, this.bound('stop'));
        this.element = null;
    },

    stop: function (event) {
        var settings = { duration: '250ms' };

        if (this.removeElement) { return this.emit('dragdrop:stop:erase', event, this.element); }

        if (this.element) {

            this.emit('dragdrop:stop', event, this.matched, this.element);

            /*this.element.style({
             position: 'absolute',
             width: 'auto',
             height: 'auto'
             });*/

            if (this.matched) {
                this.element.style({
                    opacity: 0,
                    transform: 'translate(0, 0)'
                });
            }

            if (!this.matched) {

                settings.callback = bind(function (element) {
                    this._removeStyleAttribute(element);
                    this.emit('dragdrop:stop:animation', element);
                }, this, this.element);

                this.element.animate({
                    transform: this.origin.transform || 'translate(0, 0)',
                    opacity: 1
                }, settings);
            } else {

                this.element.style({
                    transform: this.origin.transform || 'translate(0, 0)',
                    opacity: 1
                });

                this._removeStyleAttribute(this.element);
                this.emit('dragdrop:stop:animation', this.element);
            }
        }

        $(document).off(this.EVENTS.MOVE, this.bound('move'));
        $(document).off(this.EVENTS.STOP, this.bound('stop'));
        this.element = null;
    },

    move: function (event) {
        var clientX = event.clientX || (event.touches && event.touches[0].clientX) || 0,
            clientY = event.clientY || (event.touches && event.touches[0].clientY) || 0,
            overing = document.elementFromPoint(clientX, clientY);

        if (!overing) { return false; }

        this.matched = $(overing).matches(this.options.droppables) ? overing : ($(overing).parent(this.options.droppables) || [false])[0];
        this.isPlaceHolder = $(overing).matches('[data-lm-placeholder]') ? true : ($(overing).parent('[data-lm-placeholder]') ? true : false);

        // we only allow new particles to go anywhere and particles to reposition within the grid boundaries
        // and we only allow grids sorting within the same section only
        if (this.matched && this.element.data('lm-id')) {
            if ($(this.matched).parent('.grid') !== this.element.parent('.grid') || $(this.matched).parent('.section') !== this.element.parent('.section')) {
                this.matched = false;
            }
        }

        var deltaX = this.lastX - clientX,
            deltaY = this.lastY - clientY,
            direction = Math.abs(deltaX) > Math.abs(deltaY) && deltaX > 0 && 'left' ||
                Math.abs(deltaX) > Math.abs(deltaY) && deltaX < 0 && 'right' ||
                Math.abs(deltaY) > Math.abs(deltaX) && deltaY > 0 && 'up' ||
                'down';


        deltaX = (event.changedTouches ? event.changedTouches[0].pageX : event.pageX) - this.origin.x;
        deltaY = (event.changedTouches ? event.changedTouches[0].pageY : event.pageY) - this.origin.y;

        //console.log('x', this.origin.x, 'y', this.origin.y, 'ox', this.origin.offset.x, 'oy', this.origin.offset.y, 'dx', deltaX, 'dy', deltaY);

        this.direction = direction;
        this.element.style({ transform: 'translate(' + deltaX + 'px, ' + deltaY + 'px)' });

        if (!this.isPlaceHolder) {
            if (this.lastMatched && this.matched !== this.lastMatched) {
                this.emit('dragdrop:leave', event, this.lastMatched, this.element);
                this.lastMatched = false;
            }

            if (this.matched && this.matched !== this.lastMatched && overing !== this.lastOvered) {
                this.emit('dragdrop:enter', event, this.matched, this.element);
                this.lastMatched = this.matched;
            }

            if (this.matched && this.lastMatched) {
                var rect = this.matched.getBoundingClientRect();
                var x = clientX - rect.left,
                    y = clientY - rect.top;

                // divide x axis by 3 rather than 2 for 4 directions
                var location = {
                    x: Math.abs((clientX - rect.left)) < (rect.width / 2) && 'before' ||
                    Math.abs((clientX - rect.left)) >= (rect.width - (rect.width / 2)) && 'after' ||
                    'other',
                    y: Math.abs((clientY - rect.top)) < (rect.height / 2) && 'above' ||
                    Math.abs((clientY - rect.top)) >= (rect.height / 2) && 'below' ||
                    'other'
                };

                //if (!equals(location, this.lastLocation)){
                this.emit('dragdrop:location', event, location, this.matched, this.element);
                this.lastLocation = location;
                //}
            } else {
                this.emit('dragdrop:nolocation', event);
            }
        }

        this.lastOvered = overing;
        this.lastDirection = direction;
        this.lastX = clientX;
        this.lastY = clientY;

        this.emit('dragdrop:move', event, this.element);
    },

    _removeStyleAttribute: function (element) {
        //var flex = $(element).compute('flex');
        $(element || this.element).attribute('style', null);//.style({flex: flex});
        //$(element || this.element).style({'pointer-events': 'auto', 'position': 'inherit', 'z-index': 'inherit'});
    }

});

module.exports = DragDrop;
