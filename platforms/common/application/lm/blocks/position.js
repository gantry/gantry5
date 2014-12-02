"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

var UID = 0;

var Position = new prime({
    inherits: Particle,
    options: {
        type: 'position'
    },

    constructor: function(options) {
        ++UID;
        Particle.call(this, options);
        this.setAttribute('name', this.getTitle());

        if (this.isNew()) { --UID; }
    },

    getTitle: function() {
        return  (this.getAttribute('key') || this.getAttribute('name') || 'Position ' + UID);
    }
});

module.exports = Position;
