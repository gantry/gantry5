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
    excludeTop = $('body.admin.com_gantry5 nav.navbar-fixed-top, #wpadminbar');
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

    if (particles[0].scrollHeight != particles[0].offsetHeight) {
        particles.addClass('has-scrollbar').style({ 'margin-right': -scrollbarWidth() });
    }
};

ready(function() {
    initSizes();

    decouple(window, 'scroll', function() {
        if (!container || !sidebar) { return; }

        var scrollTop       = this.scrollY,
            containerBounds = container[0].getBoundingClientRect(),
            limit           = containerBounds.top + containerBounds.height,
            sidebarCoords   = sidebar[0].getBoundingClientRect(),
            shouldBeFixed   = (scrollTop > (initialSidebarCoords.top - heightTop - 10)) && scrollTop >= realSidebarTop - 10,
            reachedTheLimit = sidebarCoords.height + 10 + heightTop + parseInt(container.compute('padding-bottom'), 10) >= limit;

        sidebar.style('width', sidebarCoords.width);
        if (shouldBeFixed && !reachedTheLimit) {
            sidebar.removeClass('particles-absolute').addClass('particles-fixed');
            sidebar.style({
                top: heightTop + 10,
                bottom: 'inherit'
            });
        } else if (shouldBeFixed && reachedTheLimit) {
            sidebar.removeClass('particles-fixed').addClass('particles-absolute');
            sidebar.style({
                top: 'inherit',
                bottom: parseInt(container.compute('padding-bottom'), 10)
            });
        } else {
            sidebar.removeClass('particles-fixed').removeClass('particles-absolute');
            sidebar.style({
                top: 'inherit',
                bottom: 'inherit'
            });
        }
    });

    decouple(window, 'resize', function() {
        if (!particles) { return; }

        sidebar.style('width', null);
        initSizes();

        particles.style({
            'max-height': (window.innerHeight - heightTop - heightBottom - search[0].offsetHeight - 30)
        });
    });

    $('body').on('statechangeEnd', function() {
        initSizes();
    });
});

module.exports = initSizes;
