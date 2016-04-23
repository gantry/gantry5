"use strict";
var ready          = require('elements/domready'),
    $              = require('elements/attributes'),
    modal          = require('../ui').modal,
    toastr         = require('../ui').toastr,
    sidebar        = require('./particles-sidebar'),
    request        = require('agent'),
    zen            = require('elements/zen'),
    contains       = require('mout/array/contains'),
    size           = require('mout/collection/size'),
    trim           = require('mout/string/trim'),
    strReplace     = require('mout/string/replace'),
    properCase     = require('mout/string/properCase'),
    forEach        = require('mout/collection/forEach'),
    precision      = require('mout/number/enforcePrecision'),

    getAjaxSuffix = require('../utils/get-ajax-suffix'),
    parseAjaxURI  = require('../utils/get-ajax-url').parse,
    getAjaxURL    = require('../utils/get-ajax-url').global,

    flags         = require('../utils/flags-state'),
    Builder        = require('./builder'),
    History        = require('../utils/history'),
    validateField  = require('../utils/field-validation'),
    LMHistory      = require('./history'),
    LayoutManager  = require('./layoutmanager'),
    SaveState      = require('../utils/save-state');

require('../ui/popover');

var builder, layoutmanager, lmhistory, savestate, Tips;

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

        if (index && HM.back && HM.back.hasClass('disabled')) HM.back.removeClass('disabled');
        if (reset && HM.forward && !HM.forward.hasClass('disabled')) HM.forward.addClass('disabled');
        layoutmanager.updatePendingChanges();
    });

    lmhistory.on('undo', function(session, index) {
        var notice = $('#lm-no-layout'),
            title = $('.layout-title .title small'),
            preset_name = session.preset.name || 'Default',
            HM = {
                back: $('[data-lm-back]'),
                forward: $('[data-lm-forward]')
            };

        if (notice) { notice.style({ display: !size(session.data) ? 'block' : 'none' }); }
        if (title) { title.text('(' + properCase(trim(strReplace(preset_name, [/_/g, /\//g], [' ', ' / ']))) + ')'); }

        builder.reset(session.data);
        HM.forward.removeClass('disabled');
        if (!index) HM.back.addClass('disabled');
        layoutmanager.singles('disable');
        layoutmanager.updatePendingChanges();
    });
    lmhistory.on('redo', function(session, index) {
        var notice = $('#lm-no-layout'),
            title = $('.layout-title .title small'),
            preset_name = session.preset.name || 'Default',
            HM = {
                back: $('[data-lm-back]'),
                forward: $('[data-lm-forward]')
            };

        if (notice) { notice.style({ display: !size(session.data) ? 'block' : 'none' }); }
        if (title) { title.text('(' + properCase(trim(strReplace(preset_name, [/_/g, /\//g], [' ', ' / ']))) + ')'); }

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
    layoutmanager = new LayoutManager('[data-lm-container]', {
        delegate: '[data-lm-root] .g-grid > .g-block > [data-lm-blocktype]:not([data-lm-nodrag]) !> .g-block, .g5-lm-particles-picker [data-lm-blocktype], [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag]), [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag]), [data-lm-root] [data-lm-blocktype="offcanvas"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag]), [data-lm-root] [data-lm-blocktype="offcanvas"] > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag])',
        droppables: '[data-lm-dropzone]',
        exclude: '.section-header .button, .section-header .fa, .lm-newblocks .float-right .button, [data-lm-nodrag]',
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

        layoutmanager.history.setSession(builder.serialize(), JSON.parse(root.data('lm-preset')));
        layoutmanager.savestate.setSession(builder.serialize(null, true));
    }

    // attach events
    // Modal Tabs
    body.delegate('click', '.g-tabs a', function(event, element) {
        event.preventDefault();
        return false;
    });
    body.delegate('keydown', '.g-tabs a', function(event, element) {
        var key = (event.which ? event.which : event.keyCode);
        if (key == 32 || key == 13) { // ARIA support: Space / Enter toggle
            event.preventDefault();
            body.emit('mouseup', event);
            return false;
        }
    });
    body.delegate('mouseup', '.g-tabs a', function(event, element) {
        element = $(element);
        event.preventDefault();

        var index = 0,
            parent = element.parent('.g-tabs'),
            panes = parent.siblings('.g-panes'),
            links = parent.search('a');

        links.forEach(function(link, i) {
            if (link == element[0]) { index = i + 1; }
        });

        panes.find('.active').removeClass('active');
        parent.find('.active').removeClass('active');
        panes.find('.g-pane:nth-child(' + index + ')').addClass('active');
        parent.find('li:nth-child(' + index + ')').addClass('active');

        // ARIA
        if (panes.search('[aria-expanded]')) { panes.search('[aria-expanded]').attribute('aria-expanded', 'false'); }
        if (parent.search('[aria-expanded]')) { parent.search('[aria-expanded]').attribute('aria-expanded', 'false'); }

        panes.find('.g-pane:nth-child(' + index + ')').attribute('aria-expanded', 'true');
        if (parent.find('li:nth-child(' + index + ') [aria-expanded]')) { parent.find('li:nth-child(' + index + ') [aria-expanded]').attribute('aria-expanded', 'true'); }
    });

    // Picker
    body.delegate('statechangeBefore', '[data-g5-lm-picker]', function() {
        modal.close();
    });

    // Sub-navigation links
    body.on('statechangeAfter', function(event, element) {
        root = $('[data-lm-root]');
        if (!root) { return true; }
        data = JSON.parse(root.data('lm-root'));
        builder.setStructure(data);
        builder.load();

        layoutmanager.refresh();
        layoutmanager.history.setSession(builder.serialize(), JSON.parse(root.data('lm-preset')));
        layoutmanager.savestate.setSession(builder.serialize(null, true));

        // refresh LM eraser
        layoutmanager.eraser.element = $('[data-lm-eraseblock]');
        layoutmanager.eraser.hide(true);
    });

    // Particles filtering
    body.delegate('input', '.sidebar-block .search input', function(event, element) {
        var value = $(element).value().toLowerCase(),
            list = $('.sidebar-block [data-lm-blocktype]'),
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

    // Grid same widths button (evenize, equalize)
    ['click', 'touchend'].forEach(function(evt){
        body.delegate(evt, '[data-lm-samewidth]:not(:empty)', function(event, element) {
            window.G5.tips.hide(element[0]);
            var clientRect = element[0].getBoundingClientRect();
            if ((event.clientX || event.pageX || event.changedTouches[0].pageX || 0) < clientRect.width + clientRect.left) { return; }

            var blocks = element.search('> [data-lm-blocktype="block"]'), id;
            if (!blocks || blocks.length == 1) { return; }

            blocks.forEach(function(block) {
                id = $(block).data('lm-id');
                builder.get(id).setSize(100 / blocks.length, true);
            });

            lmhistory.push(builder.serialize(), lmhistory.get().preset);
        });
    });

    body.delegate('mouseover', '[data-lm-samewidth]:not(:empty)', function(event, element) {
        var clientRect = element[0].getBoundingClientRect(),
            clientX = event.clientX || (event.touches && event.touches[0].clientX) || 0,
            tooltips = {
                equalize: clientX + 5 > clientRect.width + clientRect.left,
                move: clientX - 5 < clientRect.left
            };

        if (!tooltips.equalize && !tooltips.move) { return; }

        var msg = tooltips.equalize ? 'Equalize the width of blocks in this grid' : 'Sort or Move the Grid';

        element.data('tip', msg).data('tip-offset', -30);

        window.G5.tips
            .get(element[0])
            .content(msg)
            .place(tooltips.equalize ? 'top-left' : 'top-right')
            .show();
    });

    body.delegate('mouseout', '[data-lm-samewidth]:not(:empty)', function(event, element) {
        window.G5.tips.hide(element[0]);
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

        lmhistory.push(builder.serialize(), lmhistory.get().preset);
    });

    // Switcher
    var SWITCHER_HIT = false;
    body.delegate('mouseover', '[data-lm-switcher]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        SWITCHER_HIT = element;
        if (!element.PopoverDefined) {
            element.getPopover({
                type: 'async',
                width: '500',
                url: parseAjaxURI(element.data('lm-switcher') + getAjaxSuffix()),
                allowElementsClick: '.g-tabs a'
            });
        }
    });

    // Switch Layout
    body.delegate('keydown', '[data-switch]', function(event, element){
        var key = (event.which ? event.which : event.keyCode);
        if (key == 32 || key == 13) { // ARIA support: Space toggle
            event.preventDefault();
            body.emit('mousedown', event);
        }
    });

    body.delegate('mousedown', '[data-switch]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }

        // it's already loading something.
        if (element.parent('.g5-popover-content').find('[data-switch] i')) {
            return false;
        }

        element.showIndicator();

        var preset = $('[data-lm-preset]'),
            checkbox = element.parent('.g-pane').find('input[type="checkbox"][data-g-preserve]'),
            preserve = checkbox && checkbox.checked(),
            method = !preserve ? 'get' : 'post',
            data = {};

        if (preserve) {
            var lm = layoutmanager;
            lm.singles('cleanup', lm.builder, true);
            lm.savestate.setSession(lm.builder.serialize(null, true));

            data.preset = preset && preset.data('lm-preset') ? preset.data('lm-preset') : 'default';
            data.layout = JSON.stringify(lm.builder.serialize());
        }

        var uri = parseAjaxURI(element.data('switch') + getAjaxSuffix());
        request(method, uri, data, function(error, response) {
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

            if (response.body.message && !flags.get('lm:switcher:' + window.btoa(uri), false)) {
                // confirm before proceeding
                flags.warning({
                    message: response.body.message,
                    callback: function(response, content) {
                        var confirm = content.find('[data-g-delete-confirm]'),
                            cancel  = content.find('[data-g-delete-cancel]');

                        if (!confirm) { return; }

                        confirm.on('click', function(e) {
                            e.preventDefault();
                            if (this.attribute('disabled')) { return false; }

                            flags.get('lm:switcher:' + window.btoa(uri), true);
                            $([confirm, cancel]).attribute('disabled');
                            body.emit('mousedown', { target: element });

                            modal.close();
                        });

                        cancel.on('click', function(e) {
                            e.preventDefault();
                            if (this.attribute('disabled')) { return false; }

                            $([confirm, cancel]).attribute('disabled');
                            flags.get('lm:switcher:' + window.btoa(uri), false);

                            modal.close();
                            if (SWITCHER_HIT) {
                                setTimeout(function(){
                                    SWITCHER_HIT.getPopover().show();
                                }, 5);
                            }
                        });
                    }
                });

                return false;
            }

            var preset = response.body.preset || { name: 'default' },
                preset_name = response.body.title || 'Default',
                structure = response.body.data,
                notice = $('#lm-no-layout'),
                title = $('.layout-title .title small');

            root.data('lm-root', JSON.stringify(structure)).empty();
            root.data('lm-preset', preset);
            if (notice) { notice.style({ display: 'none' }); }
            if (title) { title.text('(' + preset_name + ')'); }
            builder.setStructure(structure);
            builder.load();

            lmhistory.push(builder.serialize(), JSON.parse(preset));

            $('[data-lm-switcher]').getPopover().hideAll().destroy();
        });
    });

    // Particles settings
    body.delegate('click', '[data-lm-settings]', function(event, element) {
        element = $(element);

        var blocktype = element.data('lm-blocktype'),
            settingsURL = element.data('lm-settings'),
            data = null, parent;

        // grid is a special case, since relies on pseudo elements for sorting and same width (evenize)
        // we need to check where the user clicked.
        if (blocktype === 'grid') {
            var clientX = event.clientX || (event.touches && event.touches[0].clientX) || 0,
                boundings = element[0].getBoundingClientRect();

            if (clientX + 4 - boundings.left < boundings.width) {
                return false;
            }
        }

        element = element.parent('[data-lm-blocktype]');
        parent = element.parent('[data-lm-blocktype]');
        blocktype = element.data('lm-blocktype');

        var ID = element.data('lm-id'),
            parentID = parent ? parent.data('lm-id') : false,
            parentType = parent ? parent.data('lm-blocktype') : false;

        if (!contains(['block', 'grid'], blocktype)) {
            data = {};
            data.type = builder.get(element.data('lm-id')).getType() || element.data('lm-blocktype') || false;
            data.subtype = builder.get(element.data('lm-id')).getSubType() || element.data('lm-blocksubtype') || false;
            data.title = (element.find('h4') || element.find('.title')).text() || data.type || 'Untitled';
            data.options = builder.get(element.data('lm-id')).getAttributes() || {};
            data.block = parent && parentType !== 'wrapper' ? builder.get(parent.data('lm-id')).getAttributes() || {} : {};
            data.size_limits = builder.get(element.data('lm-id')).getLimits(!parent ? false : builder.get(parent.data('lm-id')));

            if (!data.type) { delete data.type; }
            if (!data.subtype) { delete data.subtype; }
            if (!size(data.options)) { delete data.options; }
            if (!size(data.block)) { delete data.block; }
        }

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            overlayClickToClose: false,
            remote: parseAjaxURI(settingsURL + getAjaxSuffix()),
            remoteLoaded: function(response, content) {
                if (!response.body.success) {
                    modal.enableCloseByOverlay();
                    return;
                }

                var form = content.elements.content.find('form'),
                    fakeDOM = zen('div').html(response.body.html).find('form'),
                    submit = content.elements.content.search('input[type="submit"], button[type="submit"], [data-apply-and-save]'),
                    dataString = [], invalid = [];

                if ((!form && !fakeDOM) || !submit) { return true; }

                var urlTemplate = content.elements.content.find('.g-urltemplate');
                if (urlTemplate) { body.emit('input', { target: urlTemplate }); }

                var blockSize = content.elements.content.find('[name="block[size]"]');

                // logic for limits
                if (blockSize && data.size_limits) {
                    var note = content.elements.content.find('.blocksize-note'),
                        min = precision(data.size_limits[0], 1),
                        max = precision(data.size_limits[1], 1);

                    blockSize.attribute('min', min);
                    blockSize.attribute('max', max);

                    if (note) {
                        var noteHTML = note.html();
                        noteHTML = noteHTML.replace(/#min#/g, min);
                        noteHTML = noteHTML.replace(/#max#/g, max);

                        note.html(noteHTML);
                        note.find('.blocksize-' + (min == max ? 'range' : 'fixed')).addClass('hidden');
                    }

                    var isValid = function() {
                        return parseFloat(blockSize.value()) >= min && parseFloat(blockSize.value()) <= max ? '' : 'You need to stay in between the min and max range';
                    };

                    blockSize.on('input', function(){
                        blockSize[0].setCustomValidity(isValid());
                    });
                }

                // Particle Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();

                    var target = $(e.target);
                    target.disabled(true);

                    dataString = [];
                    invalid = [];

                    target.hideIndicator();
                    target.showIndicator();

                    $(fakeDOM[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name');
                        if (!name || input.disabled()) { return; }

                        input = content.elements.content.find('[name="' + name + '"]');
                        var value = input.type() == 'checkbox' ? Number(input.checked()) : input.value(),
                            parent = input.parent('.settings-param'),
                            override = parent ? parent.find('> input[type="checkbox"]') : null;

                        override = override || $(input.data('override-target'));

                        if (override && !override.checked()) { return; }
                        if (!validateField(input)) { invalid.push(input); }

                        dataString.push(name + '=' + encodeURIComponent(value));
                    });

                    var title = content.elements.content.find('[data-title-editable]');
                    if (title) {
                        dataString.push('title=' + encodeURIComponent(trim(title.data('title-editable'))));
                    }

                    if (invalid.length) {
                        target.disabled(false);
                        target.hideIndicator();
                        target.showIndicator('fa fa-fw fa-exclamation-triangle');
                        toastr.error('Please review the fields in the modal and ensure you correct any invalid one.', 'Invalid Fields');
                        return;
                    }

                    request(fakeDOM.attribute('method'), parseAjaxURI(fakeDOM.attribute('action') + getAjaxSuffix()), dataString.join('&') || {}, function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            var particle = builder.get(ID),
                                block = null;

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

                                var sibling = block.block.nextSibling() || block.block.previousSibling(),
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

                            lmhistory.push(builder.serialize(), lmhistory.get().preset);

                            // if it's apply and save we also save the panel
                            if (target.data('apply-and-save') !== null) {
                                var save = $('body').find('.button-save');
                                if (save) { body.emit('click', { target: save }); }
                            }

                            modal.close();

                            toastr.success('The particle "' + particle.getTitle() + '" settings have been applied to the Layout. <br />Remember to click the Save button to store them.', 'Settings Applied');
                        }

                        target.hideIndicator();
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
