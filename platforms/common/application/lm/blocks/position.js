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
        this.setAttribute('title', this.getTitle());
        this.setAttribute('key', this.getKey());

        if (this.isNew()) { --UID; }
    },

    getTitle: function() {
        return (this.getAttribute('title') || 'Position ' + UID);
    },

    getKey: function() {
        return (this.getAttribute('key') || this.getTitle().replace(/\s/g, '-').toLowerCase());
    }
});

module.exports = Position;
