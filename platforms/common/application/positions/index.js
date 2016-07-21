"use strict";

var $             = require('elements'),
    zen           = require('elements/zen'),
    ready         = require('elements/domready'),
    trim          = require('mout/string/trim'),
    keys          = require('mout/object/keys'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxURL    = require('../utils/get-ajax-url').global,

    flags         = require('../utils/flags-state'),
    translate     = require('../utils/translate');

require('./cards');

ready(function() {
    var body = $('body'),
        warningURL = parseAjaxURI(getAjaxURL('confirmdeletion') + getAjaxSuffix());

    // Handles Positions Duplicate / Remove
    body.delegate('click', '#positions [data-g-config]', function(event, element) {
        var mode = element.data('g-config'),
            href = element.data('g-config-href'),
            encode = window.btoa(href),//.substr(-20, 20), // in case the strings gets too long
            method = (element.data('g-config-method') || 'post').toLowerCase();

        if (event && event.preventDefault) { event.preventDefault(); }

        if (mode == 'delete' && !flags.get('free:to:delete:' + encode, false)) {
            // confirm before proceeding
            flags.warning({
                url: warningURL,
                data: {page_type: 'POSITION'},
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

        request(method, parseAjaxURI(href + getAjaxSuffix()), {}, function(error, response) {
            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });
            } else {
                var positionDeleted = response.body.position,
                    reload = $('[href="' + getAjaxURL('positions') + '"]');

                if (!reload) { window.location = window.location; }
                else {
                    body.emit('click', {target: reload});
                }

                toastr.success(response.body.html || 'Action successfully completed.', response.body.title || '');
                if (positionDeleted) {
                    body.positionDeleted = positionDeleted;
                }
            }

            element.hideIndicator();
        });

    });

    // Positions Add
    body.delegate('click', '#positions .card h4 button', function(event, element) {
        event.preventDefault();

        var data = {};

        modal.open({
            content: translate('GANTRY5_PLATFORM_JS_LOADING'),
            method: 'get',
            overlayClickToClose: false,
            remote: parseAjaxURI(getAjaxURL('positions/add') + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }

                var form       = content.elements.content.find('form'),
                    fakeDOM    = zen('div').html(response.body.html).find('form'),
                    submit     = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]');

                var search      = content.elements.content.find('.search input'),
                    blocks      = content.elements.content.search('[data-pm-type]'),
                    filters     = content.elements.content.search('[data-pm-filter]'),
                    urlTemplate = content.elements.content.find('.g-urltemplate');

                if (urlTemplate) { body.emit('input', { target: urlTemplate }); }

                var editable = content.elements.content.find('[data-title-editable]');
                if (editable) {
                    editable.on('title-edit-end', function(title, original/*, canceled*/) {
                        title = trim(title);
                        if (!title) {
                            title = trim(original) || 'Title';
                            this.text(title).data('title-editable', title);

                            return true;
                        }
                    });
                }

                if (search && filters && blocks) {
                    search.on('input', function() {
                        if (!this.value()) {
                            blocks.removeClass('hidden');
                            return;
                        }

                        blocks.addClass('hidden');

                        var found = [], value = this.value().toLowerCase(), text;

                        filters.forEach(function(filter) {
                            filter = $(filter);
                            text = trim(filter.data('pm-filter')).toLowerCase();
                            if (text.match(new RegExp("^" + value + '|\\s' + value, 'gi'))) {
                                found.push(filter.matches('[data-pm-type]') ? filter : filter.parent('[data-pm-type]'));
                            }
                        }, this);

                        if (found.length) { $(found).removeClass('hidden'); }
                    });
                }

                if (search) {
                    setTimeout(function() {
                        search[0].focus();
                    }, 5);
                }

                if ((!form && !fakeDOM) || !submit) { return true; }
            }
        });
    });

    // Positions Items settings
    body.delegate('click', '#positions .item-settings', function(event, element) {
        event.preventDefault();

        var data = {},
            parent = element.parent('[data-pm-data]');

        data.item = JSON.stringify(parent.data('pm-data'));

        modal.open({
            content: translate('GANTRY5_PLATFORM_JS_LOADING'),
            method: 'post',
            data: data,
            overlayClickToClose: false,
            remote: parseAjaxURI(getAjaxURL('positions/edit/' + parent.data('pm-blocktype')) + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }

                alert('Let me know when you got to this point ;)');
            }
        });
    });

    // Handles Positions Titles Rename
    var updateTitle = function(title, original, wasCanceled) {
            this.style('text-overflow', 'ellipsis');
            if (wasCanceled || title == original) { return; }
            var element = this,
                href = element.data('g-config-href'),
                method = (element.data('g-config-method') || 'post').toLowerCase(),
                parent = element.parent();

            parent.showIndicator();
            parent.find('[data-title-edit]').addClass('disabled');

            request(method, parseAjaxURI(href + getAjaxSuffix()), { title: trim(title) }, function(error, response) {
                if (!response.body.success) {
                    modal.open({
                        content: response.body.html || response.body,
                        afterOpen: function(container) {
                            if (!response.body.html) { container.style({ width: '90%' }); }
                        }
                    });

                    element.data('title-editable', original).text(original);
                } else {
                    element.data('title', title).data('tip', title);

                    // refresh ID label and actions buttons
                    var dummy = zen('div').html(response.body.position),
                        actions = dummy.find('.position-actions');

                    element.parent('.card').find('h4 .position-key').html(response.body.id);
                    element.parent('.card').find('.position-actions').html(actions.html());
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
                editable.on('title-edit-start', function(){
                    editable.style('text-overflow', 'inherit');
                });
                editable.on('title-edit-end', updateTitle);
            });
        };

    // Global state change
    body.on('statechangeAfter', function(event, element) {
        var editables = $('#positions [data-title-editable]');
        if (!editables) { return true; }

        editables = editables.filter(function(editable) {
            return (typeof $(editable).confWasAttached) === 'undefined';
        });

        attachEditables(editables);
    });

    attachEditables($('#positions [data-title-editable]'));
});

module.exports = {};
