"use strict";
var $             = require('elements'),
    zen           = require('elements/zen'),
    ready         = require('elements/domready'),
    ui            = require('../ui'),
    interpolate   = require('mout/string/interpolate'),
    modal         = ui.modal,
    trim          = require('mout/string/trim'),

    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxURL    = require('../utils/get-ajax-url').global,
    getAjaxSuffix = require('../utils/get-ajax-suffix');

ready(function() {
    // Changelog links
    $('body').delegate('click', '[data-changelog]', function(event, element) {
        event.preventDefault();

        modal.open({
            content: 'Loading',
            method: 'post',
            className: 'g5-dialog-theme-default g5-modal-changelog',
            data: { version: element.data('changelog') },
            remote: parseAjaxURI(getAjaxURL('changelog') + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) { return; }

                var wrapper  = content.elements.content,
                    sections = wrapper.search('#g-changelog > ol > li > a');

                sections.forEach(function(section, i) {
                    section = $(section);
                    var href      = section.href(),
                        re        = new RegExp('#(common|' + GANTRY_PLATFORM + ')$', "gi"),
                        collapsed = !href.match(re),
                        status    = 'chevron-' + (!collapsed ? 'up' : 'down');

                    if (!trim(section.text())) {
                        // no platforms
                        return;
                    }

                    // if it's not common but the current platform, move it after common
                    if (i && !collapsed) {
                        section.parent('li').after(section.parent('ol').find('> li'));
                    }

                    zen('i[class="fa g-changelog-toggle fa-fw fa-' + status + '"][aria-hidden="true"]').bottom(section);

                    if (collapsed) {
                        section.nextSibling().style({
                            overflow: 'hidden',
                            height: 0
                        });
                    }

                    section.on('click', function(e) {
                        e.preventDefault();
                        var icon      = section.find('i[class*="fa-chevron-"]'),
                            collapsed = icon.hasClass('fa-chevron-down');

                        if (collapsed) {
                            icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                            section.nextSibling().slideDown();
                        } else {
                            icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                            section.nextSibling().slideUp();
                        }
                    });
                });
            }
        });
    });
});
