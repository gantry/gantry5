"use strict";

var r   = require('domready'),
    $   = require('elements'),
    zen = require('elements/zen');

var MBP = 768;

var resetSelectedActive = function() {
    var body    = $('body'),
        mainNav = $('.g-main-nav'),
        selected, actives, levels;

    body.removeClass('g-nav-overlay-active');
    selected = mainNav.search('.g-selected');
    actives = mainNav.search('.g-active');
    levels = mainNav.search('.g-toplevel, .g-sublevel');

    if (selected) { selected.removeClass('g-selected'); }
    if (actives) { actives.removeClass('g-active').addClass('g-inactive'); }
    if (levels) { levels.removeClass('g-slide-out'); }
};

var adjustOnViewportChange = function(e) {
    var body         = $('body'),
        topLevel     = $('.g-toplevel'),
        pageSurround = $('#g-page-surround'),
        mainNav      = pageSurround.search('.g-main-nav'),
        mobileNav    = pageSurround.nextSibling('.g-mobile-nav');

    if (window.innerWidth < MBP) {
        resetSelectedActive();
        if (mobileNav) { mobileNav.appendChild(topLevel); }
    } else {
        resetSelectedActive();
        if (mainNav) { mainNav.appendChild(topLevel); }
        if (body.hasClass('g-mobile-nav-active')) {
            body.removeClass('g-mobile-nav-active');
        }
    }
};

$(window).on('load', adjustOnViewportChange);
$(window).on('resize', adjustOnViewportChange);

r(function() {
    var body            = $('body'),
        topLevel        = $('.g-toplevel'),
        pageSurround    = $('#g-page-surround'),
        navOverlay      = zen('div.g-nav-overlay'),
        mobileNav       = zen('nav.g-main-nav.g-mobile-nav'),
        mobileNavToggle = zen('div.g-mobile-nav-toggle');

    body.delegate('click', '.g-menu-item [data-g-menuparent]', function(e, el) {
        el = $(el);

        var dropdown = el.nextSibling('.g-dropdown'),
            parent   = el.parent('.g-menu-item');

        if (!dropdown) { return; }

        if (dropdown.hasClass('g-inactive')) {
            // add g-selected class to clicked item
            el.addClass('g-selected');

            // remove g-inactive and add g-active to the dropdown
            dropdown.removeClass('g-inactive').addClass('g-active');

            // add g-slide-out class to the parent UL
            el.parent('ul').addClass('g-slide-out');

            // switch to inactive all siblings menu-items and remove g-selected on previous ones
            var lists = parent.search('~~ .g-menu-item ul');
            if (lists) { lists.removeClass('g-active').addClass('g-inactive'); }

            // children
            var children = el.children('.g-menu-item-content');
            if (children) { children.removeClass('g-selected'); }

            if (window.innerWidth > MBP) { body.addClass('g-nav-overlay-active'); }
        } else {
            resetSelectedActive();
        }
    });

    // Go Back Link for Level 1 || dont think we use this :)
    body.delegate('click', '.g-menu-item .g-level-1', function(e, el) {
        el = $(el);

        var dropdown = el.parent('.g-dropdown'),
            toplevel = el.parent('.g-toplevel');

        if (dropdown || toplevel) {
            if (dropdown) { dropdown.removeClass('g-active').addClass('g-inactive'); }
            if (toplevel) { toplevel.removeClass('g-slide-out'); }
        }
     });

    // Go Back Link for Level 2+
    body.delegate('click', '.g-menu-item .g-go-back', function(e, el) {
        el = $(el);

        var dropdown = el.parent('.g-dropdown'),
            parent   = el.parent('.g-menu-item');

        if (dropdown) {
            var parentSublevel = dropdown.parent('.g-sublevel');
            dropdown.removeClass('g-active').addClass('g-inactive');
            if (parentSublevel) { parentSublevel.removeClass('g-slide-out'); }
        }
        if (parent) { parent.search('> .g-menu-item-content').removeClass('g-selected'); }
    });

    // Close menu on overlay click
    body.delegate('click', '.g-nav-overlay', function() {
        if (window.innerWidth < MBP) {
            body.toggleClass('g-mobile-nav-active');
        } else {
            body.toggleClass('g-nav-overlay-active');
            resetSelectedActive();
        }
    });

    // Mobile Nav Toggle
    body.delegate('click', '.g-mobile-nav-toggle', function() {
        body.toggleClass('g-mobile-nav-active');
    });

    // Toggle Class on Mobile
    body.appendChild(mobileNav);
    navOverlay.bottom(pageSurround);
    mobileNavToggle.bottom(pageSurround);
});

