// Offcanvas slide with desktop, touch and all-in-one touch devices support that supports both left and right placement.
// Fast and optimized using CSS3 transitions
// Based on the awesome Slideout.js <https://mango.github.io/slideout/>

"use strict";

var ready     = require('domready'),
    prime     = require('prime'),
    bind      = require('mout/function/bind'),
    forEach   = require('mout/array/forEach'),
    mapNumber = require('mout/math/map'),
    clamp     = require('mout/math/clamp'),
    decouple  = require('../utils/decouple'),
    Bound     = require('prime-util/prime/bound'),
    Options   = require('prime-util/prime/options'),
    $         = require('elements'),
    zen       = require('elements/zen');

// thanks David Walsh
var prefix = (function() {
    var styles = window.getComputedStyle(document.documentElement, ''),
        pre = (Array.prototype.slice.call(styles).join('')
            .match(/-(moz|webkit|ms)-/) || (styles.OLink === '' && ['', 'o'])
        )[1],
        dom = ('WebKit|Moz|MS|O').match(new RegExp('(' + pre + ')', 'i'))[1];
    return {
        dom: dom,
        lowercase: pre,
        css: '-' + pre + '-',
        js: pre[0].toUpperCase() + pre.substr(1)
    };
})();

var hasTouchEvents = ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch,
    isScrolling    = false, scrollTimeout;

