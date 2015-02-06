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

    getAjaxSuffix = require('../utils/get-ajax-suffix'),

    Builder       = require('./builder'),
    History       = require('../utils/history'),
    LMHistory     = require('./history'),
    LayoutManager = require('./layoutmanager');

require('../ui/popover');

var builder, layoutmanager, lmhistory;


builder = new Builder();
lmhistory = new LMHistory();

ready(function(){
    var HM = {
        back:    $('[data-lm-back]'),
        forward: $('[data-lm-forward]')
    };

    if (!HM.back && !HM.forward) return;

    HM.back.on('click', function(){
        if ($(this).hasClass('disabled')) return false;
        lmhistory.undo();
    });

    HM.forward.on('click', function(){
        if ($(this).hasClass('disabled')) return false;
        lmhistory.redo();
    });

    /* lmhistory events */
    lmhistory.on('push', function(session, index, reset){
        if (index && HM.back.hasClass('disabled')) HM.back.removeClass('disabled');
        if (reset && !HM.forward.hasClass('disabled')) HM.forward.addClass('disabled');
    });
    lmhistory.on('undo', function(session, index){
        builder.reset(session.data);
        HM.forward.removeClass('disabled');
        if (!index) HM.back.addClass('disabled');
        layoutmanager.singles('disable');
    });
    lmhistory.on('redo', function(session, index){
        builder.reset(session.data);
        HM.back.removeClass('disabled');
        if (index == this.session.length - 1) HM.forward.addClass('disabled');
        layoutmanager.singles('disable');
    });

});

ready(function() {
    var body = $('body'), root = $('[data-lm-root]'), data;

    // load builder data
    if (root) {
        data = JSON.parse(root.data('lm-root'));
        if (data.name) { data = data.layout; }
        builder.setStructure(data);
        builder.load();
        lmhistory.setSession(builder.serialize());
    }

    // attach events
    // Save
    body.delegate('click', '.button-save', function(e, element) {
        e.preventDefault();
        element.showSpinner();

        var data = {},
            type = element.data('save'),
            sentence = type + ' ' + (type.slice(-1) == 's' ? 'have' : 'has');

        if ($('[data-lm-root]')) { data.layout = JSON.stringify(builder.serialize()); }
        else {
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

    // Modal Tabs
    body.delegate('click', '.g-tabs a', function(event, element) {
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
    });

    // Picker
    body.delegate('statechangeBefore', '[data-g5-lm-picker]', function() {
        modal.close();
    });

    // Sub-navigation links
    body.delegate('statechangeAfter', '#navbar [data-g5-ajaxify]', function(event, element) {
        var root = $('[data-lm-root]');
        if (!root) { return true; }
        data = JSON.parse(root.data('lm-root'));
        builder.setStructure(data);
        builder.load();
        lmhistory.setSession(builder.serialize());

        // refresh LM eraser
        layoutmanager.eraser.element = $('[data-lm-eraseblock]');
        layoutmanager.eraser.hide();
    });

    // Particles filtering
    body.delegate('input', '.sidebar-block .search input', function(event, element){
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

    // TODO: this was the + handler for new layouts which is now gone in favor of Configurations
    body.delegate('click', '[data-g5-lm-add]', function(event, element) {
        event.preventDefault();
        modal.open({
            content: 'Loading',
            remote: $(element).attribute('href') + getAjaxSuffix()
        });
    });

    // Layout Manager
    layoutmanager = new LayoutManager('body', {
        delegate: '[data-lm-root] .g-grid > .g-block > [data-lm-blocktype]:not([data-lm-nodrag]) !> .g-block, .g5-lm-particles-picker [data-lm-blocktype], [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag]), [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag])',
        droppables: '[data-lm-dropzone]',
        exclude: '.section-header .button, .lm-newblocks .float-right .button, [data-lm-nodrag]',
        resize_handles: '[data-lm-root] .g-grid > .g-block:not(:last-child)',
        builder: builder,
        history: lmhistory
    });

    // Grid same widths button (even-ize)
    body.delegate('click', '[data-lm-samewidth]', function(event, element) {
        var clientRect = element[0].getBoundingClientRect();
        if (event.clientX < clientRect.width + clientRect.left) { return; }
        
        var blocks = element.search('> [data-lm-blocktype="block"]'), id;
        if (!blocks || blocks.length == 1) { return; }

        blocks.forEach(function(block){
            id = $(block).data('lm-id');
            builder.get(id).setSize(100 / blocks.length, true);
        });

        lmhistory.push(builder.serialize());
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
            parentID = parent ? parent.data('lm-id') : false;

        if (!contains(['block', 'grid', 'section', 'atom'], blocktype)) {
            data = {};
            data.type = builder.get(element.data('lm-id')).getType() || element.data('lm-blocktype') || false;
            data.subtype = builder.get(element.data('lm-id')).getSubType() || element.data('lm-blocksubtype') || false;
            data.options = builder.get(element.data('lm-id')).getAttributes() || {};
            data.block = builder.get(parent.data('lm-id')).getAttributes() || {};

            if (!data.type) { delete data.type; }
            if (!data.subtype) { delete data.subtype; }
        }

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: settingsURL + getAjaxSuffix(),
            remoteLoaded: function(response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) { return true; }

                var title = content.elements.content.find('[data-particle-title]'),
                    titleEdit = content.elements.content.find('[data-title-edit]'),
                    titleValue;

                if (title && titleEdit) {
                    titleEdit.on('click', function() {
                        title.attribute('contenteditable', 'true');
                        title[0].focus();

                        var range = document.createRange(), selection;
                        range.selectNodeContents(title[0]);
                        selection = window.getSelection();
                        selection.removeAllRanges();
                        selection.addRange(range);

                        titleValue = trim(title.text());
                    });

                    title.on('keydown', function(event) {

                        switch (event.keyCode) {
                            case 13: // return
                            case 27: // esc
                                event.stopPropagation();
                                if (event.keyCode == 27) {
                                    title.text(titleValue);
                                }

                                title.attribute('contenteditable', null);
                                window.getSelection().removeAllRanges();
                                title[0].blur();

                                return false;
                            default:
                                return true;
                        }
                    }).on('blur', function(){
                        title.attribute('contenteditable', null);
                        title.data('particle-title', trim(title.text()));
                        window.getSelection().removeAllRanges();
                    });
                }

                // Particle Settings apply
                submit.on('click', function(e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showSpinner();

                    $(form[0].elements).forEach(function(input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) { return; }
                        dataString.push(name + '=' + value);
                    });

                    if (title) {
                        dataString.push('title=' + title.data('particle-title'));
                    }

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function(error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function(container) {
                                    if (!response.body.html) { container.style({ width: '90%' }); }
                                }
                            });
                        } else {
                            var particle = builder.get(ID),
                                block = builder.get(parentID);

                            // particle attributes
                            particle.setAttributes(response.body.data.options);
                            particle.setTitle(response.body.data.title || 'Untitled');
                            particle.updateTitle(particle.getTitle());

                            // parent block attributes
                            if (response.body.data.block && size(response.body.data.block)) {
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

                            lmhistory.push(builder.serialize());
                            modal.close();
                            toastr.success('The particle "'+particle.getTitle()+'" settings have been applied to the Layout. <br />Remember to click the Save button to store them.', 'Settings Applied');
                        }

                        submit.hideSpinner();
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
    history: lmhistory
};