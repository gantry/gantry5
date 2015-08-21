"use strict";
var $          = require('elements'),
    moofx      = require('moofx'),
    map        = require('mout/array/map'),
    series     = require('mout/function/series'),
    slick      = require('slick'),
    zen        = require('elements/zen'),
    progresser = require('../ui/progresser');

var walk = function(combinator, method) {

    return function(expression) {
        var parts = slick.parse(expression || "*");

        expression = map(parts, function(part) {
            return combinator + " " + part;
        }).join(', ');

        return this[method](expression);
    };

};


$.implement({
    style: function() {
        var moo = moofx(this);
        moo.style.apply(moo, arguments);
        return this;
    },

    animate: function() {
        var moo = moofx(this);
        moo.animate.apply(moo, arguments);
        return this;
    },

    hide: function() {
        return this.style('display', 'none');
    },

    show: function(mode) {
        return this.style('display', mode || 'inherit');
    },

    progresser: function(options) {
        var instance;

        this.forEach(function(node) {
            instance = node.ProgresserInstance;

            if (!instance) { instance = new progresser(node, options); }
            else { instance.constructor(node, options); }

            node.ProgresserInstance = instance;
            return instance;
        });
    },

    compute: function() {
        var moo = moofx(this);
        return moo.compute.apply(moo, arguments);
    },

    showIndicator: function(klass, keepIcon) {
        this.forEach(function(node) {
            node = $(node);
            if (typeof klass == 'boolean') {
                keepIcon = klass;
                klass = null;
            }

            var icon = keepIcon ? false : node.find('i');
            node.gHadIcon = !!icon;

            if (!icon) {
                if (!node.find('span') && !node.children()) { zen('span').text(node.text()).top(node.empty()); }

                icon = zen('i');
                icon.top(node);
            }

            if (!node.gIndicator) { node.gIndicator = icon.attribute('class') || true; }
            icon.attribute('class', klass || 'fa fa-fw fa-spin-fast fa-spinner');
        });
    },

    hideIndicator: function() {
        this.forEach(function(node) {
            node = $(node);
            if (!node.gIndicator) { return; }

            var icon = node.find('i');

            if (!icon) { return; }

            if (!node.gHadIcon) { icon.remove(); }
            else { icon.attribute('class', node.gIndicator); }

            node.gIndicator = null;
        });
    },

    slideDown: function(animation, callback) {
        var element       = this,
            size          = this.getRealSize(),
            callbackStart = function() {
                element.gSlideCollapsed = false;
            },
            callbackEnd   = function() {
                element.attribute('style', element.gSlideStyle);
            };

        callback = typeof animation == 'function' ? animation : (callback || function() {});
        if (this.gSlideCollapsed === false) { return callback(); }
        callback = series(callbackStart, callback, callbackEnd);

        animation = typeof animation == 'string' ? animation : {
            duration: '250ms',
            callback: callback
        };

        this.style('visibility', 'visible').attribute('aria-hidden', false);
        this.animate({ height: size.height }, animation);
    },

    slideUp: function(animation, callback) {
        if (typeof this.gSlideCollapsed == 'undefined') {
            this.gSlideStyle = this.attribute('style');
        }

        var element       = this,
            callbackStart = function() {
                element.gSlideCollapsed = true;
            },
            callbackEnd = function() {
                element.style('visibility', 'hidden').attribute('aria-hidden', true);
            };

        callback = typeof animation == 'function' ? animation : (callback || function() {});
        if (this.gSlideCollapsed === true) { return callback(); }
        callback = series(callbackStart, callback, callbackEnd);

        animation = typeof animation == 'string' ? animation : {
            duration: '250ms',
            callback: callback
        };
        this.style({ overflow: 'hidden' }).animate({ height: 0 }, animation);
    },

    slideToggle: function(animation, callback) {
        var size = this.getRealSize();
        return this[size.height && !this.gSlideCollapsed ? 'slideUp' : 'slideDown'](animation, callback);
    },

    getRealSize: function() {
        var style = this.attribute('style'), size;
        this.style({
            position: 'relative',
            overflow: 'inherit',
            top: -50000,
            height: 'auto',
            width: 'auto'
        });

        size = {
            width: parseInt(this.compute('width'), 10),
            height: parseInt(this.compute('height'), 10)
        };

        this.attribute('style', style);

        return size;
    },

    sibling: walk('++', 'find'),

    siblings: walk('~~', 'search')
});

module.exports = $;
