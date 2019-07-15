"use strict";

var ready          = require('elements/domready'),
    $              = require('elements'),
    decouple       = require('../utils/decouple'),
    scrollbarWidth = require('../utils/get-scrollbar-width');

var container, sidebar, search, particles, height, heightTop, heightBottom, excludeTop, excludeBottom,
    initialSidebarCoords, realSidebarTop;

var initSizes = function() {
    // fixed and contained particles sidebar
    container = $('.sidebar-block');
    if (!container) { return; }

    sidebar = container.find('.g5-lm-particles-picker');
    if (!sidebar) { return; }

    search = sidebar.find('> .search');
    particles = sidebar.find('> .particles-container');
    height = window.innerHeight;
    heightTop = 0;
    heightBottom = 0;
    initialSidebarCoords = sidebar[0].getBoundingClientRect();
    realSidebarTop = sidebar.position().top;
    excludeTop = $('body.admin.com_gantry5 nav.navbar-fixed-top, #wpadminbar, #admin-main #titlebar, #admin-main .grav-update.grav');
    excludeBottom = $('body.admin.com_gantry5 #status');

    if (excludeTop) {
        $(excludeTop).forEach(function(element) {
            heightTop += element.offsetHeight;
        });
    }

    if (excludeBottom) {
        $(excludeBottom).forEach(function(element) {
            heightBottom += element.offsetHeight;
        });
    }

    particles.style({
        'max-height': (height - heightTop - heightBottom - search[0].offsetHeight - 30),
        overflow: 'auto'
    });

    if (particles[0].scrollHeight !== particles[0].offsetHeight) {
        particles.addClass('has-scrollbar').style({ 'margin-right': -scrollbarWidth() });
    }
};

ready(function() {
    initSizes();

    var scrollElement = $(GANTRY_PLATFORM === 'grav' ? Grav.default.Scrollbar.Instance.instance.getViewElement() || '#admin-main .content-padding' : window) || [window],
        scroll        = function() {
            if (!container || !sidebar) { return; }

            var scrollTop       = this.scrollY || this.scrollTop,
                containerBounds = container[0].getBoundingClientRect(),
                limit           = containerBounds.top + containerBounds.height,
                sidebarCoords   = sidebar[0].getBoundingClientRect(),
                shouldBeFixed   = (scrollTop > (initialSidebarCoords.top - heightTop - 10)) && scrollTop >= realSidebarTop - 10,
                reachedTheLimit = sidebarCoords.height + 10 + heightTop + parseInt(container.compute('padding-bottom'), 10) >= limit,
                sidebarTallerThanContainer = containerBounds.height <= sidebarCoords.height;

            sidebar.style('width', sidebarCoords.width);
            if (shouldBeFixed && !reachedTheLimit) {
                sidebar.removeClass('particles-absolute').addClass('particles-fixed');
                sidebar.style({
                    top: heightTop + 10,
                    bottom: 'inherit'
                });
            } else if (shouldBeFixed && reachedTheLimit) {
                if (sidebarTallerThanContainer || (GANTRY_PLATFORM === 'grav' && containerBounds.bottom < sidebarCoords.bottom)) {
                    sidebar.removeClass('particles-fixed').addClass('particles-absolute');
                    sidebar.style({
                        top: 'inherit',
                        bottom: parseInt(container.compute('padding-bottom'), 10)
                    });
                }
            } else {
                sidebar.removeClass('particles-fixed').removeClass('particles-absolute');
                sidebar.style({
                    top: 'inherit',
                    bottom: 'inherit'
                });
            }
        };

    decouple(scrollElement[0], 'scroll', scroll.bind(scrollElement[0]));

    decouple(window, 'resize', function() {
        if (!particles) { return; }

        // initSizes();
        scroll.call(scrollElement[0]);

        particles.style({
            'max-height': (window.innerHeight - heightTop - heightBottom - search[0].offsetHeight - 30)
        });
    });

    $('body').on('statechangeEnd', function() {
        initSizes();
    });
});

module.exports = initSizes;
