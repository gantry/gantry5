"use strict";
var ready   = require('elements/domready'),
    json    = require('./json_test'),
    $       = require('elements/attributes'),
    modal   = require('../ui').modal,
    request = require('agent'),
    zen     = require('elements/zen'),

    AjaxURL = require('../utils/ajax-uri'),

    Builder = require('./builder');

require('../ui/popover');

var builder;


builder = new Builder(json);

ready(function () {;
    // attach events
    // Picker
    $('body').delegate('click', '[data-g5-lm-picker]', function(event, element){
        var data = JSON.parse(element.data('g5-lm-picker'));
        request(AjaxURL('page', 'pages_create'), function(error, response){
            $('[data-g5-content]').html(response.body.data.html).find('.title').text(data.name);
            builder = new Builder(data.layout);
            builder.load();

            // -!- Popovers
            // particles picker
            $('[data-lm-addparticle]').popover({type: 'async', placement: 'left-bottom', width: '200', style: 'fixed', url: AjaxURL('particles')});
        });

        modal.close();

    });
    var addPage = $('[data-g5-lm-add]');
    if (addPage) {
        addPage.on('click', function (e) {
            e.preventDefault();
            modal.open({
                content: 'Loading',
                remote: AjaxURL('layouts')
            });
        });
    }

    //builder.load();
});

module.exports = {
    $: $,
    builder: builder
};