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
    },

    getTitle: function() {
        return "Position: <strong>" + (this.getAttribute('key') || this.getAttribute('name') || 'Position ' + UID) + "</strong>";
    }
});

module.exports = Position;
