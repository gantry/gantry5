"use strict";
var $     = require('elements'),
    moofx = require('moofx'),
    map   = require('mout/array/map'),
    slick = require('slick');


$.implement({
    belowthefold: function(expression, treshold) {
        var elements = this.search(expression);
        treshold = treshold || 0;
        if (!elements) { return false; }

        var fold = this.position().height + this[0].scrollTop;
        return elements.filter(function(element) {
            return fold <= $(element)[0].offsetTop - treshold;
        });
    },

    abovethetop: function(expression, treshold) {
        var elements = this.search(expression);
        treshold = treshold || 0;
        if (!elements) { return false; }

        var top = this[0].scrollTop;
        return elements.filter(function(element) {
            return top >= $(element)[0].offsetTop + $(element).position().height - treshold;
        });
    },

    rightofscreen: function(expression, treshold) {
        var elements = this.search(expression);
        treshold = treshold || 0;
        if (!elements) { return false; }

        var fold = this.position().width + this[0].scrollLeft;
        return elements.filter(function(element) {
            return fold <= $(element)[0].offsetLeft - treshold;
        });
    },

    leftofscreen: function(expression, treshold) {
        var elements = this.search(expression);
        treshold = treshold || 0;
        if (!elements) { return false; }

        var left = this[0].scrollLeft;
        return elements.filter(function(element) {
            return left >= $(element)[0].offsetLeft + $(element).position().width - treshold;
        });
    },

    inviewport: function(expression, treshold) {
        var elements = this.search(expression);
        treshold = treshold || 0;

        var position = this.position();
        return elements.filter(function(element) {
            element = $(element);
            return element[0].offsetTop - treshold >= this[0].scrollTop &&
                element[0].offsetTop + treshold <= this[0].scrollTop + position.height;
        }, this);
    }
});
/*
 (function($) {

 $.belowthefold = function(element, settings) {
 var fold = $(window).height() + $(window).scrollTop();
 return fold <= $(element).offset().top - settings.threshold;
 };

 $.abovethetop = function(element, settings) {
 var top = $(window).scrollTop();
 return top >= $(element).offset().top + $(element).height() - settings.threshold;
 };

 $.rightofscreen = function(element, settings) {
 var fold = $(window).width() + $(window).scrollLeft();
 return fold <= $(element).offset().left - settings.threshold;
 };

 $.leftofscreen = function(element, settings) {
 var left = $(window).scrollLeft();
 return left >= $(element).offset().left + $(element).width() - settings.threshold;
 };

 $.inviewport = function(element, settings) {
 return !$.rightofscreen(element, settings) && !$.leftofscreen(element, settings) && !$.belowthefold(element, settings) && !$.abovethetop(element, settings);
 };

 $.extend($.expr[':'], {
 "below-the-fold": function(a, i, m) {
 return $.belowthefold(a, {threshold : 0});
 },
 "above-the-top": function(a, i, m) {
 return $.abovethetop(a, {threshold : 0});
 },
 "left-of-screen": function(a, i, m) {
 return $.leftofscreen(a, {threshold : 0});
 },
 "right-of-screen": function(a, i, m) {
 return $.rightofscreen(a, {threshold : 0});
 },
 "in-viewport": function(a, i, m) {
 return $.inviewport(a, {threshold : 0});
 }
 });


 })(jQuery);

 var inViewport = function(){

 }*/

module.exports = $;
