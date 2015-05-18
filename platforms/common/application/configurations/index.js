"use strict";

var $             = require('elements'),
    ready         = require('elements/domready'),
    trim          = require('mout/string/trim'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    getAjaxURL    = require('../utils/get-ajax-url').global,

    flags         = require('../utils/flags-state'),
    warningURL    = getAjaxURL('confirmdeletion') + getAjaxSuffix();


var Configurations = {};

ready(function() {
    var body = $('body');

    // Handles Configurations Duplicate / Remove
    body.delegate('click', '[data-g-config]', function(event, element) {
        var mode = element.data('g-config'),
            href = element.data('g-config-href'),
            encode = window.btoa(href),//.substr(-20, 20), // in case the strings gets too long
            method = (element.data('g-config-method') || 'post').toLowerCase();

        if (event && event.preventDefault) { event.preventDefault(); }

        if (mode == 'delete' && !flags.get('free:to:delete:' + encode, false)) {
            // confirm before proceeding
            flags.warning({
                url: warningURL,
                callback: function(response, content) {
                    var confirm = content.find('[data-g-delete-confirm]'),
                        cancel  = content.find('[data-g-delete-cancel]');

                    if (!confirm) { return; }

                    confirm.on('click', function(e) {
                        e.preventDefault();
                        if (this.attribute('disabled')) { return false; }

                        flags.get('free:to:delete:' + encode, true);
                        $([confirm, cancel]).attribute('disabled');
                        body.emit('click', { target: element });

                        modal.close();
                    });

                    cancel.on('click', function(e) {
                        e.preventDefault();
                        if (this.attribute('disabled')) { return false; }

                        $([confirm, cancel]).attribute('disabled');
                        flags.get('free:to:delete:' + encode, false);

                        modal.close();
                    });
                }
            });

            return false;
        }

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
                var reload = $('[href="' + getAjaxURL('configurations') + '"]');
                if (!reload) { window.location = window.location; }
                else {
                    body.emit('click', {target: reload});
                }

                toastr.success(response.body.html || 'Action successfully completed.', response.body.title || '');
                if (response.body.outline) {
                    body.outlineDeleted = response.body.outline;
                }
            }

            element.hideIndicator();
        });

    });

    // Handles Configurations Titles Rename
    var updateTitle = function(title, original, wasCanceled) {
            if (wasCanceled || title == original) { return; }
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
                    //console.log(response);
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
