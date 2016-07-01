"use strict";

var ready         = require('elements/domready'),
    $             = require('elements'),
    zen           = require('elements/zen'),
    Submit        = require('../../fields/submit'),
    modal         = require('../../ui').modal,
    toastr        = require('../../ui').toastr,
    request       = require('agent'),
    lastItem      = require('mout/array/last'),
    indexOf       = require('mout/array/indexOf'),
    simpleSort    = require('sortablejs'),

    trim          = require('mout/string/trim'),

    parseAjaxURI  = require('../../utils/get-ajax-url').parse,
    getAjaxSuffix = require('../../utils/get-ajax-suffix');

require('elements/insertion');

ready(function() {
    var body = $('body');

    var addNewByEnter = function(title, key) {
        if (key == 'enter' && this.CollectionNew) {
            this.CollectionNew = false;
            body.emit('click', { target: this.parent('.settings-param').find('[data-collection-addnew]') });
        }

        if (key == 'esc' && this.CollectionNew) {
            this.CollectionNew = false;
            body.emit('click', { target: this.parent('[data-collection-item]').find('[data-collection-remove]') });
        }
    };

    var createSortables = function(list) {
        var lists = list || $('.collection-list ul');
        if (!lists) { return; }
        lists.forEach(function(list) {
            list = $(list);
            list.SimpleSort = simpleSort.create(list[0], {
                handle: '.fa-reorder',
                filter: '[data-collection-nosort]',
                scroll: false,
                animation: 150,
                onStart: function() {
                    $(this.el).addClass('collection-sorting');
                },
                onEnd: function(evt) {
                    var element = $(this.el);
                    element.removeClass('collection-sorting');

                    if (evt.oldIndex === evt.newIndex) { return; }

                    var dataField = element.parent('.settings-param').find('[data-collection-data]'),
                        data = dataField.value();

                    data = JSON.parse(data);

                    data.splice(evt.newIndex, 0, data.splice(evt.oldIndex, 1)[0]);
                    dataField.value(JSON.stringify(data));
                    body.emit('change', { target: dataField });
                }
            });
        });
    };

    createSortables();

    // delegate sortables collections for ajax support
    body.delegate('mouseover', '.collection-list ul', function(event, element) {
        if (!element.SimpleSort) { createSortables(element); }
    });

    // Add new item
    body.delegate('click', '[data-collection-addnew]', function(event, element) {
        var param = element.parent('.settings-param'),
            list = param.find('ul'),
            editall = list.parent('[data-field-name]').find('[data-collection-editall]'),
            dataField = param.find('[data-collection-data]'),
            tmpl = param.find('[data-collection-template]'),
            items = list.search('> [data-collection-item]') || [],
            last = $(lastItem(items));

        var clone = $(tmpl[0].cloneNode(true)), title, editable;

        if (last) { clone.after(last); }
        else { clone.top(list); }
        
        if (items.length && editall) { editall.style('display', 'inline-block'); }

        title = clone.find('a');
        editable = title.find('[data-title-editable]');

        var re = new RegExp('%id%', 'g');
        title.href(title.href().replace(re, items.length));

        clone.attribute('style', null).data('collection-item', clone.data('collection-template'));
        clone.attribute('data-collection-template', null);
        clone.attribute('data-collection-nosort', null);
        editable.CollectionNew = true;
        body.emit('click', { target: title.siblings('[data-title-edit]') });

        editable.on('title-edit-exit', addNewByEnter);
        body.emit('change', { target: dataField });
    });

    // Edit Title
    body.delegate('blur', '[data-collection-item] [data-title-editable]', function(event, element) {
        var text = trim(element.text()),
            item = element.parent('[data-collection-item]'),
            key = item.data('collection-item'),
            items = element.parent('ul').search('> [data-collection-item]'),
            dataField = element.parent('.settings-param').find('[data-collection-data]'),
            data = dataField.value(),
            index = indexOf(items, item[0]);

        if (index == -1) { return; }

        data = JSON.parse(data);
        if (!data[index]) { data.splice(index, 0, {}); }
        data[index][key] = text;
        dataField.value(JSON.stringify(data));
        body.emit('change', { target: dataField });
    }, true);

    // Remove item
    body.delegate('click', '[data-collection-remove]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var item = element.parent('[data-collection-item]'),
            list = element.parent('ul'),
            items = list.search('> [data-collection-item]'),
            index = indexOf(items, item[0]),
            dataField = element.parent('.settings-param').find('[data-collection-data]'),
            data = dataField.value();

        data = JSON.parse(data);
        data.splice(index, 1);
        dataField.value(JSON.stringify(data));
        item.remove();
        if (items.length <= 2) { list.parent('[data-field-name]').find('[data-collection-editall]').style('display', 'none'); }
        body.emit('change', { target: dataField });
    });

    // Duplicate item
    body.delegate('click', '[data-collection-duplicate]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var param = element.parent('.settings-param'),
            item = element.parent('[data-collection-item]'),
            list = element.parent('ul'),
            url = param.find('[data-collection-template]').find('a').href(),
            items = list.search('> [data-collection-item]'),
            index = indexOf(items, item[0]),
            clone = $(item[0].cloneNode(true)).after(item),
            dataField = element.parent('.settings-param').find('[data-collection-data]'),
            data = dataField.value();

        var re = new RegExp('%id%', 'g');
        clone.find('a').href(url.replace(re, items.length + 1));

        data = JSON.parse(data);
        data.splice(index, 0, data[index]);
        dataField.value(JSON.stringify(data));

        if ((items.length + 1) <= 2) { list.parent('[data-field-name]').find('[data-collection-editall]').style('display', 'none'); }
        body.emit('change', { target: dataField });
    });

    // Preventing click of links when title is being edited
    body.delegate('click', '[data-collection-item] a', function(event, element) {
        if (element.find('[contenteditable]')) {
            event.preventDefault();
            event.stopPropagation();
        }
    });

    // Load item settings
    body.delegate('click', '[data-collection-item] .config-cog, [data-collection-editall]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        var editable = element.find('[data-title-editable]');
        if (editable && editable.attribute('contenteditable')) {
            event.stopPropagation();
            return false;
        }

        var isEditAll = element.data('collection-editall') !== null,
            parent = element.parent('.settings-param'),
            dataField = parent.find('[data-collection-data]'),
            data = dataField.value(),
            item = element.parent('[data-collection-item]'),
            items = parent.search('ul > [data-collection-item]');

        var dataPost = { data: isEditAll ? data : JSON.stringify(JSON.parse(data)[indexOf(items, item[0])]) };
        modal.open({
            content: 'Loading',
            method: 'post',
            className: 'g5-dialog-theme-default g5-modal-collection g5-modal-collection-' + (isEditAll ? 'editall' : 'single'),
            data: dataPost,
            overlayClickToClose: false,
            remote: parseAjaxURI(element.attribute('href') + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }
                
                var form = content.elements.content.find('form'),
                    fakeDOM = zen('div').html(response.body.html).find('form'),
                    submit = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]'),
                    dataValue = JSON.parse(data);

                if (modal.getAll().length > 1) {
                    var applyAndSave = content.elements.content.search('[data-apply-and-save]');
                    if (applyAndSave) { applyAndSave.remove(); }
                }

                if (dataValue.length == 1) {
                    // TODO: need to determine better how to handle single collections cards
                    //content.elements.content.style({ width: 450 });
                }

                if ((!form && !fakeDOM) || !submit) {
                    return true;
                }

                // Collection Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();

                    var target = $(e.target);

                    target.hideIndicator();
                    target.showIndicator();

                    var post = Submit(fakeDOM[0].elements, content.elements.content);

                    if (post.invalid.length) {
                        target.hideIndicator();
                        target.showIndicator('fa fa-fw fa-exclamation-triangle');
                        toastr.error('Please review the fields in the modal and ensure you correct any invalid one.', 'Invalid Fields');
                        return;
                    }

                    request(fakeDOM.attribute('method'), parseAjaxURI(fakeDOM.attribute('action') + getAjaxSuffix()), post.valid.join('&') || {}, function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            if (item) { // single editing
                                dataValue[indexOf(items, item[0])] = response.body.data;
                            } else { // multi editing
                                dataValue = response.body.data;
                            }

                            dataField.value(JSON.stringify(dataValue));
                            body.emit('change', { target: dataField });

                            element.parent('.settings-param-field').search('ul > [data-collection-item]').forEach(function(item, index) {
                                item = $(item);
                                var label = item.find('[data-title-editable]'),
                                    text = dataValue[index][item.data('collection-item')];

                                label.data('title-editable', text).text(text);
                            });

                            // if it's apply and save we also save the panel
                            if (target.data('apply-and-save') !== null) {
                                var save = $('body').find('.button-save');
                                if (save) { body.emit('click', { target: save }); }
                            }

                            modal.close();
                            toastr.success('Collection Item updated', 'Item Updated');
                        }

                        target.hideIndicator();
                    });
                });
            }
        });
    });
});

module.exports = {};
