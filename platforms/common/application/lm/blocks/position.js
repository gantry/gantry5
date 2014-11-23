"use strict";
var prime = require('prime'),
    Base  = require('./base'),
    $     = require('elements'),
    zen   = require('elements/zen');

require('elements/insertion');

var UID = 0;

var Position = new prime({
    inherits: Base,
    options: {
        type: 'position'
    },

    constructor: function(options) {
        ++UID;
        Base.call(this, options);
        this.setAttribute('name', this.getTitle());
    },

    getTitle: function() {
        return this.getAttribute('name') || 'Position ' + UID;
    },

    layout: function() {
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '<div class="float-right"><span class="particle-size"></span> <i class="fa fa-cog"></i></div></div>';
    }
});

module.exports = Position;
