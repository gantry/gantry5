"use strict";
var prime     = require('prime'),
    Atom      = require('./atom'),
    bind      = require('mout/function/bind'),
    precision = require('mout/number/enforcePrecision');

var UID = 0;

var Particle = new prime({
    inherits: Atom,
    options: {
        type: 'particle'
    },

    constructor: function(options) {
        ++UID;
        Atom.call(this, options);

        this.on('rendered', bind(function(element, parent) {
            var size = parent.getSize() || 100,
                label = this.block.find('.particle-size');

            if (label) { label.text(precision(size, 1) + '%'); }

            parent.on('resized', bind(function(resize, a, b){
                if (label) { label.text(precision(resize, 1) + '%'); }
            }, this));
        }, this));
    },

    layout: function() {
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '<div class="float-right"><span class="particle-size"></span> <i class="fa fa-cog"></i></div></div>';
    }
});

module.exports = Particle;
