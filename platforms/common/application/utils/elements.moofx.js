"use strict";
var $     = require('elements'),
    moofx = require('moofx'),
    map   = require('mout/array/map'),
    slick = require('slick'),
    zen   = require('elements/zen');

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

    showSpinner: function(klass) {
        var icon = this.find('i');
        this.gHadIcon = !!icon;

        if (!icon) {
            if (!this.find('span')) { zen('span').text(this.text()).top(this.empty()); }

            icon = zen('i');
            icon.top(this);
        }

        if (!this.gSpinner) { this.gSpinner = icon.attribute('class'); }
        icon.attribute('class', klass || 'fa fa-spin-fast fa-spinner');
    },

    hideSpinner: function() {
        var icon = this.find('i');

        if (!this.gHadIcon) { icon.remove(); }
        else { icon.attribute('class', this.gSpinner); }

        this.gSpinner = null;
    },

    sibling: walk('++', 'find'),

    siblings: walk('~~', 'search')
});

module.exports = $;
