"use strict";
var $     = require('elements');

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
        if (!elements) { return false; }

        var position = this.position();
        return elements.filter(function(element) {
            element = $(element);
            return element[0].offsetTop + treshold >= this[0].scrollTop &&
                element[0].offsetTop - treshold <= this[0].scrollTop + position.height;
        }, this);
    }
});

module.exports = $;
