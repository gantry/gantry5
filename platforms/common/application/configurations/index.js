"use strict";

var $             = require('elements'),
    ready         = require('elements/domready'),
    trim          = require('mout/string/trim'),
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

    // Handles Configurations Titles Rename
    var updateTitle = function(title, original) {
            var element = this,
                href = element.data('g-config-href'),
                method = (element.data('g-config-method') || 'post').toLowerCase(),
                parent = element.parent();

            parent.showIndicator();
            parent.find('[data-title-edit]').addClass('disabled');

            request(method, href + getAjaxSuffix(), { title: trim(title) }, function(error, response) {
                if (!response.body.success) {
                    modal.open({
                        content: response.body.html || response.body,
                        afterOpen: function(container) {
                            if (!response.body.html) { container.style({ width: '90%' }); }
                        }
                    });

                    element.data('title-editable', original).text(original);
                } else {
                    console.log(response);
                }

                parent.hideIndicator();
                parent.find('[data-title-edit]').removeClass('disabled');
            });
        },

        attachEditables = function(editables) {
            if (!editables || !editables.length) { return; }
            editables.forEach(function(editable) {
                editable = $(editable);
                editable.confWasAttached = true;
                editable.on('title-edit-end', updateTitle);
            });
        };

    body.on('statechangeAfter', function(event, element) {
        var editables = $('#configurations [data-title-editable]');
        if (!editables) { return true; }

        editables = editables.filter(function(editable) {
            return (typeof $(editable).confWasAttached) === 'undefined';
        });

        attachEditables(editables);
    });

    attachEditables($('#configurations [data-title-editable]'));
});

module.exports = Configurations;
