"use strict";
var ready   = require('elements/domready'),
    json    = require('./json_test'),
    $       = require('elements/attributes'),
    modal   = require('../ui').modal,
    request = require('agent'),
    zen     = require('elements/zen'),

    AjaxURL = require('../utils/ajax-uri'),

    Builder = require('./builder'),
    History = require('../utils/History');

require('../ui/popover');

var builder;


builder = new Builder(json);

ready(function () {;
    // attach events
    // Picker
    $('body').delegate('statechangeBefore', '[data-g5-lm-picker]', function(){
        modal.close();
    });

    $('body').delegate('statechangeAfter', '[data-g5-lm-picker]', function(event, element){
        var data = JSON.parse(element.data('g5-lm-picker'));
        $('[data-g5-content]').find('.title').text(data.name);
        builder = new Builder(data.layout);
        builder.load();

        // -!- Popovers
        // particles picker
        $('[data-lm-addparticle]').popover({type: 'async', placement: 'left-bottom', width: '200', style: 'fixed', url: AjaxURL('particles')});
    });

    $('body').delegate('click', '[data-g5-lm-add]', function(event, element){
        event.preventDefault();
        modal.open({
            content: 'Loading',
            remote: AjaxURL('layouts')
        });
    });

});

module.exports = {
    $: $,
    builder: builder
};