"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

var System = new prime({
    inherits: Particle,
    options: {
        type: 'system',
        attributes: {}
    }
});

module.exports = System;
