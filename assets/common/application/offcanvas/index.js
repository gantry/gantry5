"use strict";
// Based on the awesome Slideout.js <https://mango.github.io/slideout/>

var ready   = require('domready'),
    prime   = require('prime'),
    bind    = require('mout/function/bind'),
    forEach = require('mout/array/forEach'),
    Bound   = require('prime-util/prime/bound'),
    Options = require('prime-util/prime/options'),
    $       = require('elements'),
    zen     = require('elements/zen');

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

var hasTouchEvents     = ('ontouchstart' in window) || window.DocumentTouch && document instanceof DocumentTouch,
    msPointerSupported = window.navigator.msPointerEnabled,
    touch              = {
        start: msPointerSupported ? 'MSPointerDown' : 'touchstart',
        move: msPointerSupported ? 'MSPointerMove' : 'touchmove',
        end: msPointerSupported ? 'MSPointerUp' : 'touchend'
    };

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
        this.opened = false;
        this.preventOpen = false;
        this.offsetX = {
            start: 0,
            current: 0
        };

        this.panel = $('#g-page-surround');
        this.offcanvas = $('#g-offcanvas');

        if (!this.panel || !this.offcanvas) { return false; }

        if (!this.options.padding) { this.setOptions({ padding: this.offcanvas[0].getBoundingClientRect().width }); }

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

    open: function(event, element) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        if (this.opened) { return this; }

        var body = $('body')[0];

        if (!~body.className.search(this.options.openClass)) {
            body.className += ' ' + this.options.openClass;
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

    close: function(event) {
        if (event && event.type.match(/^touch/i)) { event.preventDefault(); }
        if (!this.opened && !this.opening) { return this; }

        var body = $('body')[0];

        this.overlay[0].style.opacity = 0;

        this._setTransition();
        this._translateXTo(0);
        this.opened = false;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            body.className = body.className.replace(' ' + this.options.openClass, '');
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
    }
});

module.exports = Offcanvas;