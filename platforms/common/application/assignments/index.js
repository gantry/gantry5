"use strict";

var ready        = require('elements/domready'),
    map          = require('prime/map')(),
    merge        = require('mout/object/merge'),
    forEach      = require('mout/array/forEach'),
    trim         = require('mout/string/trim'),
    $            = require('../utils/elements.utils'),
    decouple     = require('../utils/decouple'),
    asyncForEach = require('../utils/async-foreach');

var Map         = map,
    Assignments = {
        toggleSection: function(e, element, index, array) {
            if (e.type.match(/^touch/)) { e.preventDefault(); }
            if (element.siblings('[data-g-global-filter]') || element.parent('[data-g-global-filter]')) { return Assignments.globalToggleSection(e, element); }
            if (element.matches('label')) { return Assignments.treatLabel(e, element); }

            var card    = element.parent('.card'),
                toggles = Map.get(card),
                save    = $('[data-save]'),
                mode    = element.data('g-assignments-check') == null ? 0 : 1;

            if (!toggles || !toggles.inputs) {
                var inputs = card.search('.enabler input[type=hidden]');

                if (!toggles) { toggles = Map.set(card, { inputs: inputs }).get(card); }
                if (!toggles.inputs) { toggles = Map.set(card, merge(Map.get(card), { inputs: inputs })).get(card); }
            }

            // if necessary we should move to asyncForEach for an asynchronous loop, else forEach
            asyncForEach(toggles.inputs, function(item) {
                item = $(item);

                if (item.parent('label, h4').compute('display') == 'none') { return; }

                item.value(mode).emit('change');
                $('body').emit('change', { target: item });
            }, function() {
                if (typeof index !== 'undefined' && typeof array !== 'undefined' && (index + 1 == array.length)) {
                    save.disabled(false);
                }
            });
        },

        filterSection: function(e, element, value, global) {
            if (element.siblings('[data-g-global-filter]') || element.parent('[data-g-global-filter]')) { return Assignments.globalFilterSection(e, element); }

            var card        = element.parent('.card'),
                onlyEnabled = $('[data-assignments-enabledonly]'),
                items       = Map.get(card) || Map.set(card, { labels: card.search('label .settings-param-title') }).get(card);

            value = value || element.value();

            if (!items || !items.labels) {
                var labels = card.search('label .settings-param-title');

                if (!items) { items = Map.set(card, { labels: labels }).get(card); }
                if (!items.labels) { items = Map.set(card, merge(Map.get(card), { labels: labels })).get(card); }
            }

            items = $(items.labels);

            if (!value && !onlyEnabled.checked()) {
                card.style('display', 'inline-block');
                return items ? items.search('!> label').style('display', 'block') : items;
            }

            var count = 0, off = 0, on = 0, text, match;

            if (!items) {
                element.parent('.card').style('display', onlyEnabled.checked() || value ? 'none' : 'inline-block');
            }

            asyncForEach(items, function(item, i) {
                item = $(item);
                text = trim(item.text());
                match = text.match(new RegExp("^" + value + '|\\s' + value, 'gi'));

                if (onlyEnabled.checked()) {
                    match = Number(!!match) & Number(item.parent('label, h4').find('.enabler input[type="hidden"]').value());
                }

                if (match) {
                    var group = item.parent('[data-g-assignments-parent]');
                    if (group && (group = group.data('g-assignments-parent'))) {
                        var parentGroup = item.parent('.card').find('[data-g-assignments-group="' + group + '"]');
                        if (parentGroup) { parentGroup.style('display', 'block'); }
                    }

                    item.parent('label, h4').style('display', 'block');
                    on++;
                } else {
                    item.parent('label, h4').style('display', 'none');
                    off++;
                }

                count++;
                if (count == items.length && global) {
                    card.style('display', !on ? 'none' : 'inline-block');
                }
            });
        },

        filterEnabledOnly: function(e, element) {
            var global = $('[data-g-global-filter] input[type="text"]');
            Assignments.globalFilterSection(e, global, element);
        },

        treatLabel: function(event, element) {
            if (event && event.stopPropagation && event.preventDefault) {
                event.stopPropagation();
                event.preventDefault();
            }

            if ($(event.target).matches('.knob, .toggle')) { return; }
            var input = element.find('input[type="hidden"]:not([disabled])');
            if (!input) { return; }

            var value = input.value();
            value = !!+value;
            input.value(Number(!value)).emit('change');
            $('body').emit('change', { target: input });

            return false;
        },

        globalToggleSection: function(e, element) {
            var mode   = element.data('g-assignments-check') == null ? '[data-g-assignments-uncheck]' : '[data-g-assignments-check]',
                save   = $('[data-save]'),
                search = $('#assignments .card ' + mode + ', ' + '.settings-assignments .card ' + mode);

            if (!search) { return; }

            save.disabled(true);
            // if necessary we should move to asyncForEach for an asynchronous loop
            asyncForEach(search, function(item, index, array) {
                Assignments.toggleSection(e, $(item), index, array);
            });
        },

        globalFilterSection: function(e, element) {
            var value       = element.value(),
                onlyEnabled = $('[data-assignments-enabledonly]'),
                search      = $('#assignments .card .search input[type="text"], .settings-assignments .card .search input[type="text"]');

            if (!search && !onlyEnabled.checked()) { return; }

            asyncForEach(search, function(item) {
                Assignments.filterSection(e, $(item), value, 'global');
            });
        },

        toggleStateDelegation: function(event, element) {
            var enabled = element.value() == '1';
            element.attribute('disabled', !enabled);
        },

        // chrome workaround for overflow and columns
        chromeFix: function() {
            if (!Assignments.isChrome()) { return; }
            var panels = $('#assignments .settings-param-wrapper, .settings-assignments .settings-param-wrapper'), height, maxHeight;
            if (!panels) { return; }

            panels.forEach(function(panel){
                panel = $(panel);
                maxHeight = parseInt(panel.compute('max-height'), 10);
                height = panel[0].getBoundingClientRect().height;
                panel.style({overflow: height >= maxHeight ? 'auto' : 'visible'});

                if (height >= maxHeight) {
                    var alt = 100;
                    decouple(panel, 'scroll', function() {
                        alt = alt == 100 ? 100.01 : 100;
                        panel.parent('.card').style('width', alt + '%');
                    });
                }
            });
        },

        isChrome: function() {
            return navigator.userAgent.toLowerCase().indexOf('chrome') > -1;
        }
    };

ready(function() {
    var body = $('body');

    body.delegate('input', '#assignments .search input[type="text"], .settings-assignments .search input[type="text"]', Assignments.filterSection);
    body.delegate('click', '#assignments .card label, #assignments [data-g-assignments-check], #assignments [data-g-assignments-uncheck], .settings-assignments .card label, .settings-assignments [data-g-assignments-check], .settings-assignments [data-g-assignments-uncheck]', Assignments.toggleSection);
    body.delegate('touchend', '#assignments .card label, #assignments [data-g-assignments-check], #assignments [data-g-assignments-uncheck], .settings-assignments .card label, .settings-assignments [data-g-assignments-check], .settings-assignments [data-g-assignments-uncheck]', Assignments.toggleSection);
    body.delegate('change', '[data-assignments-enabledonly]', Assignments.filterEnabledOnly);
    body.delegate('change', '#assignments input[type="hidden"][name], .settings-assignments input[type="hidden"][name]', Assignments.toggleStateDelegation);

    // chrome workaround for overflow and columns
    //if (Assignments.isChrome()) Assignments.chromeFix();
});

module.exports = Assignments;
