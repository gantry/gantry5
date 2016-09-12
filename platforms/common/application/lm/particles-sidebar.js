"use strict";

var prime          = require('prime'),
    $              = require('elements'),
    decouple       = require('../utils/decouple'),
    Cookie         = require('../utils/cookie');

var ParticlesPicker = new prime({
    constructor: function() {
        this.bounds = {
            toggle: this.toggle.bind(this)
        };

        $('body').delegate('click', '.particles-picker-toggle', this.bounds.toggle);
    },

    toggle: function(event, element) {
        var container = element.parent('.particles-container'),
            wrapper   = element.nextSibling('.particles-picker-wrapper'),
            isCollapsed = container.hasClass('particles-hide');

        container[isCollapsed ? 'removeClass' : 'addClass']('particles-hide');
        Cookie.write('g5-particles-collapsed', isCollapsed ? 0 : 1);
    }
});

module.exports = new ParticlesPicker();
