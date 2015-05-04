"use strict";
var prime       = require('prime'),
    Pagecontent = require('./pagecontent');

var SystemMessages = new prime({
    inherits: Pagecontent,
    options: {
        type: 'system',
        title: 'System Message',
        attributes: {}
    }
});

module.exports = SystemMessages;
