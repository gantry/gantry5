"use strict";
var ready = require('elements/domready'),
    $ = require('elements');

ready(function(){
    var hiddens;
    $('body').delegate('click', '.enabler .toggle', function(e, element){
        element = $(element);
        hiddens = element.find('~~ [type=hidden]');

        if (!hiddens) return true;
        if (hiddens) hiddens.value(hiddens.value() == '0' ? '1' : '0');
    });
});

module.exports = {};