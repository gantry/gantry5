"use strict";
var prime    = require('prime'),
    Particle = require('./particle');

var Pagecontent = new prime({
    inherits: Particle,
    options: {
        type: 'pagecontent',
        title: 'Page Content',
        attributes: {}
    }
});

module.exports = Pagecontent;
