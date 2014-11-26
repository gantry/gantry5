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
    },

    layout: function() {
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '<div class="float-right"><span class="particle-size"></span> <i class="fa fa-cog"></i></div></div>';
    },

    setLabelSize: function(size){
        var label = this.block.find('.particle-size');
        if (!label) { return false; }

        label.text(precision(size, 1) + '%');
    },

    onRendered: function(element, parent) {
        var size = parent.getSize() || 100;

        this.setLabelSize(size);
        parent.on('resized', this.bound('onParentResize'));
    },

    onParentResize: function(resize) {
        this.setLabelSize(resize);
    }
});

module.exports = Particle;
