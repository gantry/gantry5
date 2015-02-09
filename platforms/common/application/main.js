var $       = require('elements'),
    ready   = require('elements/domready'),
    request = require('agent'),
    ui      = require('./ui'),
    modal   = ui.modal,
    toastr  = ui.toastr,

    getAjaxSuffix = require('./utils/get-ajax-suffix'),

    lm      = require('./lm'),
    mm      = require('./menu').menumanager;

require('elements/attributes');
require('elements/events');
require('elements/delegation');
require('elements/insertion');
require('elements/traversal');
require('./ui/popover');
require('./utils/ajaxify-links');

ready(function() {
    var body = $('body');
    // Save
    body.delegate('click', '.button-save', function(e, element) {
        e.preventDefault();
        element.showSpinner();

        var data = {},
            type = element.data('save'),
            sentence = type + ' ' + (type.slice(-1) == 's' ? 'have' : 'has');

        if ($('[data-lm-root]')) {
            data.layout = JSON.stringify(lm.builder.serialize());
        } else if ($('[data-mm-id]')) {
            data.menutype = $('select.menu-select-wrap').value();
            data.ordering = JSON.stringify(mm.ordering);
            data.items = JSON.stringify(mm.items);
        } else {
            var form = element.parent('form');

            if (form && element.attribute('type') == 'submit') {
                $(form[0].elements).forEach(function(input) {
                    input = $(input);
                    var name = input.attribute('name'), value = input.value();
                    if (!name) { return; }
                    data[name] = value;
                });
            }
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
                toastr.success('The ' + sentence + ' been successfully saved!', type + ' Saved');
            }

            element.hideSpinner();
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
    menu: mm,
    ui: require('./ui'),
    styles: require('./styles'),
    "$": $,
    domready: require('elements/domready'),
    particles: require('./particles'),
    zen: require('elements/zen'),
    moofx: require('moofx')
};