module.exports = {};

/*jQuery(document).ready(function($) {
    // Variables
    var MBP = 768; // Temporarily Hardcoded Mobile BreakPoint
    var Window = $(window);
    var Body = $('body');
    var MainNav = $('.g-main-nav');
    var TopLevel = $('.g-toplevel');
    var PageSurround = $('#g-page-surround');
    var MobileNavOverlay = $('<div/>', { 'class': 'g-nav-overlay' });
    var MobileNav = $('<nav/>', { 'class': 'g-main-nav' }).addClass('g-mobile-nav');
    var MobileNavToggle = $('<div/>', { 'class': 'g-mobile-nav-toggle' });

    // Open Dropdown & Overlay
    $('.g-menu-item').children('[data-g-menuparent]').on('click', function() {
        var selected = $(this);
        if (selected.next('.g-dropdown').hasClass('g-inactive')) {

            if (selected.parents('.g-standard')) {
                selected.parents('.g-dropdown-column').find('.g-active').removeClass('g-active').addClass('g-inactive');
                selected.parents('.g-dropdown-column').find('.g-selected').removeClass('g-selected');
            }


            selected.addClass('g-selected').next('.g-dropdown').removeClass('g-inactive').addClass('g-active').end()
                .parent('li').parent('ul').addClass('g-slide-out').end()
                .closest('.g-block').siblings('.g-block').addClass('g-block-inactive');
            selected.parent('.g-menu-item').siblings('.g-menu-item').children('ul').removeClass('g-active').addClass('g-inactive').end()
                .children('.g-menu-item-content').removeClass('g-selected');
            if (Window.width() > MBP) {
                Body.addClass('g-nav-overlay-active');
            }
        } else {
            resetSelectedActive();
        }
    });

    // Go Back Link for Level 1
    $('.g-level-1').on('click', function() {
        $(this).closest('.g-dropdown').removeClass('g-active').addClass('g-inactive').closest('.g-toplevel').removeClass('g-slide-out');
    });
    // Go Back Link for Level 2+
    $('.g-go-back').on('click', function() {
        $(this).closest('.g-dropdown').removeClass('g-active').addClass('g-inactive').closest('.g-sublevel').removeClass('g-slide-out').end()
            .closest('.g-menu-item').children('.g-menu-item-content').removeClass('g-selected').end()
            .closest('.g-block').siblings('.g-block').removeClass('g-block-inactive');
    });

    // Toggle Class on Mobile
    Body.append(MobileNav);
    MobileNavOverlay.appendTo(PageSurround);
    MobileNavToggle.appendTo(PageSurround);

    Window.bind("load resize", function(e) {
        if (Window.width() < MBP) {
            resetSelectedActive();
            PageSurround.next('.g-mobile-nav').append(TopLevel);
        } else {
            resetSelectedActive();
            PageSurround.find('.g-main-nav').append(TopLevel);
            if (Body.hasClass('g-mobile-nav-active')) {
                Body.removeClass('g-mobile-nav-active');
            }
        }
    });

    // Mobile Nav Toggle
    $('.g-mobile-nav-toggle').on('click', function() {
        Body.toggleClass('g-mobile-nav-active');
    });

    // Page Surround Overlay
    $('.g-nav-overlay').on('click', function() {
        if (Window.width() < MBP) {
            Body.toggleClass('g-mobile-nav-active');
        } else {
            Body.toggleClass('g-nav-overlay-active');
            resetSelectedActive();
        }
    });

    // Reset Menu Item Selected and SubLevel Active State
    function resetSelectedActive() {
        Body.removeClass('g-nav-overlay-active');
        MainNav.find('.g-selected').removeClass('g-selected').end()
            .find('.g-active').removeClass('g-active').addClass('g-inactive').end()
            .find('.g-toplevel').removeClass('g-slide-out').end()
            .find('.g-sublevel').removeClass('g-slide-out');
    }

});*/
