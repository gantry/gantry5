"use strict";
var ready         = require('elements/domready'),
    //json          = require('./json_test'), // debug
    $             = require('elements/attributes'),
    modal         = require('../ui').modal,
    toastr        = require('../ui').toastr,
    request       = require('agent'),
    zen           = require('elements/zen'),
    contains      = require('mout/array/contains'),
    size          = require('mout/collection/size'),
    trim          = require('mout/string/trim'),
    forEach       = require('mout/collection/forEach'),

    getAjaxSuffix = require('../utils/get-ajax-suffix'),

    Builder       = require('./builder'),
    History       = require('../utils/history'),
    LMHistory     = require('./history'),
    LayoutManager = require('./layoutmanager'),
    SaveState     = require('../utils/save-state');

require('../ui/popover');

var builder, layoutmanager, lmhistory, savestate;


builder = new Builder();
lmhistory = new LMHistory();
savestate = new SaveState();

ready(function() {
    var body = $('body');

    body.delegate('click', '[data-lm-back]', function(e, element) {
        if (e) { e.preventDefault(); }
        if ($(element).hasClass('disabled')) return false;
        lmhistory.undo();
    });

    body.delegate('click', '[data-lm-forward]', function(e, element) {
        if (e) { e.preventDefault(); }
        if ($(element).hasClass('disabled')) return false;
        lmhistory.redo();
    });

    /* lmhistory events */
    lmhistory.on('push', function(session, index, reset) {
        var HM = {
            back: $('[data-lm-back]'),
            forward: $('[data-lm-forward]')
        };

        if (index && HM.back.hasClass('disabled')) HM.back.removeClass('disabled');
        if (reset && !HM.forward.hasClass('disabled')) HM.forward.addClass('disabled');
        layoutmanager.updatePendingChanges();
    });

    lmhistory.on('undo', function(session, index) {
        var notice = $('#lm-no-layout'),
            HM     = {
                back: $('[data-lm-back]'),
                forward: $('[data-lm-forward]')
            };

        if (notice) { notice.style({ display: !size(session.data) ? 'block' : 'none' }); }

        builder.reset(session.data);
        HM.forward.removeClass('disabled');
        if (!index) HM.back.addClass('disabled');
        layoutmanager.singles('disable');
        layoutmanager.updatePendingChanges();
    });
    lmhistory.on('redo', function(session, index) {
        var notice = $('#lm-no-layout'),
            HM     = {
                back: $('[data-lm-back]'),
                forward: $('[data-lm-forward]')
            };

        if (notice) { notice.style({ display: !size(session.data) ? 'block' : 'none' }); }

        builder.reset(session.data);
        HM.back.removeClass('disabled');
        if (index == this.session.length - 1) HM.forward.addClass('disabled');
        layoutmanager.singles('disable');
        layoutmanager.updatePendingChanges();
    });

});