var Offcanvas = new prime({

    mixin: [Bound, Options],

    options: {
        effect: 'ease',
        duration: 300,
        tolerance: function(padding) { // tolerance can also be just an integer value
            return padding / 2;
        },
        padding: 0,
        touch: true,

        openClass: 'g-offcanvas-open',
        overlayClass: 'g-nav-overlay'
    },

    constructor: function(options) {
        this.setOptions(options);

        this.opening = false;
        this.moved = false;
        this.dragging = false;
        this.opened = false;
        this.preventOpen = false;
        this.offsetX = {
            start: 0,
            current: 0
        };

        this.panel = $('#g-page-surround');
        this.offcanvas = $('#g-offcanvas');

        if (!this.panel || !this.offcanvas) { return false; }

        if (!this.options.padding) {
            this.offcanvas[0].style.display = 'block';
            var width = this.offcanvas[0].getBoundingClientRect().width;
            this.offcanvas[0].style.display = null;

            this.setOptions({ padding: width });
        }

        this.tolerance = typeof this.options.tolerance == 'function' ? this.options.tolerance.call(this, this.options.padding) : this.options.tolerance;

        if (this.options.touch && hasTouchEvents) {
            this._touchEvents();
        }

        return this.attach();
    },

    attach: function() {
        var body = $('body');

        forEach(['toggle', 'open', 'close'], bind(function(mode) {
            body.delegate('click', '[data-offcanvas-' + mode + ']', this.bound(mode));
            body.delegate('touchend', '[data-offcanvas-' + mode + ']', this.bound(mode));
        }, this));

        this.overlay = zen('div[data-offcanvas-close].' + this.options.overlayClass).top(this.panel);

        return this;
    },

    detach: function() {
        var body = $('body');

        forEach(['toggle', 'open', 'close'], bind(function(mode) {
            body.undelegate('click', '[data-offcanvas-' + mode + ']', this.bound(mode));
            body.undelegate('touchend', '[data-offcanvas-' + mode + ']', this.bound(mode));
        }, this));

        this.overlay.remove();

        return this;
    },

    open: function(event) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        else { this.dragging = false; }

        if (this.opened) { return this; }

        var html = $('html'),
            body = $('body');

        if (!html.hasClass(this.options.openClass)) {
            html.addClass(this.options.openClass);
        }

        this.overlay[0].style.opacity = 1;

        this._setTransition();
        this._translateXTo((body.hasClass('g-offcanvas-right') ? -1 : 1) * this.options.padding);
        this.opened = true;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            panel.style.transition = panel.style['-webkit-transition'] = '';
        }, this), this.options.duration);

        return this;
    },

    close: function(event, element) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        else { this.dragging = false; }

        if (!this.opened && !this.opening) { return this; }
        if (this.panel !== element && this.dragging) { return false; }

        var html = $('html');

        this.overlay[0].style.opacity = 0;

        this._setTransition();
        this._translateXTo(0);
        this.opened = false;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            html.removeClass(this.options.openClass);
            panel.style.transition = panel.style['-webkit-transition'] = '';
        }, this), this.options.duration);


        return this;
    },

    toggle: function(event, element) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        else { this.dragging = false; }

        return this[this.opened ? 'close' : 'open'](event, element);
    },

    _setTransition: function() {
        var panel = this.panel[0];

        panel.style[prefix.css + 'transition'] = panel.style.transition = prefix.css + 'transform ' + this.options.duration + 'ms ' + this.options.effect;
    },

    _translateXTo: function(x) {
        var panel = this.panel[0];
        this.offsetX.current = x;

        panel.style[prefix.css + 'transform'] = panel.style.transform = 'translate3d(' + x + 'px, 0, 0)';
    },

    _touchEvents: function() {
        var msPointerSupported = window.navigator.msPointerEnabled,
            self = this,
            html = $('html'),
            body = $('body'),
            touch = {
                start: msPointerSupported ? 'MSPointerDown' : 'touchstart',
                move: msPointerSupported ? 'MSPointerMove' : 'touchmove',
                end: msPointerSupported ? 'MSPointerUp' : 'touchend'
            };

        decouple(body, 'scroll', function() {
            if (!self.moved) {
                clearTimeout(scrollTimeout);
                isScrolling = true;
                scrollTimeout = setTimeout(function() {
                    isScrolling = false;
                }, 250);
            }
        });

        body.on(touch.move, function(event) {
            if (self.moved) { event.preventDefault(); }
            self.dragging = true;
        });

        this.panel.on(touch.start, function(event) {
            if (!event.touches) { return; }

            self.moved = false;
            self.opening = false;
            self.dragging = false;
            self.offsetX.start = event.touches[0].pageX;
            self.preventOpen = (!self.opened && self.offcanvas[0].clientWidth !== 0);
        });

        this.panel.on('touchcancel', function() {
            self.moved = false;
            self.opening = false;
        });

        this.panel.on(touch.end, function(event) {

            if (self.moved) {
                var tolerance = Math.abs(self.offsetX.current) > self.tolerance,
                    placement = body.hasClass('g-offcanvas-right') ? true : false,
                    direction = !placement ? (self.offsetX.current < 0) : (self.offsetX.current > 0);

                self.opening = tolerance ? !direction : direction;
                self.opened = !self.opening;
                self[self.opening ? 'open' : 'close'](event, self.panel);
            }

            self.moved = false;
        });

        this.panel.on(touch.move, function(event) {
            if (isScrolling || self.preventOpen || !event.touches) { return; }

            var placement = (body.hasClass('g-offcanvas-right') ? -1 : 1), // 1: left, -1: right
                place = placement < 0 ? 'right' : 'left',
                diffX = clamp(event.touches[0].clientX - self.offsetX.start, -self.options.padding, self.options.padding),
                translateX = self.offsetX.current = diffX,
                overlayOpacity;

            if (Math.abs(translateX) > self.options.padding) { return; }
            if (Math.abs(diffX) > 0) {
                self.opening = true;

                // offcanvas on left
                if (place == 'left' && (self.opened && diffX > 0 || !self.opened && diffX < 0)) { return; }

                // offcanvas on right
                if (place == 'right' && (self.opened && diffX < 0 || !self.opened && diffX > 0)) { return; }

                if (!self.moved && !html.hasClass(self.options.openClass)) {
                    html.addClass(self.options.openClass);
                }

                if ((place == 'left' && diffX <= 0) || (place == 'right' && diffX >= 0)) {
                    translateX = diffX + (placement * self.options.padding);
                    self.opening = false;
                }

                overlayOpacity = mapNumber(Math.abs(translateX), 0, self.options.padding, 0, 1);

                self.panel[0].style[prefix.css + 'transform'] = self.panel[0].style.transform = 'translate3d(' + translateX + 'px, 0, 0)';
                self.overlay[0].style.opacity = overlayOpacity;

                self.moved = true;
            }

        });
    }
});

module.exports = Offcanvas;