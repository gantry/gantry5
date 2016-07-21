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

    Eraser        = require('../ui/eraser'),
    simpleSort    = require('sortablejs'),

    flags         = require('../utils/flags-state');


var PositionsField = '[name="page[head][atoms][_json]"]',
    groupOptions   = [
        { name: 'positions', pull: true, put: true },
        { name: 'positions', pull: false, put: false }
    ];

var Positions = {
    eraser: null,
    lists: [],

    serialize: function() {
        var output = [],
            positions  = $('[data-position]');

        if (!positions) {
            return '[]';
        }

        positions.forEach(function(item) {
            item = $(item);
            output.push(JSON.parse(item.data('position')));
        });

        return JSON.stringify(output).replace(/\//g, '\\/');
    },

    attachEraser: function() {
        if (Positions.eraser) {
            Positions.eraser.element = $('[data-positions-erase]');
            Positions.eraser.hide('fast');
            return;
        }

        Positions.eraser = new Eraser('[data-positions-erase]');
    },

    createSortables: function(element) {
        var list, sort;

        Positions.attachEraser();

        groupOptions.forEach(function(groupOption, i) {
            list = !i ? '[data-position] ul' : '#trash';
            list = $(list);

            list.forEach(function(element, listIndex){
                sort = simpleSort.create(element, {
                    sort: !i,
                    filter: '[data-position-ignore]',
                    group: groupOption,
                    scroll: true,
                    forceFallback: true,
                    animation: 100,

                    onStart: function(event) {
                        Positions.attachEraser();

                        var item = $(event.item);
                        item.addClass('position-dragging');

                        Positions.eraser.show();
                    },

                    onEnd: function(event) {
                        var item       = $(event.item),
                            trash      = $('#trash'),
                            target     = $(this.originalEvent.target),
                            touchTrash = false;

                        // workaround for touch devices
                        if (this.originalEvent.type === 'touchend') {
                            var trashSize = trash[0].getBoundingClientRect(),
                                oE        = this.originalEvent,
                                position  = (oE.pageY || oE.changedTouches[0].pageY) - window.scrollY;

                            touchTrash = position <= trashSize.height;
                        }

                        if (target.matches('#trash') || target.parent('#trash') || touchTrash) {
                            item.remove();
                            Positions.eraser.hide();
                            this.options.onSort(event);
                            return;
                        }

                        item.removeClass('position-dragging');

                        Positions.eraser.hide();
                    },

                    onSort: function(event) {
                        var from = $(event.from),
                            to = $(event.to),
                            lists = [from.parent('[data-position]'), to.parent('[data-position]')];

                        if (event.from[0] === event.to[0]) {
                            lists.shift();
                        }

                        lists.forEach(function(list) {
                            list = $(list);

                            var data = JSON.parse(list.data('position'));

                            data.modules = (list.search('[data-pm-data]') || []).map(function(item) {
                                item = $(item);

                                return item.data('pm-data');
                            });

                            list.data('position', JSON.stringify(data));
                        });
                    },

                    onOver: function(event) {
                        if (!$(event.from).matches('ul')) { return; }

                        var over = $(event.newIndex);
                        if (over.matches('#trash') || over.parent('#trash')) {
                            Positions.eraser.over();
                        } else {
                            Positions.eraser.out();
                        }
                    }
                });

                if (!i) {
                    if (!Positions.lists[listIndex]) {
                        Positions.lists[listIndex] = sort;
                    }
                }
            });

            if (!i) {
                element.SimpleSort = sort;
            }
        });
    }
};

var AttachSettings = function() {
    var body = $('body');

    body.delegate('click', '.atoms-list [data-atom-picked] .config-cog', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        var list      = element.parent('ul'),
            dataField = $(PositionsField),
            data      = dataField.value(),
            items     = list.search('> [data-atom-picked]'),
            item      = element.parent('[data-atom-picked]'),
            itemData  = item.data('atom-picked');

        modal.open({
            content: translate('GANTRY5_PLATFORM_JS_LOADING'),
            method: 'post',
            data: { data: itemData },
            overlayClickToClose: false,
            remote: parseAjaxURI(element.attribute('href') + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                var form      = content.elements.content.find('form'),
                    fakeDOM   = zen('div').html(response.body.html).find('form'),
                    submit    = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]'),
                    dataValue = JSON.parse(data);

                if (modal.getAll().length > 1) {
                    var applyAndSave = content.elements.content.search('[data-apply-and-save]');
                    if (applyAndSave) { applyAndSave.remove(); }
                }

                if ((!form && !fakeDOM) || !submit) {
                    return true;
                }

                // Atom Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();

                    var target = $(e.target);

                    target.hideIndicator();
                    target.showIndicator();

                    // Refresh the form to collect fresh and dynamic fields
                    var formElements = content.elements.content.find('form')[0].elements;
                    var post = Submit(formElements, content.elements.content);

                    if (post.invalid.length) {
                        target.hideIndicator();
                        target.showIndicator('fa fa-fw fa-exclamation-triangle');
                        toastr.error(translate('GANTRY5_PLATFORM_JS_REVIEW_FIELDS'), 'GANTRY5_PLATFORM_JS_INVALID_FIELDS');
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
                            var index = indexOf(items, item[0]);
                            dataValue[index] = response.body.item;

                            dataField.value(JSON.stringify(dataValue).replace(/\//g, '\\/'));
                            item.find('.atom-title').text(dataValue[index].title);
                            item.data('atom-picked', JSON.stringify(dataValue[index]).replace(/\//g, '\\/'));

                            // toggle enabled/disabled status as needed
                            var enabled    = Number(dataValue[index].attributes.enabled),
                                inheriting = response.body.item.inherit && size(response.body.item.inherit);
                            item[enabled ? 'removeClass' : 'addClass']('atom-disabled');
                            item[!inheriting ? 'removeClass' : 'addClass']('g-inheriting');
                            item.attribute('title', enabled ? '' : translate('GANTRY5_PLATFORM_JS_LM_DISABLED_PARTICLE', 'atom'));

                            item.data('tip', null);
                            if (inheriting) {
                                var inherit = response.body.item.inherit,
                                    outline = getOutlineNameById(inherit ? inherit.outline : null),
                                    atom    = inherit.atom || '',
                                    include = (inherit.include || []).join(', ');

                                item.data('tip', translate('GANTRY5_PLATFORM_INHERITING_FROM_X', '<strong>' + outline + '</strong>') + '<br />ID: ' + atom + '<br />Replace: ' + include);
                            }

                            body.emit('change', { target: dataField });
                            global.G5.tips.reload();

                            // if it's apply and save we also save the panel
                            if (target.data('apply-and-save') !== null) {
                                var save = $('body').find('.button-save');
                                if (save) { body.emit('click', { target: save }); }
                            }

                            modal.close();
                            toastr.success(translate('GANTRY5_PLATFORM_JS_GENERIC_SETTINGS_APPLIED', 'Atom'), translate('GANTRY5_PLATFORM_JS_SETTINGS_APPLIED'));
                        }

                        target.hideIndicator();
                    });
                });
            }
        });
    });
};

var AttachSortablePositions = function(positions) {
    if (!positions) { return; }
    if (!positions.SimpleSort) { Positions.createSortables(positions); }
};

ready(function() {
    var positions = $('#positions');

    $('body').delegate('mouseover', '#positions', function(event, element) {
        AttachSortablePositions(element);
    });

    AttachSortablePositions(positions);
    AttachSettings();
});

module.exports = Positions;