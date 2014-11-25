"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

var UID = 0;

var Spacer = new prime({
    inherits: Particle,
    options: {
        type: 'spacer',
        attributes: {
            name: "Spacer"
        }
    }
});

module.exports = Spacer;
