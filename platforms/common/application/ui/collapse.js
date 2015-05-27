"use strict";
var ready = require('elements/domready'),
    $     = require('elements');

var refreshCards = function() {
    var collapsers = $('[data-g-collapse]'), data, handle;
    if (!collapsers) { return false; }

    collapsers.forEach(function(collapser) {
        collapser = $(collapser);
        data = JSON.parse(collapser.data('g-collapse'));
        handle = data.handle ? collapser.find(data.handle) : collapser.find('.g-collapse');
        collapser.gFastCollapse = true;
        $('body').emit('click', { target: handle, element: collapser });
    });
};

ready(function() {
    var data, target, storage;
    $('body').delegate('click', '[data-g-collapse]', function(event, element) {
        element = event.element || element;

        data = JSON.parse(element.data('g-collapse'));
        target = $(event.target);
        storage = JSON.parse(localStorage.getItem('g5-collapsed') || '{}');
        if (!data.handle) { data.handle = element.find('.g-collapse'); }

        if (!target.matches(data.handle) && !target.parent(data.handle)) { return false; }

        if (storage[data.id] === undefined) {
            storage[data.id] = data.collapsed;
            localStorage.setItem('g5-collapsed', JSON.stringify(storage));
        }

        var collapsed = storage[data.id],
            panel = data.target ? element.find(data.target) : element,
            card = panel.parent('.card');

        if (card && card.hasClass('g-collapsed')) {
            card.removeClass('g-collapsed');
            panel.style({
                overflow: 'hidden',
                height: 0
            });
        }

        var slide = function(override) {
            collapsed = typeof override != 'number' ? override : collapsed;
            if (!collapsed) {
                card.addClass('g-collapsed');
                element.attribute('style', null);
            }

            data.handle.data('title', !collapsed ? data.expand : data.collapse);
            storage[data.id] = !collapsed;
            localStorage.setItem('g5-collapsed', JSON.stringify(storage));
        };

        if (element.gFastCollapse) {
            panel[collapsed ? 'removeClass' : 'addClass']('g-collapsed');
            slide(!collapsed);
        } else {
            panel.removeClass('g-collapsed')[collapsed ? 'slideDown' : 'slideUp'](slide);
        }

        element.gFastCollapse = false;
    });

    refreshCards();
});

module.exports = refreshCards;