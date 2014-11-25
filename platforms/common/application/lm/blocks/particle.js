"use strict";
var prime = require('prime'),
    Base  = require('./base'),
    bind  = require('mout/function/bind');

var UID = 0;

var Particle = new prime({
    inherits: Base,
    options: {
        type: 'particle'
    },

    constructor: function(options) {
        ++UID;
        Base.call(this, options);

        this.on('rendered', bind(function(element, parent) {
            var size = parent.getSize() || 100,
                label = this.block.find('.particle-size');

            if (size % 1 > 0) { size = size.toFixed(1); }
            if (label) { label.text(size + '%'); }
        }, this));
    },

    getTitle: function() {
        return this.getAttribute('name');
    },

    layout: function() {
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '<div class="float-right"><span class="particle-size"></span> <i class="fa fa-cog"></i></div></div>';
    }
});

module.exports = Particle;
