"use strict";
var $              = require('elements'),
    zen            = require('elements/zen'),
    ready          = require('elements/domready'),
    request        = require('agent'),
    ui             = require('./ui'),
    interpolate    = require('mout/string/interpolate'),
    trim           = require('mout/string/trim'),
    setParam       = require('mout/queryString/setParam'),
    modal          = ui.modal,
    toastr         = ui.toastr,

    parseAjaxURI   = require('./utils/get-ajax-url').parse,
    getAjaxURL     = require('./utils/get-ajax-url').global,
    getAjaxSuffix  = require('./utils/get-ajax-suffix'),

    flags          = require('./utils/flags-state'),
    validateField  = require('./utils/field-validation'),
    lm             = require('./lm'),
    mm             = require('./menu'),
    configurations = require('./configurations'),
    positions      = require('./positions'),
    changelog      = require('./changelog');

require('elements/attributes');
require('elements/events');
require('elements/delegation');
require('elements/insertion');
require('elements/traversal');
require('./fields');
require('./ui/popover');
require('./utils/ajaxify-links');
require('./utils/rAF-polyfill');

var createHandler = function(divisor, noun, restOfString) {
    return function(diff) {
        var n = Math.floor(diff / divisor);
        var pluralizedNoun = noun + ( n > 1 ? 's' : '' );
        return "" + n + " " + pluralizedNoun + " " + restOfString;
    }
};

var formatters = [
    { threshold: -31535999, handler: createHandler(-31536000,	"year",     "from now" ) },
    { threshold: -2591999, 	handler: createHandler(-2592000,  	"month",    "from now" ) },
    { threshold: -604799,  	handler: createHandler(-604800,   	"week",     "from now" ) },
    { threshold: -172799,   handler: createHandler(-86400,    	"day",      "from now" ) },
    { threshold: -86399,   	handler: function(){ return      	"tomorrow" } },
    { threshold: -3599,    	handler: createHandler(-3600,     	"hour",     "from now" ) },
    { threshold: -59,     	handler: createHandler(-60,       	"minute",   "from now" ) },
    { threshold: -0.9999,   handler: createHandler(-1,			"second",   "from now" ) },
    { threshold: 1,        	handler: function(){ return      	"just now" } },
    { threshold: 60,       	handler: createHandler(1,        	"second",	"ago" ) },
    { threshold: 3600,     	handler: createHandler(60,       	"minute",	"ago" ) },
    { threshold: 86400,    	handler: createHandler(3600,     	"hour",     "ago" ) },
    { threshold: 172800,   	handler: function(){ return      	"yesterday" } },
    { threshold: 604800,   	handler: createHandler(86400,    	"day",      "ago" ) },
    { threshold: 2592000,  	handler: createHandler(604800,   	"week",     "ago" ) },
    { threshold: 31536000, 	handler: createHandler(2592000,  	"month",    "ago" ) },
    { threshold: Infinity, 	handler: createHandler(31536000, 	"year",     "ago" ) }
];

var prettyDate = {
    format: function(date) {
        var diff = (((new Date()).getTime() - date.getTime()) / 1000);
        for (var i = 0; i < formatters.length; i++) {
            if (diff < formatters[i].threshold) {
                return formatters[i].handler(diff);
            }
        }
        throw new Error("exhausted all formatter options, none found"); //should never be reached
    }
};

window.onbeforeunload = function() {
    if (flags.get('pending')) {
        return 'You haven\'t saved your changes and by leaving the page they will be lost.\nDo you want to leave without saving?';
    }
};

