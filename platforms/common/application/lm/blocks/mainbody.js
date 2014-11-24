"use strict";
var prime = require('prime'),
    Base  = require('./base'),
    $     = require('elements'),
    zen   = require('elements/zen');

require('elements/insertion');

var UID = 0;

var Mainbody = new prime({
    inherits: Base,
    options: {
        type: 'mainbody'
    },

    constructor: function(options) {
        ++UID;
        Base.call(this, options);
    },

    getTitle: function() {
        return 'Mainbody ' + UID;
    },

    layout: function() {
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '<div class="float-right"><span class="particle-size">100%</span> </span><i class="fa fa-cog"></i></div></div>';
    }
});

module.exports = Mainbody;
