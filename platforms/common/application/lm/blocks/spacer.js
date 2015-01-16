"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

var UID = 0;

var Spacer = new prime({
    inherits: Particle,
    options: {
        type: 'spacer',
        attributes: {
            title: "Spacer"
        }
    }
});

module.exports = Spacer;
