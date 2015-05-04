"use strict";

var $             = require('elements'),
    ready         = require('elements/domready'),

    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    getAjaxSuffix = require('../utils/get-ajax-suffix');


var Configurations = {};

ready(function() {
    var body = $('body');

    // Handles Configurations Duplicate / Remove
    body.delegate('click', '[data-g-config]', function(event, element) {
        var mode = element.data('g-config'),
            href = element.data('g-config-href'),
            method = (element.data('g-config-method') || 'post').toLowerCase();

        event.preventDefault();

        element.hideIndicator();
        element.showIndicator();

        request(method, href + getAjaxSuffix(), {}, function(error, response) {
            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });
            } else {
                console.log(response);
            }

            element.hideIndicator();
        });

    });

    // TODO: this was the + handler for new layouts which is now gone in favor of Configurations
    body.delegate('click', '[data-g5-lm-add]', function(event, element) {
        event.preventDefault();
        modal.open({
            content: '<h1 class="center">Configurations are still WIP!</h1>'/*,
             remote: $(element).attribute('href') + getAjaxSuffix()*/
        });
    });
});

module.exports = Configurations;
