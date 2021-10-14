"use strict";
var ready  = require('elements/domready'),
    trim   = require('mout/string/trim'),
    forOwn = require('mout/object/forOwn'),
    $      = require('elements'),
    Cookie = require('../utils/cookie');


var loadFromStorage = function() {
    var storage    = Cookie.read('g5-collapsed') || {},
        collapsers = $('[data-g-collapse]');
    if (!collapsers) { return false; }

    var item, data, handle, panel, card;
    forOwn(storage, function(value, key) {
        item = $('[data-g-collapse-id="' + key + '"]');
        if (!item) { return; }

        data = JSON.parse(item.data('g-collapse'));
        handle = data.handle ? item.find(data.handle) : item.find('.g-collapse');
        panel = data.target ? item.find(data.target) : item;
        card = item.parent('.card') || panel;
        handle
            .data('title', value ? data.expand : data.collapse)
            .data('tip', value ? data.expand : data.collapse);

        panel.attribute('style', null);
        card[!value ? 'removeClass' : 'addClass']('g-collapsed');
        item[!value ? 'removeClass' : 'addClass']('g-collapsed-main');
    });
};

ready(function() {
    var body = $('body'), data, target, storage;

    // single collapser
    body.delegate('click', '[data-g-collapse]', function(event, element) {
        element = event.element || element;

        data = JSON.parse(element.data('g-collapse'));
        target = $(event.target);
        storage = ((data.store !== false) ? Cookie.read('g5-collapsed') : storage) || {};
        if (!data.handle) { data.handle = element.find('.g-collapse'); }

        if (!target.matches(data.handle) && !target.parent(data.handle)) { return false; }

        if (storage[data.id] === undefined) {
            storage[data.id] = data.collapsed;

            if (data.store !== false) { Cookie.write('g5-collapsed', storage); }
        }

        var collapsed = storage[data.id],
            panel     = data.target ? element.find(data.target) : element,
            card      = panel.parent('.card') || panel;

        if (card && card.hasClass('g-collapsed')) {
            card.removeClass('g-collapsed');
            element.removeClass('g-collapsed-main');
            /* for animations
             panel.style({
             overflow: 'hidden',
             height: 0
             });*/
        }

        var slide = function(override) {
            collapsed = typeof override !== 'number' ? override : collapsed;
            if (!collapsed) {
                card.addClass('g-collapsed');
                element.addClass('g-collapsed-main');
                element.attribute('style', null);
            }

            data.handle
                .data('title', !collapsed ? data.expand : data.collapse)
                .data('tip', !collapsed ? data.expand : data.collapse);
            storage[data.id] = !collapsed;
            data.collapsed = !collapsed;

            var refreshData = JSON.parse(element.data('g-collapse'));
            refreshData.collapsed = !collapsed;
            element.data('g-collapse', JSON.stringify(refreshData));

            if (data.store !== false) {
                Cookie.write('g5-collapsed', storage);
            }
        };

        if (element.gFastCollapse) {
            panel[collapsed ? 'removeClass' : 'addClass']('g-collapsed');
            element[collapsed ? 'removeClass' : 'addClass']('g-collapsed-main');
            slide(collapsed);
        } else {
            element.removeClass('g-collapsed-main');
            // for animations
            // panel.removeClass('g-collapsed')[collapsed ? 'slideDown' : 'slideUp'](slide);
            panel.removeClass('g-collapsed')[collapsed ? 'removeClass' : 'addClass']('g-collapsed');
            slide(collapsed);
        }

        element.gFastCollapse = false;
    });

    // global collapse togglers
    body.delegate('click', '[data-g-collapse-all]', function(event, element) {
        var mode          = element.data('g-collapse-all') === 'true',
            parent        = element.parent('.g-filter-actions'),
            container     = parent.nextSibling(),
            collapsers    = container.search('[data-g-collapse]'),
            CookieStorage = Cookie.read('g5-collapsed') || {},
            panel, data, handle, card, inner;

        if (!collapsers) { return; }

        collapsers.forEach(function(collapser) {
            collapser = $(collapser);
            card = collapser.parent('.card');
            inner = card.find('> .g-collapsed');
            data = JSON.parse(collapser.data('g-collapse'));
            handle = data.handle ? collapser.find(data.handle) : collapser.find('.g-collapse');
            panel = data.target ? collapser.find(data.target) : collapser;

            handle
                .data('title', mode ? data.expand : data.collapse)
                .data('tip', mode ? data.expand : data.collapse);

            storage = ((data.store !== false) ? CookieStorage : storage) || {};

            storage[data.id] = mode;
            if (data.store !== false) {
                Cookie.write('g5-collapsed', storage);
            }

            panel.attribute('style', null);
            collapser[!mode ? 'removeClass' : 'addClass']('g-collapsed-main');
            card[!mode ? 'removeClass' : 'addClass']('g-collapsed');

            if (inner) {
                inner[!mode ? 'removeClass' : 'addClass']('g-collapsed');
            }
        });
    });

    // filter by card title
    body.delegate('input', '[data-g-collapse-filter]', function(event, element) {
        var filter    = JSON.parse(element.data('g-collapse-filter') || '{}'),
            parent    = element.parent('.g-filter-actions'),
            container = parent.nextSibling(),
            cards     = container.search(filter.element || '.card'),
            value     = element.value();

        if (!cards) { return; }

        if (!value) { cards.attribute('style', null); }
        cards.forEach(function(element, index) {
            element = $(element);
            var title   = trim(element.find(filter.title || 'h4 .g-title').text()),
                matches = title.match(new RegExp("^" + value + '|\\s' + value, 'gi'));

            if (matches) { element.attribute('style', null); }
            else { element.style('display', 'none'); }
        });
    });

    // this is now handled from the twig files
    // no need to run on domready
    //loadFromStorage();
});

module.exports = loadFromStorage;
