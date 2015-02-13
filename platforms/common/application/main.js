var $             = require('elements'),
    ready         = require('elements/domready'),
    request       = require('agent'),
    ui            = require('./ui'),
    interpolate   = require('mout/string/interpolate'),
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
require('./ui/popover');
require('./utils/ajaxify-links');

ready(function() {
    var body = $('body'),
        sentence = 'The {{type}} {{verb}} been successfully saved! {{extras}}';
    // Save
    body.delegate('click', '.button-save', function(e, element) {
        e.preventDefault();
        element.showSpinner();

        var data = {},
            type = element.data('save'),
            extras = '',
            page = $('[data-lm-root]') ? 'layout' : ($('[data-mm-id]') ? 'menu' : 'other');

        switch (page) {
            case 'layout':
                lm.savestate.setSession(lm.builder.serialize(null, true));
                data.layout = JSON.stringify(lm.builder.serialize());

                break;
            case 'menu':
                data.menutype = $('select.menu-select-wrap').value();
                data.settings = JSON.stringify(mm.settings);
                data.ordering = JSON.stringify(mm.ordering);
                data.items = JSON.stringify(mm.items);

                break;

            case 'other':
            default:
                var form = element.parent('form');

                if (form && element.attribute('type') == 'submit') {
                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value(),
                            parent = input.parent('.settings-param'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        if (!name || input.disabled() || (override && !override.checked())) { return; }
                        data[name] = value;
                    });
                }

                if ($('#styles')) { extras = '<br />The CSS was successfully compiled!'; }
        }

        request('post', window.location.href + getAjaxSuffix(), data, function(error, response) {
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

            element.hideSpinner();

            if (page == 'layout') { lm.layoutmanager.updatePendingChanges(); }
        });
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
