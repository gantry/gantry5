"use strict";

var ready   = require('elements/domready'),
    map     = require('prime/map')(),
    merge   = require('mout/object/merge'),
    forEach = require('mout/array/forEach'),
    trim    = require('mout/string/trim'),
    $       = require('../utils/elements.utils');

var eachAsync = function(array, fn) {
    var i = 0;
    (function tmp() {
        fn(array[i]);
        if (++i < array.length) { window.requestAnimationFrame(tmp, 0); }
    })();
};

var Map         = map,
    Assignments = {
        toggleSection: function(e, element) {
            if (e.type.match(/^touch/)) { e.preventDefault(); }
            if (element.parent('[data-g-global-filter]')) { return Assignments.globalToggleSection(e, element); }
            if (element.matches('label')) { return Assignments.treatLabel(e, element); }

            var card = element.parent('.card'),
                toggles = Map.get(card),
                mode = element.data('g-assignments-check') == null ? 0 : 1;

            if (!toggles || !toggles.inputs) {
                var inputs = card.search('.enabler input[type=hidden]');

                if (!toggles) { toggles = Map.set(card, { inputs: inputs }).get(card); }
                if (!toggles.inputs) { toggles = Map.set(card, merge(Map.get(card), { inputs: inputs })).get(card); }
            }

            eachAsync(toggles.inputs, function(item) {
                item = $(item);

                if (item.parent('label').compute('display') == 'none') { return; }

                item.value(mode).emit('change');
                $('body').emit('change', { target: item });
            });
        },

        filterSection: function(e, element, value) {
            if (element.parent('[data-g-global-filter]')) { return Assignments.globalFilterSection(e, element); }

            var card = element.parent('.card'),
                items = Map.get(card) || Map.set(card, { labels: card.search('label .settings-param-title') }).get(card);

            value = value || element.value();

            if (!items || !items.labels) {
                var labels = card.search('label .settings-param-title');

                if (!items) { items = Map.set(card, { labels: labels }).get(card); }
                if (!items.labels) { items = Map.set(card, merge(Map.get(card), { labels: labels })).get(card); }
            }

            items = $(items.labels);

            if (!value) { return items.search('!> label').style('display', 'block'); }

            forEach(items, function(item) {
                item = $(item);
                var text = trim(item.text());
                if (text.match(new RegExp("^" + value + '|\\s' + value, 'gi'))) {
                    item.parent('label').style('display', 'block');
                } else {
                    item.parent('label').style('display', 'none');
                }
            });
        },

        treatLabel: function(event, element) {
            if ($(event.target).matches('.knob, .toggle')) { return; }
            var input = element.find('input[type="hidden"]'),
                value = input.value();

            value = !!+value;
            input.value(Number(!value)).emit('change');
            $('body').emit('change', { target: input });
        },

        globalToggleSection: function(e, element) {
            var mode = element.data('g-assignments-check') == null ? '[data-g-assignments-uncheck]' : '[data-g-assignments-check]',
                search = $('#assignments .card ' + mode);

            if (!search) { return; }

            eachAsync(search, function(item){
                Assignments.toggleSection(e, $(item));
            });
        },

        globalFilterSection: function(e, element) {
            var value = element.value(),
                search = $('#assignments .card .search input[type="text"]');

            if (!search) { return; }

            forEach(search, function(item){
                Assignments.filterSection(e, $(item), value);
            });
        }
    };

ready(function() {
    var body = $('body');

    body.delegate('input', '#assignments .search input[type="text"]', Assignments.filterSection);
    body.delegate('click', '#assignments .card label, #assignments [data-g-assignments-check], #assignments [data-g-assignments-uncheck]', Assignments.toggleSection);
    body.delegate('touchend', '#assignments .card label, #assignments [data-g-assignments-check], #assignments [data-g-assignments-uncheck]', Assignments.toggleSection);
});

module.exports = {};