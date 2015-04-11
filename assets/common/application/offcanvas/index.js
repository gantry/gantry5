"use strict";
// Based on the awesome Slideout.js <https://mango.github.io/slideout/>

var ready   = require('domready'),
    prime   = require('prime'),
    bind    = require('mout/function/bind'),
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
        body.delegate('click', '[data-offcanvas-toggle]', this.bound('toggle'));
        body.delegate('click', '[data-offcanvas-open]', this.bound('open'));
        body.delegate('click', '[data-offcanvas-close]', this.bound('close'));

        this.overlay = zen('div[data-offcanvas-close].' + this.options.overlayClass).top(this.panel);

        return this;
    },

    detach: function() {
        var body = $('body');
        body.undelegate('click', '[data-offcanvas-toggle]', this.bound('toggle'));
        body.undelegate('click', '[data-offcanvas-open]', this.bound('open'));
        body.undelegate('click', '[data-offcanvas-close]', this.bound('close'));

        this.overlay.remove();

        return this;
    },

    open: function() {
        if (this.opened) { return this; }
        var body = $('body')[0];

        if (!~body.className.search(this.options.openClass)) {
            body.className += ' ' + this.options.openClass;
        }

        this.overlay[0].style.opacity = 1;

        this._setTransition();
        this._translateXTo(this.options.padding);
        this.opened = true;

        setTimeout(bind(function() {
            var panel = this.panel[0];

            panel.style.transition = panel.style['-webkit-transition'] = '';
        }, this), this.options.duration);

        return this;
    },

    close: function() {
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

    toggle: function() {
        return this[this.opened ? 'close' : 'open']();
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