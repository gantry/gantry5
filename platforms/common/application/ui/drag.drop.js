"use strict";

var prime      = require('prime'),
    Emitter    = require('prime/emitter'),
    Bound      = require('prime-util/prime/bound'),
    Options    = require('prime-util/prime/options'),
    bind       = require('mout/function/bind'),
    contains   = require('mout/array/contains'),
    DragEvents = require('./drag.events'),
    $          = require('../utils/elements.utils');

// $ utils
require('elements/events');
require('elements/delegation');

var isIE = (navigator.appName === "Microsoft Internet Explorer");

var DragDrop = new prime({

    mixin: [Bound, Options],
    inherits: Emitter,

    options: {
        delegate: null,
        droppables: false,
        catchClick: false
    },

    DRAG_EVENTS: DragEvents,

    constructor: function(container, options) {
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

    attach: function() {
        this.DRAG_EVENTS.EVENTS.START.forEach(bind(function(event) {
            this.container.delegate(event, this.options.delegate, this.bound('start'));
        }, this));
    },

    detach: function() {
        this.DRAG_EVENTS.EVENTS.START.forEach(bind(function(event) {
            this.container.undelegate(event, this.options.delegate, this.bound('start'));
        }, this));
    },

    start: function(event, element) {
        //if (event && event.type.match(/^touch/i)) { event.preventDefault(); }

        clearTimeout(this.scrollInterval);
        if (element.LMTooltip) { element.LMTooltip.remove(); }
        $('html').attribute('style', 'height: 100% !important');
        this.scrollHeight = document.body.scrollHeight;

        // Prevents dragging a column from itself and limiting to its handle
        var target = $(event.target);
        if (!element.parent('[data-lm-root]') && element.hasClass('g-block') && (!target.matches('.submenu-reorder') && !target.parent('.submenu-reorder'))) { return true; }

        if (event.which && event.which !== 1 || $(event.target).matches(this.options.exclude)) { return true; }
        this.element = $(element);
        this.original = this.element;
        this.matched = false;
        if (this.options.catchClick) { this.moved = false; }

        // we force the menu column reorder handle to the g-block parent
        if (target.matches('.submenu-reorder') || target.parent('.submenu-reorder')) {
            this.element = target.parent('[data-mm-id]');
        }

        this.emit('dragdrop:beforestart', event, this.element);

        // Stops default MS touch actions since preventDefault doesn't work
        if (isIE) {
            this.element.style({
                '-ms-touch-action': 'none',
                'touch-action': 'none'
            });
        }

        // Stops text selection
        event.preventDefault();

        this.origin = {
            x: event.changedTouches ? event.changedTouches[0].pageX : event.pageX,
            y: event.changedTouches ? event.changedTouches[0].pageY : event.pageY,
            transform: this.element.compute('transform')
        };

        var clientRect = this.element[0].getBoundingClientRect();
        this.origin.offset = {
            clientRect: clientRect,
            scroll: {
                x: window.scrollX,
                y: window.scrollY
            },
            x: this.origin.x - clientRect.right,
            y: clientRect.top - this.origin.y
        };

        // Only allow to sort grids when targeting the left handle
        if (this.element.data('lm-blocktype') === 'grid' && Math.abs(this.origin.offset.x) < clientRect.width) {
            return false;
        }

        var offset  = Math.abs(this.origin.offset.x),
            columns = (this.element.parent().data('lm-blocktype') === 'grid' && this.element.parent().parent().data('lm-root')) ||
                (this.element.parent().parent().data('lm-blocktype') == 'container' && (this.element.parent().parent().parent().data('lm-root') || this.element.parent().parent().parent().data('lm-blocktype') == 'wrapper'));

        if (
            this.element.data('lm-blocktype') == 'grid' &&
            (this.element.parent().data('lm-blocktype') === 'container' && this.element.parent().parent().parent().data('lm-root')) ||
            (this.element.parent().data('lm-blocktype') === 'section' && this.element.parent().parent().parent().data('lm-root'))
        ) { columns = false; }

        // Resizing and only if it's not a non-visible (atoms) section
        if ((offset < 6 && this.element.parent().find(':last-child') !== this.element) || (columns && offset > 3 && offset < 10)) {
            if (this.element.parent('[data-lm-blocktype="atoms"]')) { return false; }

            this.emit('dragdrop:resize', event, this.element, (this.element.parent('[data-mm-id]') || this.element).siblings(':not(.placeholder):not(.g-grid-settings)'), this.origin.offset.x);
            return false;
        }

        if (columns || (element.hasClass('submenu-column') && (!target.matches('.submenu-reorder') && !target.parent('.submenu-reorder')))) { return true; }

        this.element.style({
            'pointer-events': 'none',
            zIndex: 100
        });

        this.DRAG_EVENTS.EVENTS.MOVE.forEach(bind(function(event) {
            $('body').on(event, this.bound('move'));
        }, this));

        this.DRAG_EVENTS.EVENTS.STOP.forEach(bind(function(event) {
            // Trackpads `tap` (mousedown + mouseup) happens too fast and the stop event
            // won't get attached. We need to defer it to avoid issues

            $('body').on(event, this.bound('deferStop'));
        }, this));

        this.emit('dragdrop:start', event, this.element);

        return this.element;
    },

    deferStop: function(event) {
        var self = this;
        setTimeout(function() {
            self.stop(event);
        }, 0);
    },

    stop: function(event) {
        //if (event && event.type.match(/^touch/i)) { event.preventDefault(); }

        clearTimeout(this.scrollInterval);
        $('html').attribute('style', null);
        if (!this.moved && this.options.catchClick) {
            // this is just a click
            this.element.style({ transform: this.origin.transform || 'translate(0, 0)' });
            this.emit('dragdrop:stop', event, this.matched, this.element);
            this._removeStyleAttribute(this.element);
            this.emit('dragdrop:stop:animation', this.element);
            this.emit('dragdrop:click', event, this.element);

            this.DRAG_EVENTS.EVENTS.MOVE.forEach(bind(function(event) {
                $('body').off(event, this.bound('move'));
            }, this));

            this.DRAG_EVENTS.EVENTS.STOP.forEach(bind(function(event) {
                $('body').off(event, this.bound('deferStop'));
            }, this));

            this.element = null;

            return;
        }

        var settings = { duration: '250ms' };

        if (this.removeElement) {
            this.DRAG_EVENTS.EVENTS.MOVE.forEach(bind(function(event) {
                $('body').off(event, this.bound('move'));
            }, this));

            this.DRAG_EVENTS.EVENTS.STOP.forEach(bind(function(event) {
                $('body').off(event, this.bound('deferStop'));
            }, this));

            return this.emit('dragdrop:stop:erase', event, this.element);
        }

        if (this.element) {

            this.emit('dragdrop:stop', event, this.matched, this.element);

            if (this.matched) {
                this.element.style({
                    opacity: 0,
                    transform: 'translate(0, 0)'
                }).removeClass('active');
            }

            if (!this.matched) {

                settings.callback = bind(function(element) {
                    this._removeStyleAttribute(element);
                    setTimeout(bind(function() {
                        this.emit('dragdrop:stop:animation', element);
                    }, this), 1);
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

        this.DRAG_EVENTS.EVENTS.MOVE.forEach(bind(function(event) {
            $('body').off(event, this.bound('move'));
        }, this));

        this.DRAG_EVENTS.EVENTS.STOP.forEach(bind(function(event) {
            $('body').off(event, this.bound('deferStop'));
        }, this));

        this.element = null;
    },

    move: function(event) {
        //if (event && event.type.match(/^touch/i)) { event.preventDefault(); }

        if (this.options.catchClick) {
            var didItMove = {
                x: event.changedTouches ? event.changedTouches[0].pageX : event.pageX,
                y: event.changedTouches ? event.changedTouches[0].pageY : event.pageY
            };

            if (Math.abs(didItMove.x - this.origin.x) <= 3 && Math.abs(didItMove.y - this.origin.y) <= 3) {
                return;
            }

            if (!this.moved) {
                this.element.style({ opacity: 0.5 });
                this.emit('dragdrop:move:once', this.element);
            }

            this.moved = true;
        }

        var clientX = event.clientX || (event.touches && event.touches[0].clientX) || 0,
            clientY = event.clientY || (event.touches && event.touches[0].clientY) || 0,
            overing = document.elementFromPoint(clientX, clientY),
            isGrid  = this.element.data('lm-blocktype') === 'grid';


        // Logic to auto-scroll on drag
        var scrollHeight = this.scrollHeight,
            Height       = document.body.clientHeight,
            Scroll       = window.pageYOffset;

        clearTimeout(this.scrollInterval);
        if (!overing) { return; }

        if (!$(overing).matches('#trash') && !$(overing).parent('#trash')) {
            var st, sl, trash = $('#g5-container #trash');
            if (clientY + 50 >= Height && Scroll + Height < scrollHeight) {
                this.scrollInterval = setInterval(function() {
                    sl = (window.pageXOffset || document.documentElement.scrollLeft) - (document.documentElement.clientLeft || 0);
                    st = (window.pageYOffset || document.documentElement.scrollTop) - (document.documentElement.clientTop || 0);
                    window.scrollTo(sl, Math.min(scrollHeight, st + 4));
                }, 8);
            } else if (clientY - 50 <= (trash ? trash[0].offsetHeight : 0) && scrollHeight > 0) {
                this.scrollInterval = setInterval(function() {
                    sl = (window.pageXOffset || document.documentElement.scrollLeft) - (document.documentElement.clientLeft || 0);
                    st = (window.pageYOffset || document.documentElement.scrollTop) - (document.documentElement.clientTop || 0);
                    window.scrollTo(sl, Math.max(0, st - 4));
                }, 8);
            }
        }

        // We tweak the overing to take into account the negative offset for the handle
        if (isGrid) {
            // More accurate is: clientX + (this.element[0].getBoundingClientRect().left - clientX)
            overing = document.elementFromPoint(clientX + 30, clientY);
        }

        if (!overing) { return false; }

        this.matched = $(overing).matches(this.options.droppables) ? overing : ($(overing).parent(this.options.droppables) || [false])[0];
        this.isPlaceHolder = $(overing).matches('[data-lm-placeholder]') ? true : ($(overing).parent('[data-lm-placeholder]') ? true : false);

        var deltaX    = this.lastX - clientX,
            deltaY    = this.lastY - clientY,
            direction = Math.abs(deltaX) > Math.abs(deltaY) && deltaX > 0 && 'left' ||
                Math.abs(deltaX) > Math.abs(deltaY) && deltaX < 0 && 'right' ||
                Math.abs(deltaY) > Math.abs(deltaX) && deltaY > 0 && 'up' ||
                'down';


        deltaX = (event.changedTouches ? event.changedTouches[0].pageX : event.pageX) - this.origin.x;
        deltaY = (event.changedTouches ? event.changedTouches[0].pageY : event.pageY) - this.origin.y;

        var isNew = this.element.parent('.particles-container');
        if (isNew) {
            deltaY += this.origin.offset.scroll.y - window.scrollY;
        }

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
                // Note: you can divide x axis by 3 rather than 2 for 4 directions
                var location = {
                    x: Math.abs((clientX - rect.left)) < (rect.width / 2) && 'before' ||
                    Math.abs((clientX - rect.left)) >= (rect.width - (rect.width / 2)) && 'after' ||
                    'other',
                    y: Math.abs((clientY - rect.top)) < (rect.height / 2) && 'above' ||
                    Math.abs((clientY - rect.top)) >= (rect.height / 2) && 'below' ||
                    'other'
                };

                this.emit('dragdrop:location', event, location, this.matched, this.element);
            } else {
                this.emit('dragdrop:nolocation', event);
            }
        }

        this.lastOvered = overing;
        this.lastX = clientX;
        this.lastY = clientY;

        this.emit('dragdrop:move', event, this.element);
    },

    _removeStyleAttribute: function(element) {
        element = $(element || this.element);
        if (element.data('mm-id')) { return; }

        element.attribute('style', null);//.style({flex: flex});
    }

});

module.exports = DragDrop;
