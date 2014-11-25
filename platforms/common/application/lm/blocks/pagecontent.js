"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

var Pagecontent = new prime({
    inherits: Particle,
    options: {
        type: 'pagecontent',
        attributes: {
            name: "Page Content"
        }
    }
});

module.exports = Pagecontent;