ready(function() {
    var body     = $('body'),
        sentence = 'The {{type}} {{verb}} been successfully saved! {{extras}}';

    // Close notification
    body.delegate('click', '[data-g-close]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var parent = element.data('g-close');
        parent = parent ? element.parent(parent) : element;

        parent.slideUp(function() {
            parent.remove();
        });
    });

    // Extras
    body.delegate('click', '[data-g-extras]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        if (!element.PopoverDefined) {
            var content = element.find('[data-popover-content]') || element.siblings('[data-popover-content]'),
                popover = element.getPopover({
                    style: 'extras',
                    width: 220,
                    content: zen('ul').html(content.html())[0].outerHTML,
                    allowElementsClick: '.toggle'
                });
            element.on('shown.popover', function(popover){
                element.attribute('aria-expanded', true).attribute('aria-hidden', false);
                element.find('.enabler')[0].focus();
            });

            element.on('hide.popover', function(popover){
                element.attribute('aria-expanded', false).attribute('aria-hidden', true);
            });

            element.getPopover().show();
        }
    });

    // Platform Settings redirect
    body.delegate('mousedown', '[data-settings-key]', function(event, element) {
        var key = element.data('settings-key');
        if (!key) { return true; }

        var redirect = window.location.search,
            settings = element.attribute('href'),
            uri      = window.location.href.split('?');
        if (uri.length > 1 && uri[0].match(/index.php$/)) { redirect = 'index.php' + redirect; }

        redirect = setParam(settings, key, btoa(redirect));
        element.href(redirect);
    });

    // Save Tooltip
    body.delegate('mouseover', '.button-save', function(event, element) {
        if (!element.lastSaved) { return true; }
        var feedback = 'Last Saved: ' + prettyDate.format(element.lastSaved);
        element
            .data('tip', feedback)
            .data('title', feedback);
    });

    // Save
    body.delegate('click', '.button-save', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        var saves = $('.button-save');

        if (saves.disabled()) {
            return false;
        }

        saves.disabled(true);
        saves.hideIndicator();
        saves.showIndicator();

        var data    = {},
            invalid = [],
            type    = element.data('save'),
            extras  = '',
            page    = $('[data-lm-root]') ? 'layout' : ($('[data-mm-id]') ? 'menu' : 'other'),
            saveURL = parseAjaxURI(trim(window.location.href, '#') + getAjaxSuffix());

        switch (page) {
            case 'layout':
                var preset = $('[data-lm-preset]');
                lm.layoutmanager.singles('cleanup', lm.builder, true);
                lm.savestate.setSession(lm.builder.serialize(null, true));

                data.preset = preset && preset.data('lm-preset') ? preset.data('lm-preset') : 'default';

                var layout = JSON.stringify(lm.builder.serialize());

                // base64 encoding doesn't quite work with mod_security
                // data.layout = btoa ? btoa(encodeURIComponent(layout)) : layout;

                data.layout = layout;
                break;

            case 'menu':
                data.menutype = $('select.menu-select-wrap').value();
                data.settings = JSON.stringify(mm.menumanager.settings);
                data.ordering = JSON.stringify(mm.menumanager.ordering);

                var items = JSON.stringify(mm.menumanager.items);

                // base64 encoding doesn't quite work with mod_security
                // data.items = btoa ? btoa(encodeURIComponent(items)) : items;

                data.items = items;

                saveURL = parseAjaxURI(element.parent('form').attribute('action') + getAjaxSuffix());
                break;

            case 'other':
            default:
                var form = element.parent('form');

                if (form && element.attribute('type') == 'submit') {
                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name     = input.attribute('name'),
                            type     = input.attribute('type'),
                            value    = input.value(),
                            parent   = input.parent('.settings-param, .card-overrideable'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        override = override || $(input.data('override-target'));

                        if (!name || input.disabled() || (override && !override.checked()) || (type == 'radio' && !input.checked())) { return; }
                        if (!validateField(input)) { invalid.push(input); }
                        data[name] = value;
                    });
                }
        }

        if (invalid.length) {
            saves.disabled(false);
            saves.hideIndicator();
            saves.showIndicator('fa fa-fw fa-exclamation-triangle');
            toastr.error('Please review the fields in the page and ensure you correct any invalid one.', 'Invalid Fields');
            return;
        }

        if (page == 'other') { $('.settings-param-title, .card.settings-block > h4').hideIndicator(); }
        body.emit('updateOriginalFields');

        request('post', saveURL, data, function(error, response) {
            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });
            } else {
                modal.close();

                if ($('#styles')) {
                    extras = '<br />' + (response.body.warning ? '<hr />' + response.body.title + '<br />' + response.body.html : 'The CSS was successfully compiled!');
                }

                toastr[response.body.warning ? 'warning' : 'success'](interpolate(sentence, {
                    verb: type.slice(-1) == 's' ? 'have' : 'has',
                    type: type,
                    extras: extras
                }), type + ' Saved');
            }

            saves.disabled(false);
            saves.hideIndicator();
            saves.forEach(function(save) {
                $(save).lastSaved = new Date();
            });

            if (page == 'layout') { lm.layoutmanager.updatePendingChanges(); }

            // all good, disable 'pending' flag
            flags.set('pending', false);
            flags.emit('update:pending');
        });
    });

    // Editable titles
    body.delegate('keydown', '[data-title-edit]', function(event, element) {
        var key = (event.which ? event.which : event.keyCode);
        if (key == 32 || key == 13) { // ARIA support: Space / Enter toggle
            event.preventDefault();
            body.emit('click', event);
        }
    });

    body.delegate('click', '[data-title-edit]', function(event, element) {
        element = $(element);
        if (element.hasClass('disabled')) { return false; }

        var $title = element.siblings('[data-title-editable]') || element.previousSiblings().find('[data-title-editable]') || element.nextSiblings().find('[data-title-editable]'), title;
        if (!$title) { return true; }

        title = $title[0];
        $title.text(trim($title.text()));

        $title.attribute('contenteditable', true);
        title.focus();

        var range = document.createRange(), selection;
        range.selectNodeContents(title);
        selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);

        $title.storedTitle = trim($title.text());
        $title.titleEditCanceled = false;
        $title.emit('title-edit-start', $title.storedTitle);
    });

    body.delegate('keydown', '[data-title-editable]', function(event, element) {
        element = $(element);
        switch (event.keyCode) {
            case 13: // return
            case 27: // esc
                event.stopPropagation();

                if (event.keyCode == 27) {
                    if (typeof element.storedTitle !== 'undefined') {
                        element.text(element.storedTitle);
                        element.titleEditCanceled = true;
                    }
                }

                element.attribute('contenteditable', null);
                element[0].blur();

                element.emit('title-edit-exit', element.data('title-editable'), event.keyCode == 13 ? 'enter' : 'esc');
                return false;
            default:
                return true;
        }
    });

    body.delegate('blur', '[data-title-editable]', function(event, element) {
        element = $(element);
        element[0].scrollLeft = 0;
        element.attribute('contenteditable', null);
        element.data('title-editable', trim(element.text()));
        window.getSelection().removeAllRanges();
        element.emit('title-edit-end', element.data('title-editable'), element.storedTitle, element.titleEditCanceled);
    }, true);

    // Quick Ajax Calls [data-ajax-action]
    body.delegate('click', '[data-ajax-action]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        var href      = element.attribute('href') || element.data('ajax-action'),
            method    = element.data('ajax-action-method') || 'post',
            indicator = $(element.data('ajax-action-indicator')) || element;

        if (!href) { return false; }

        indicator.showIndicator();
        request(method, parseAjaxURI(href + getAjaxSuffix()), function(error, response) {
            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });

                indicator.hideIndicator();
                return false;
            } else {
                toastr[response.body.warning ? 'warning' : 'success'](response.body.html || 'Action successfully completed.', response.body.title || '');
            }

            indicator.hideIndicator();
        })
    });
});

var modules = {
    /*mout    : require('mout'),
     prime   : require('prime'),
     "$"     : elements,
     zen     : zen,
     domready: domready,
     agent   : require('agent'),*/
    lm: lm,
    mm: mm,
    assingments: require('./assignments'),
    ui: require('./ui'),
    styles: require('./styles'),
    "$": $,
    domready: require('elements/domready'),
    particles: require('./particles'),
    zen: require('elements/zen'),
    moofx: require('moofx'),
    atoms: require('./pagesettings'),
    tips: require('./ui/tooltips')
};

window.G5 = modules;
module.exports = modules;
