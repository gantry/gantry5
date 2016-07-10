"use strict";
var $             = require('../../utils/elements.utils'),
    domready      = require('elements/domready'),
    modal         = require('../../ui').modal,
    getAjaxSuffix = require('../../utils/get-ajax-suffix'),
    parseAjaxURI  = require('../../utils/get-ajax-url').parse,
    getAjaxURL    = require('../../utils/get-ajax-url').global,
    translate     = require('../../utils/translate'),

    trim          = require('mout/string/trim'),
    contains      = require('mout/array/contains');

domready(function() {
    var body = $('body');
    body.delegate('keyup', '.g-icons input[type="text"]', function(event, element){
        element = $(element);
        var preview = element.sibling('[data-g5-iconpicker]') || element.siblings().find('[data-g5-iconpicker]'),
            value = element.value(),
            size;

        preview.find('i').attribute('class', value || 'fa fa-hand-o-up picker');

        size = preview[0].offsetWidth;

        if (!size) { preview.find('i').attribute('class', 'fa fa-hand-o-up picker'); }
    });

    body.delegate('click', '[data-g5-iconpicker]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        element = $(element);
        var field = $(element.data('g5-iconpicker')),
            realPreview = element,
            value = trim(field.value()).replace(/\s{2,}/g, ' ').split(' ');

        modal.open({
            content: translate('GANTRY5_PLATFORM_JS_LOADINg'),
            className: 'g5-dialog-theme-default g5-modal-icons',
            remote: parseAjaxURI(getAjaxURL('icons') + getAjaxSuffix()),
            afterClose: function() {
                var popovers = $('.g5-popover');
                if (popovers) { popovers.remove(); }
            },
            remoteLoaded: function(response, content) {
                var html, large,
                    container = content.elements.content,
                    icons = container.search('[data-g-icon]');

                if (!icons || !response.body.success) {
                    container.html(response.body.html || response.body);
                    return false;
                }

                var updatePreview = function() {
                    var data = [],
                        active = container.find('[data-g-icon].active'),
                        options = container.search('.g-particles-header .float-right input:checked, .g-particles-header .float-right select');

                    if (active) { data.push(active.data('g-icon')); }
                    if (options) {
                        options.forEach(function(option) {
                            var v = $(option).value();
                            if (v && v !== 'fa-') { data.push(v); }
                        });
                    }

                    container.find('.g-icon-preview').html('<i class="fa ' + data.join(' ') + '"></i> <span>' + data[0] + '</span>');
                    container.find('[data-g-select]').disabled(container.find('[data-g-icon].active') ? null : true);
                };

                var updateTotal = function() {
                    var total = container.search('[data-g-icon]:not(.hide-icon)');
                    container.find('.particle-search-total').text(total ? total.length : 0);
                };

                container.find('[data-g-select]').disabled(container.find('[data-g-icon].active') ? null : true);

                container.delegate('click', '[data-g-icon]', function(event, element) {
                    if (event && event.preventDefault) { event.preventDefault(); }
                    element = $(element);

                    var active = container.find('[data-g-icon].active');
                    if (active) { active.removeClass('active'); }

                    element.addClass('active');
                    container.find('[data-g-select]').disabled(null);

                    updatePreview();
                });

                container.delegate('click', '[data-g-select]', function(event){
                    event.preventDefault();

                    if (!container.find('[data-g-icon].active')) { return false; }

                    var output = container.find('.g-icon-preview i');
                    field.value(output.attribute('class'));
                    realPreview.find('i').attribute('class', output.attribute('class'));

                    field.emit('input');

                    $('body').emit('input', {target: field});

                    modal.close();
                });

                container.delegate('change', '.g-particles-header .float-right input[type="checkbox"], .g-particles-header .float-right select', function(/*e, input*/) {
                    updatePreview();
                });

                container.delegate('keyup', '.particle-search-wrapper input[type="text"]', function(e, input) {
                    input = $(input);
                    var value = input.value(),
                        hidden = container.search('[data-g-icon].hide-icon');

                    if (!value) {
                        if (hidden) {
                            hidden.removeClass('hide-icon');
                            updateTotal();
                        }
                        return true;
                    }

                    var found = container.search('[data-g-icon*="' + value + '"]');
                    container.search('[data-g-icon]').addClass('hide-icon');
                    if (found) {
                        found.removeClass('hide-icon');
                    }

                    updateTotal();

                });

                icons.forEach(function(icon) {
                    icon = $(icon);
                    html = '';

                    for (var i = 5, l = 0; i > l; i--) {
                        large = (!i) ? 'lg' : i + 'x';
                        html += '<i class="fa ' + icon.data('g-icon') + ' fa-' + large + '"></i> ';
                    }

                    html += '<h3>' + icon.data('g-icon') + '</h3>';

                    icon.popover({
                        content: html,
                        placement: 'auto',
                        trigger: 'mouse',
                        style: 'above-modal, icons-preview',
                        width: 'auto',
                        targetEvents: false,
                        delay: 1
                    }).on('hidden.popover', function(instance) {
                        if (instance.$target) { instance.$target.remove(); }
                    });

                    if (contains(value, icon.data('g-icon'))) {
                        // set active icon
                        icon.addClass('active');

                        // toggle options
                        value.forEach(function(name) {
                            var field = container.find('[name="' + name + '"]');
                            if (field) { field.checked(true); }
                            else {
                                field = container.find('option[value="' + name + '"]');
                                if (field) { field.parent().value(name); }
                            }
                        });

                        // scroll into place of active icon
                        var wrap = icon.parent('.icons-wrapper'),
                            wrapHeight = wrap[0].offsetHeight;
                        wrap[0].scrollTop = icon[0].offsetTop - (wrapHeight / 2);

                        // update preview
                        updatePreview();
                    }
                });

                setTimeout(function() {
                    container.find('.particle-search-wrapper input')[0].focus();
                }, 5);
            }
        });
    });
});

module.exports = {};
