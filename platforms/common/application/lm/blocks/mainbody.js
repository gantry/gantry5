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
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" ' + this.dropZone() + ' data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '</div>';
    }
});

module.exports = Mainbody;
