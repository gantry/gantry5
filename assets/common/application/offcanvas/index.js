// Offcanvas slide with desktop, touch and all-in-one touch devices support.
// Fast and optimized using CSS3 transitions
// Based on the awesome Slideout.js <https://mango.github.io/slideout/>

"use strict";

var ready    = require('domready'),
    prime    = require('prime'),
    bind     = require('mout/function/bind'),
    forEach  = require('mout/array/forEach'),
    decouple = require('../utils/decouple'),
    Bound    = require('prime-util/prime/bound'),
    Options  = require('prime-util/prime/options'),
    $        = require('elements'),
    zen      = require('elements/zen');

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
        tolerance: 70,
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

        if (this.options.touch && hasTouchEvents) {
            this._touchEvents();
        }

        return this.attach();
    },

    attach: function() {
        var body = $('body');

        forEach(['toggle', 'open', 'close'], bind(function(mode) {
            body.delegate('click', '[data-offcanvas-' + mode + ']', this.bound(mode));
            if (hasTouchEvents) { body.delegate('touchend', '[data-offcanvas-' + mode + ']', this.bound(mode)); }
        }, this));

        this.overlay = zen('div[data-offcanvas-close].' + this.options.overlayClass).top(this.panel);

        return this;
    },

    detach: function() {
        var body = $('body');

        forEach(['toggle', 'open', 'close'], bind(function(mode) {
            body.undelegate('click', '[data-offcanvas-' + mode + ']', this.bound(mode));
            if (hasTouchEvents) { body.undelegate('touchend', '[data-offcanvas-' + mode + ']', this.bound(mode)); }
        }, this));

        this.overlay.remove();

        return this;
    },

    open: function(event) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        if (this.opened) { return this; }

        var html = $('html')[0],
            body = $('body')[0];

        if (!~html.className.search(this.options.openClass)) {
            html.className += ' ' + this.options.openClass;
        }

        this.overlay[0].style.opacity = 1;

        this._setTransition();
        this._translateXTo((!~body.className.search('g-offcanvas-right') ? 1 : -1) * this.options.padding);
        this.opened = true;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            panel.style.transition = panel.style['-webkit-transition'] = '';
        }, this), this.options.duration);

        return this;
    },

    close: function(event, element) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        if (!this.opened && !this.opening) { return this; }
        if (this.panel !== element && this.dragging) { return false; }

        var html = $('html')[0];

        this.overlay[0].style.opacity = 0;

        this._setTransition();
        this._translateXTo(0);
        this.opened = false;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            html.className = html.className.replace(' ' + this.options.openClass, '');
            panel.style.transition = panel.style['-webkit-transition'] = '';
        }, this), this.options.duration);


        return this;
    },

    toggle: function(event, element) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }

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
                var tolerance = Math.abs(self.offsetX.current) > self.options.tolerance;
                self[self.opening && tolerance ? 'open' : 'close'](event, self.panel);
            }

            self.moved = false;
        });

        this.panel.on(touch.move, function(event) {
            if (isScrolling || self.preventOpen || !event.touches) { return; }

            var diffX = event.touches[0].clientX - self.offsetX.start;
            var translateX = self.offsetX.current = diffX;

            if (Math.abs(translateX) > self.options.padding) { return; }

            if (Math.abs(diffX) > 20) {
                self.opening = true;

                if (self.opened && diffX > 0 || !self.opened && diffX < 0) { return; }

                if (!self.moved && !~html[0].className.search(self.options.openClass)) {
                    html[0].className += ' ' + self.options.openClass;
                }

                if (diffX <= 0) {
                    translateX = diffX + self.options.padding;
                    self.opening = false;
                }

                self.panel[0].style[prefix.css + 'transform'] = self.panel[0].style.transform = 'translate3d(' + translateX + 'px, 0, 0)';

                self.moved = true;
            }

        });
    }
});

module.exports = Offcanvas;