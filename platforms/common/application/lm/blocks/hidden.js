"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

require('elements/insertion');

var Hidden = new prime({
    inherits: Particle,
    options: {
        type: 'hidden'
    }
});

module.exports = Hidden;
