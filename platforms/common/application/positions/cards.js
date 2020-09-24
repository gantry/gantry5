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
    state: [],

    init: function(position) {
        Positions.state = Positions.serialize(position);

        return Positions.state;
    },

    equals: function() {
        return Positions.state === Positions.serialize();
    },

    updatePendingChanges: function() {
        var different = false,

            equals    = Positions.equals(),
            save      = $('[data-save="Positions"]'),
            icon      = save.find('i'),
            indicator = save.find('.changes-indicator');

        if (equals && indicator) { save.hideIndicator(); }
        if (!equals && !indicator) { save.showIndicator('changes-indicator far fa-fw fa-circle') }
        flags.set('pending', !equals);
    },

    serialize: function(position) {
        var data,
            output    = [],
            positions = $(position) || $('[data-g5-position]');

        if (!positions) {
            return '[]';
        }

        positions.forEach(function(position) {
            position = $(position);
            data = JSON.parse(position.data('g5-position'));
            data.modules = [];

            // collect positions items
            (position.search('[data-pm-data]') || []).forEach(function(item) {
                item = $(item);
                data.modules.push(JSON.parse(item.data('pm-data') || '{}'));
            });

            output.push(data);
            position.data('g5-position', JSON.stringify(data));
        });

        return JSON.stringify(output).replace(/\//g, '\\/');
    },

    attachEraser: function() {
        if (Positions.eraser) {
            Positions.eraser.element = $('[data-g5-positions-erase]');
            Positions.eraser.hide('fast');
            return;
        }

        Positions.eraser = new Eraser('[data-g5-positions-erase]');
    },

    createSortables: function(element) {
        var list, sort;

        Positions.attachEraser();

        groupOptions.forEach(function(groupOption, i) {
            list = !i ? '[data-g5-position] ul' : '#trash';
            list = $(list);

            list.forEach(function(element, listIndex) {
                sort = simpleSort.create(element, {
                    sort: !i,
                    filter: '[data-g5-position-ignore]',
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
                        var from  = $(event.from),
                            to    = $(event.to),
                            lists = [from.parent('[data-g5-position]'), to.parent('[data-g5-position]')];

                        if (event.from[0] === event.to[0]) {
                            lists.shift();
                        }

                        Positions.serialize(lists);
                        Positions.updatePendingChanges();
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
});

module.exports = Positions;