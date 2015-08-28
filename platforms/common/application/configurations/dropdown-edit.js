"use strict";

var $             = require('elements'),
    ready         = require('elements/domready'),
    request       = require('agent'),

    modal         = require('../ui').modal,
    trim          = require('mout/string/trim'),

    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxURL    = require('../utils/get-ajax-url').global;

ready(function() {
    var body = $('body');

    var selectized, select, editable, href;
    body.delegate('keydown', '.config-select-wrap [data-title-edit]', function(event, element) {
        var key = (event.which ? event.which : event.keyCode);
        if (key == 32 || key == 13) { // ARIA support: Space / Enter toggle
            event.preventDefault();
            body.emit('mousedown', event);
        }
    });

    body.delegate('mousedown', '.config-select-wrap [data-title-edit]', function(event, element) {
        selectized = element.siblings('.selectize-control');
        select = element.siblings('select');
        editable = element.siblings('[data-title-editable]');

        if (!editable.gConfEditAttached) {
            editable.gConfEditAttached = true;
            editable.on('title-edit-end', function(title, original, canceled) {
                title = trim(title);
                if (canceled || title == original) {
                    selectized.style('display', 'inline-block');
                    editable.style('display', 'none').attribute('contenteditable', null);

                    return;
                }

                element.addClass('disabled');
                element.removeClass('fa-pencil').addClass('fa-spin-fast fa-spinner');
                href = editable.data('g-config-href');

                request('post', parseAjaxURI(href + getAjaxSuffix()), { title: title }, function(error, response) {
                    if (!response.body.success) {
                        modal.open({
                            content: response.body.html || response.body,
                            afterOpen: function(container) {
                                if (!response.body.html) { container.style({ width: '90%' }); }
                            }
                        });

                        editable.data('title-editable', original).text(original);
                    } else {
                        var selectize = select.selectizeInstance,
                            value = select.value(),
                            data = selectize.Options[value];

                        data[selectize.options.labelField] = title;
                        selectize.updateOption(value, data);

                        selectized.style('display', 'inline-block');
                        editable.style('display', 'none')
                    }

                    element.removeClass('disabled');
                    element.removeClass('fa-spin-fast fa-spinner').addClass('fa-pencil');
                });
            });
        }

        editable.style({
            width: selectized.compute('width'),
            display: 'inline-block'
        });
        selectized.style('display', 'none');
    });
});

module.exports = {};
