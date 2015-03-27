"use strict";
var ready = require('elements/domready'),
    $ = require('elements');

ready(function(){
    var hiddens,
        click = ('ontouchend' in window) ? 'touchend' : 'click';

    $('body').delegate(click, '.enabler .toggle', function(e, element){
        console.log('a');
        element = $(element);
        hiddens = element.find('~~ [type=hidden]');

        if (!hiddens) return true;
        hiddens.value(hiddens.value() == '0' ? '1' : '0');

        hiddens.emit('change');
        $('body').emit('change', {target: hiddens});
    });
});

module.exports = {};