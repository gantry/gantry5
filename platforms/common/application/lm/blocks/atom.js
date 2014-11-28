"use strict";
var prime = require('prime'),
    Base  = require('./base');

var Atom = new prime({
    inherits: Base,
    options: {
        type: 'atom'
    },

    constructor: function(options) {
        Base.call(this, options);
    },

    getTitle: function() {
        return this.getAttribute('name');
    },

    layout: function() {
        return '<div class="' + this.getType() + '" data-lm-id="' + this.getId() + '" data-lm-blocktype="' + this.getType() + '">' + this.getTitle() + '<div class="float-right"><i class="fa fa-cog"></i></div></div>';
    }
});

module.exports = Atom;
