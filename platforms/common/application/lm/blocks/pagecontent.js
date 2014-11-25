"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

require('elements/insertion');

var Pagecontent = new prime({
    inherits: Particle,
    options: {
        type: 'pagecontent'
    }
});

module.exports = Pagecontent;