ready(function() {
    var body = $('body'), root = $('[data-lm-root]'), data;

    // Layout Manager
    layoutmanager = new LayoutManager('body', {
        delegate: '[data-lm-root] .g-grid > .g-block > [data-lm-blocktype]:not([data-lm-nodrag]) !> .g-block, .g5-lm-particles-picker [data-lm-blocktype], [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag]), [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag])',
        droppables: '[data-lm-dropzone]',
        exclude: '.section-header .button, .lm-newblocks .float-right .button, [data-lm-nodrag]',
        resize_handles: '[data-lm-root] .g-grid > .g-block:not(:last-child)',
        builder: builder,
        history: lmhistory,
        savestate: savestate
    });

    module.exports.layoutmanager = layoutmanager;

    // load builder data
    if (root) {
        data = JSON.parse(root.data('lm-root'));
        if (data.name) { data = data.layout; }
        builder.setStructure(data);
        builder.load();

        layoutmanager.history.setSession(builder.serialize());
        layoutmanager.savestate.setSession(builder.serialize(null, true));
    }

    // attach events
    // Modal Tabs
    body.delegate('mousedown', '.g-tabs a', function(event, element) {
        element = $(element);
        event.preventDefault();

        var index  = 0,
            parent = element.parent('.g-tabs'),
            panes  = parent.siblings('.g-panes'),
            links  = parent.search('a');

        links.forEach(function(link, i) {
            if (link == element[0]) { index = i + 1; }
        });

        panes.find('.active').removeClass('active');
        parent.find('.active').removeClass('active');
        panes.find('.g-pane:nth-child(' + index + ')').addClass('active');
        parent.find('li:nth-child(' + index + ')').addClass('active');
    });

    // Picker
    body.delegate('statechangeBefore', '[data-g5-lm-picker]', function() {
        modal.close();
    });

    // Sub-navigation links
    body.delegate('statechangeAfter', '#navbar [data-g5-ajaxify]', function(event, element) {
        root = $('[data-lm-root]');
        if (!root) { return true; }
        data = JSON.parse(root.data('lm-root'));
        builder.setStructure(data);
        builder.load();

        layoutmanager.history.setSession(builder.serialize());
        layoutmanager.savestate.setSession(builder.serialize(null, true));

        // refresh LM eraser
        layoutmanager.eraser.element = $('[data-lm-eraseblock]');
        layoutmanager.eraser.hide();
    });

    // Particles filtering
    body.delegate('input', '.sidebar-block .search input', function(event, element) {
        var value = $(element).value().toLowerCase(),
            list  = $('.sidebar-block [data-lm-blocktype]'),
            text, type;
        if (!list) { return false; }

        list.style({ display: 'none' }).forEach(function(blocktype) {
            blocktype = $(blocktype);
            type = blocktype.data('lm-blocktype').toLowerCase();
            text = trim(blocktype.text()).toLowerCase();
            if (type.substr(0, value.length) == value || text.match(value)) {
                blocktype.style({ display: 'block' });
            }
        }, this);
    });

    // TODO: this was the + handler for new layouts which is now gone in favor of Configurations
    body.delegate('click', '[data-g5-lm-add]', function(event, element) {
        event.preventDefault();
        modal.open({
            content: 'Loading',
            remote: $(element).attribute('href') + getAjaxSuffix()
        });
    });

    // Grid same widths button (evenize, equalize)
    body.delegate('click', '[data-lm-samewidth]', function(event, element) {
        if (element.LMTooltip) { element.LMTooltip.remove(); }
        var clientRect = element[0].getBoundingClientRect();
        if (event.clientX < clientRect.width + clientRect.left) { return; }

        var blocks = element.search('> [data-lm-blocktype="block"]'), id;
        if (!blocks || blocks.length == 1) { return; }

        blocks.forEach(function(block) {
            id = $(block).data('lm-id');
            builder.get(id).setSize(100 / blocks.length, true);
        });

        lmhistory.push(builder.serialize());
    });

    body.delegate('mouseover', '[data-lm-samewidth]', function(event, element) {
        var clientRect = element[0].getBoundingClientRect(),
            clientX = event.clientX || (event.touches && event.touches[0].clientX) || 0,
            tooltips = {
                equalize: clientX + 5 > clientRect.width + clientRect.left,
                move: clientX - 5 < clientRect.left
            };

        if (!tooltips.equalize && !tooltips.move) { return; }

        var msg = tooltips.equalize ? 'Equalize the width the blocks in this grid' : 'Sort or Move the Grid',
            tooltip = zen('span.g-tooltip.g-tooltip-force[data-title="' + msg + '"]').top(element);

        if (tooltips.equalize) { tooltip.addClass('g-tooltip-right'); }
        tooltip.style({position: 'absolute', top: 26 }).style(tooltips.equalize ? 'right' : 'left', -30);

        element.LMTooltip = tooltip;
    });

    body.delegate('mouseout', '[data-lm-samewidth]', function(event, element) {
        if (element.LMTooltip) { element.LMTooltip.remove(); }
    });

    // Clear Layout
    body.delegate('click', '[data-lm-clear]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        var type, child;
        forEach(builder.map, function(obj, id) {
            type = obj.getType();
            child = obj.block.find('> [data-lm-id]');
            if (child) { child = child.data('lm-blocktype'); }
            if (contains(['particle', 'grid', 'block'], type) && (type == 'block' && (child && (child !== 'section' && child !== 'container')))) {
                builder.remove(id);
                obj.block.remove();
            }
        }, this);

        layoutmanager.singles('cleanup', builder);

        lmhistory.push(builder.serialize());
    });

    // Switcher
    body.delegate('mouseover', '[data-lm-switcher]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        if (!element.PopoverDefined) {
            var popover = element.getPopover({
                type: 'async',
                url: element.data('lm-switcher') + getAjaxSuffix(),
                allowElementsClick: '.g-tabs a'
            });
        }
    });

    // Clear Layout
    body.delegate('mousedown', '[data-switch]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        // it's already loading something.
        if (element.parent('.g5-popover-content').find('[data-switch] i')) {
            return false;
        }

        element.showIndicator();

        request('get', element.data('switch') + getAjaxSuffix(), function(error, response) {
            element.hideIndicator();

            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });
                return;
            }

            var structure = response.body.data,
                notice    = $('#lm-no-layout');

            root.data('lm-root', JSON.stringify(structure)).empty();
            if (notice) { notice.style({ display: 'none' }); }
            builder.setStructure(structure);
            builder.load();

            lmhistory.push(builder.serialize());

            $('[data-lm-switcher]').getPopover().hide();
        });
    });

    // Particles settings
    body.delegate('click', '[data-lm-settings]', function(event, element) {
        element = $(element);

        var blocktype   = element.data('lm-blocktype'),
            settingsURL = element.data('lm-settings'),
            data        = null, parent;

        // grid is a special case, since relies on pseudo elements for sorting and same width (evenize)
        // we need to check where the user clicked.
        if (blocktype === 'grid') {
            var clientX   = event.clientX || (event.touches && event.touches[0].clientX) || 0,
                boundings = element[0].getBoundingClientRect();

            if (clientX + 4 - boundings.left < boundings.width) {
                return false;
            }
        }

        element = element.parent('[data-lm-blocktype]');
        parent = element.parent('[data-lm-blocktype]');
        blocktype = element.data('lm-blocktype');

        var ID       = element.data('lm-id'),
            parentID = parent ? parent.data('lm-id') : false;

        if (!contains(['block', 'grid'], blocktype)) {
            data = {};
            data.type = builder.get(element.data('lm-id')).getType() || element.data('lm-blocktype') || false;
            data.subtype = builder.get(element.data('lm-id')).getSubType() || element.data('lm-blocksubtype') || false;
            data.title = (element.find('h4') || element.find('.title')).text() || data.type || 'Untitled';
            data.options = builder.get(element.data('lm-id')).getAttributes() || {};
            data.block = parent ? builder.get(parent.data('lm-id')).getAttributes() || {} : {};

            if (!data.type) { delete data.type; }
            if (!data.subtype) { delete data.subtype; }
        }

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: settingsURL + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var form       = content.elements.content.find('form'),
                    submit     = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) { return true; }

                // Particle Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showIndicator();

                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name     = input.attribute('name'),
                            value    = input.value(),
                            parent   = input.parent('.settings-param'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        if (!name || input.disabled() || (override && !override.checked())) { return; }
                        dataString.push(name + '=' + encodeURIComponent(value));
                    });

                    var title = content.elements.content.find('[data-title-editable]');
                    if (title) {
                        dataString.push('title=' + encodeURIComponent(title.data('title-editable')));
                    }

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&') || {}, function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            var particle = builder.get(ID),
                                block    = null;

                            // particle attributes
                            particle.setAttributes(response.body.data.options);

                            if (particle.hasAttribute('enabled')) { particle[particle.getAttribute('enabled') ? 'enable' : 'disable'](); }

                            if (particle.getType() != 'section') {
                                particle.setTitle(response.body.data.title || 'Untitled');
                                particle.updateTitle(particle.getTitle());
                            }

                            if (particle.getType() == 'position') {
                                particle.updateKey();
                            }

                            // parent block attributes
                            if (response.body.data.block && size(response.body.data.block)) {
                                block = builder.get(parentID);

                                var sibling     = block.block.nextSibling() || block.block.previousSibling(),
                                    currentSize = block.getSize(),
                                    diffSize;

                                block.setAttributes(response.body.data.block);

                                diffSize = currentSize - block.getSize();

                                block.setAnimatedSize(block.getSize());

                                if (sibling) {
                                    sibling = builder.get(sibling.data('lm-id'));
                                    sibling.setAnimatedSize(parseFloat(sibling.getSize()) + diffSize, true);
                                }
                            }

                            lmhistory.push(builder.serialize());
                            modal.close();

                            toastr.success('The particle "' + particle.getTitle() + '" settings have been applied to the Layout. <br />Remember to click the Save button to store them.', 'Settings Applied');
                        }

                        submit.hideIndicator();
                    });
                });
            }
        });

    });

});

module.exports = {
    $: $,
    builder: builder,
    layoutmanager: layoutmanager,
    history: lmhistory,
    savestate: savestate
};