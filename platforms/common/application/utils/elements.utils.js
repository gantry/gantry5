"use strict";
var $      = require('elements'),
    moofx  = require('moofx'),
    map    = require('mout/array/map'),
    series = require('mout/function/series'),
    slick  = require('slick'),
    zen    = require('elements/zen');

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

    compute: function() {
        var moo = moofx(this);
        return moo.compute.apply(moo, arguments);
    },

    showIndicator: function(klass, keepIcon) {
        if (typeof klass == 'boolean') {
            keepIcon = klass;
            klass = null;
        }

        var icon = keepIcon ? false : this.find('i');
        this.gHadIcon = !!icon;

        if (!icon) {
            if (!this.find('span') && !this.children()) { zen('span').text(this.text()).top(this.empty()); }

            icon = zen('i');
            icon.top(this);
        }

        if (!this.gIndicator) { this.gIndicator = icon.attribute('class') || true; }
        icon.attribute('class', klass || 'fa fa-fw fa-spin-fast fa-spinner');
    },

    hideIndicator: function() {
        if (!this.gIndicator) { return; }

        var icon = this.find('i');

        if (!this.gHadIcon) { icon.remove(); }
        else { icon.attribute('class', this.gIndicator); }

        this.gIndicator = null;
    },

    slideDown: function(animation, callback) {
        if (this.gSlideCollapsed === false) { return; }

        var element = this,
            size = this.getRealSize(),
            callbackStart = function(){
                element.gSlideCollapsed = false;
            },
            callbackEnd = function(){
                element.attribute('style', element.gSlideStyle);
            };

        callback = typeof animation == 'function' ? animation : (callback || function(){});
        callback = series(callbackStart, callback, callbackEnd);

        animation = typeof animation == 'string' ? animation : { duration: '250ms', callback: callback };
        this.animate({height: size.height}, animation);
    },

    slideUp: function(animation, callback) {
        if (this.gSlideCollapsed === true) { return; }

        if (typeof this.gSlideCollapsed == 'undefined') {
            this.gSlideStyle = this.attribute('style');
        }

        var element = this,
            callbackStart = function(){
                element.gSlideCollapsed = true;
            };

        callback = typeof animation == 'function' ? animation : (callback || function(){});
        callback = series(callbackStart, callback);

        animation = typeof animation == 'string' ? animation : { duration: '250ms', callback: callback };
        this.style({overflow: 'hidden'}).animate({height: 0}, animation);
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
