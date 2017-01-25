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
    Submit        = require('../fields/submit'),

    flags         = require('../utils/flags-state'),
    translate     = require('../utils/translate'),
    Cards         = require('./cards');

ready(function() {
    var body = $('body'),
        warningURL = parseAjaxURI(getAjaxURL('confirmdeletion') + getAjaxSuffix());

    Cards.init();

    // Handles Positions Duplicate / Remove
    body.delegate('click', '#positions [data-g-config], [data-g-create="position"]', function(event, element) {
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
    body.delegate('click', '#positions .position-add', function(event, element) {
        event.preventDefault();

        var data = {};

        modal.open({
            content: translate('GANTRY5_PLATFORM_JS_LOADING'),
            method: 'get',
            overlayClickToClose: false,
            remote: parseAjaxURI(element.attribute('href') + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }

                var form       = content.elements.content.find('form'),
                    fakeDOM    = zen('div').html(response.body.html).find('form'),
                    submit     = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]');

                var search      = content.elements.content.find('.search input'),
                    blocks      = content.elements.content.search('[data-mm-type]'),
                    filters     = content.elements.content.search('[data-mm-filter]'),
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
                            text = trim(filter.data('mm-filter')).toLowerCase();
                            if (text.match(new RegExp("^" + value + '|\\s' + value, 'gi'))) {
                                found.push(filter.matches('[data-mm-type]') ? filter : filter.parent('[data-mm-type]'));
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
            parent = element.parent('[data-pm-data]'),
            position = JSON.parse(element.parent('[data-position]').data('position'));

        data.position = position.name;
        data.item = parent.data('pm-data');

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

                var form       = content.elements.content.find('form'),
                    fakeDOM    = zen('div').html(response.body.html).find('form'),
                    submit     = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]');

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

                if ((!form && !fakeDOM) || !submit) { return true; }

                // Position Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();
                    fakeDOM = content.elements.content.find('form');

                    var target = $(e.currentTarget);
                    target.disabled(true);
                    target.hideIndicator();
                    target.showIndicator();

                    var post = Submit(fakeDOM[0].elements, content.elements.content);

                    if (post.invalid.length) {
                        target.disabled(false);
                        target.hideIndicator();
                        target.showIndicator('fa fa-fw fa-exclamation-triangle');
                        toastr.error(translate('GANTRY5_PLATFORM_JS_REVIEW_FIELDS'), translate('GANTRY5_PLATFORM_JS_INVALID_FIELDS'));
                        return;
                    }

                    request(fakeDOM.attribute('method'), parseAjaxURI(fakeDOM.attribute('action') + getAjaxSuffix()), post.valid.join('&'), function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            var parent = element.parent('[data-pm-data]');

                            if (parent) {
                                parent.data('pm-data', JSON.stringify(response.body.item));

                                var status = response.body.item.enabled || response.body.item.options.attributes.enabled;
                                var dummy = zen('div').html(response.body.html);
                                parent.html(dummy.firstChild().html());
                                parent[status == '0' ? 'addClass' : 'removeClass']('g-menu-item-disabled');
                            }

                            // if it's apply and save we also save the panel
                            if (target.data('apply-and-save') !== null) {
                                var save = $('body').find('.button-save');
                                if (save) { body.emit('click', { target: save }); }
                            }

                            Cards.serialize(element.parent('[data-position]'));
                            Cards.updatePendingChanges();

                            modal.close();
                            toastr.success(translate('GANTRY5_PLATFORM_JS_POSITIONS_SETTINGS_APPLIED'), translate('GANTRY5_PLATFORM_JS_SETTINGS_APPLIED'));
                        }

                        target.hideIndicator();
                    });
                });
            }
        });
    });

    // Handles Positions Titles Rename
    var updateTitle = function(title, original, wasCanceled) {
            this.style('text-overflow', 'ellipsis');
            if (wasCanceled || title == original) { return; }
            var element = this,
                href = element.data('g-config-href'),
                type = element.data('title-editable-type'),
                method = (element.data('g-config-method') || 'post').toLowerCase(),
                parent = element.parent('[id]');

            parent.showIndicator();
            parent.find('[data-title-edit]').addClass('disabled');

            var data = type === 'title' ? { title: trim(title) } : { key: trim(title) };
            data.data = parent.find('[data-position]').data('position');

            request(method, parseAjaxURI(href + getAjaxSuffix()), data, function(error, response) {
                if (!response.body.success) {
                    modal.open({
                        content: response.body.html || response.body,
                        afterOpen: function(container) {
                            if (!response.body.html) { container.style({ width: '90%' }); }
                        }
                    });

                    element.data('title-editable', original).text(original);
                } else {
                    var dummy = zen('div').html(response.body.position);

                    parent.html(dummy.find('[id]').html());

                    var editables = parent.search('[data-title-editable]');
                    attachEditables(editables);
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

    // Toggle all assignments on/off
    body.delegate('change', '[data-positions-assignments] input[type="hidden"]', function(event, element) {
        var card = element.parent('.card'),
            wrapper = card.find('.settings-param-wrapper');

        wrapper[element.value() == 1 ? 'addClass' : 'removeClass']('hide');
        wrapper.search('input[type="hidden"]').forEach(function(element) {
            element = $(element);
            element.value(0).disabled(true);
        });
    });

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
