var $             = require('elements'),
    ready         = require('elements/domready'),
    request       = require('agent'),
    ui            = require('./ui'),
    interpolate   = require('mout/string/interpolate'),
    trim          = require('mout/string/trim'),
    setParam      = require('mout/queryString/setParam'),
    modal         = ui.modal,
    toastr        = ui.toastr,

    getAjaxSuffix = require('./utils/get-ajax-suffix'),

    lm            = require('./lm'),
    mm            = require('./menu');

require('elements/attributes');
require('elements/events');
require('elements/delegation');
require('elements/insertion');
require('elements/traversal');
require('./fields');
require('./ui/popover');
require('./utils/ajaxify-links');

var createHandler = function(divisor,noun,restOfString){
    return function(diff){
        var n = Math.floor(diff/divisor);
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
    format: function (date) {
        var diff = (((new Date()).getTime() - date.getTime()) / 1000);
        for( var i=0; i<formatters.length; i++ ){
            if( diff < formatters[i].threshold ){
                return formatters[i].handler(diff);
            }
        }
        throw new Error("exhausted all formatter options, none found"); //should never be reached
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

    // Platform Settings redirect
    body.delegate('mousedown', '[data-settings-key]', function(event, element){
        var key = element.data('settings-key');
        if (!key) { return true; }

        var redirect = window.location.search,
            settings = element.attribute('href'),
            uri = window.location.href.split('?');
        if (uri.length > 1 && uri[0].match(/index.php$/)) { redirect = 'index.php' + redirect; }

        redirect = setParam(settings, key, btoa(redirect));
        element.href(redirect);
    });

    // Save Tooltip
    body.delegate('mouseover', '.button-save', function(event, element){
        if (!element.lastSaved) { return true; }
        element.addClass('g-tooltip').addClass('g-tooltip-right').data('title', 'Last Saved: ' + prettyDate.format(element.lastSaved));
    });

    // Save
    body.delegate('click', '.button-save', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        element.showIndicator();

        var data    = {},
            type    = element.data('save'),
            extras  = '',
            page    = $('[data-lm-root]') ? 'layout' : ($('[data-mm-id]') ? 'menu' : 'other'),
            saveURL = trim(window.location.href, '#') + getAjaxSuffix();

        switch (page) {
            case 'layout':
                lm.layoutmanager.singles('cleanup', lm.builder, true);
                lm.savestate.setSession(lm.builder.serialize(null, true));
                data.layout = JSON.stringify(lm.builder.serialize());

                break;
            case 'menu':
                data.menutype = $('select.menu-select-wrap').value();
                data.settings = JSON.stringify(mm.menumanager.settings);
                data.ordering = JSON.stringify(mm.menumanager.ordering);
                data.items = JSON.stringify(mm.menumanager.items);

                saveURL = element.parent('form').attribute('action') + getAjaxSuffix();
                break;

            case 'other':
            default:
                var form = element.parent('form');

                if (form && element.attribute('type') == 'submit') {
                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name     = input.attribute('name'),
                            value    = input.value(),
                            parent   = input.parent('.settings-param'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        if (!name || input.disabled() || (override && !override.checked())) { return; }
                        data[name] = value;
                    });
                }

                $('.settings-param-title, .card.settings-block > h4').hideIndicator();

                if ($('#styles')) { extras = '<br />The CSS was successfully compiled!'; }
        }

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
                toastr.success(interpolate(sentence, {
                    verb: type.slice(-1) == 's' ? 'have' : 'has',
                    type: type,
                    extras: extras
                }), type + ' Saved');
            }

            element.hideIndicator();
            element.lastSaved = new Date();

            if (page == 'layout') { lm.layoutmanager.updatePendingChanges(); }
        });
    });

    // Editable titles
    body.delegate('click', '[data-title-edit]', function(event, element) {
        element = $(element);
        var $title = element.siblings('[data-title-editable]') || element.previousSiblings().find('[data-title-editable]') || element.nextSiblings().find('[data-title-editable]'), title;
        if (!$title) { return true; }

        title = $title[0];

        $title.attribute('contenteditable', true);
        title.focus();

        var range = document.createRange(), selection;
        range.selectNodeContents(title);
        selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);

        $title.storedTitle = trim($title.text());
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
                    }
                }

                element.attribute('contenteditable', null);
                window.getSelection().removeAllRanges();
                element[0].blur();

                element.emit('title-edit-exit', element.data('title-editable'), event.keyCode == 13 ? 'enter' : 'esc');
                return false;
            default:
                return true;
        }
    });

    body.delegate('blur', '[data-title-editable]', function(event, element) {
        element = $(element);
        element.attribute('contenteditable', null);
        element.data('title-editable', trim(element.text()));
        window.getSelection().removeAllRanges();
        element.emit('title-edit-end', element.data('title-editable'));
    }, true);

    // Quick Ajax Calls [data-ajax-action]
    body.delegate('click', '[data-ajax-action]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        var href   = element.attribute('href') || element.data('ajax-action'),
            method = element.data('ajax-action-method') || 'post';

        if (!href) { return false; }

        element.showIndicator();
        request(method, href + getAjaxSuffix(), function(error, response) {
            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });

                element.hideIndicator();
                return false;
            } else {
                toastr.success(response.body.html || 'Action successfully completed.', response.body.title || '');
            }

            element.hideIndicator();
        })
    });

});


module.exports = {
    /*mout    : require('mout'),
     prime   : require('prime'),
     "$"     : elements,
     zen     : zen,
     domready: domready,
     agent   : require('agent'),*/
    lm: lm,
    mm: mm,
    ui: require('./ui'),
    styles: require('./styles'),
    "$": $,
    domready: require('elements/domready'),
    particles: require('./particles'),
    zen: require('elements/zen'),
    moofx: require('moofx')
};
