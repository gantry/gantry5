"use strict";
var ready         = require('elements/domready'),
    //json          = require('./json_test'),
    $             = require('elements/attributes'),
    modal         = require('../ui').modal,
    request       = require('agent'),
    zen           = require('elements/zen'),
    contains      = require('mout/array/contains'),
    size          = require('mout/collection/size'),
    trim          = require('mout/string/trim'),

    getAjaxSuffix = require('../utils/get-ajax-suffix'),

    Builder       = require('./builder'),
    History       = require('../utils/History'),
    LMHistory     = require('./history'),
    LayoutManager = require('./layoutmanager');

require('../ui/popover');

var builder, layoutmanager, lmhistory;


builder = new Builder();
lmhistory = new LMHistory(builder.serialize());

/*var particlesPopover = function() {
    var particles = $('[data-lm-addparticle]');
    particles.popover({
        type: 'async',
        placement: 'left-bottom',
        width: '200',
        style: 'particles, inverse, fixed, nooverflow',
        url: particles.attribute('href') + getAjaxSuffix()
    }).on('shown.popover', function(popover) {
        if (popover.$target.particleFilter) { return false; }

        var search = popover.$target.find('input[type=text]'),
            list = popover.$target.search('[data-lm-blocktype]');
        if (!search) { return false; }

        popover.$target.particleFilter = true;
        search.on('input', function(e) {
            list.style({ display: 'none' }).forEach(function(blocktype) {
                var value = this.value().toLowerCase();
                blocktype = $(blocktype);
                if (blocktype.data('lm-blocktype').toLowerCase().match(value) || blocktype.text().toLowerCase().match(value)) {
                    blocktype.style({ display: 'block' });
                }
            }, this);
        });
    });
};*/

ready(function() {
    var body = $('body'), root = $('[data-lm-root]'), data;

    // load builder data
    if (root) {
        data = JSON.parse(root.data('lm-root'));
        if (data.name) {
            //$('[data-g5-content]').find('.title').text(data.name);
            data = data.layout;
        }
        builder.setStructure(data);
        builder.load();
        //particlesPopover();
    }

    // attach events
    // Save
    body.delegate('click', '.button-save', function(e, element) {
        if (!$('[data-lm-root]')) { return true; }

        e.preventDefault();

        var lm = JSON.stringify(builder.serialize());

        request('post', window.location.href + getAjaxSuffix(), {
            //title: $('[data-g5-content] h2 .title').text().toLowerCase(), // we dont need the title anymore
            layout: lm
        }, function(error, response) {
            if (!response.body.success) {
                modal.open({
                    content: response.body.html || response.body,
                    afterOpen: function(container) {
                        if (!response.body.html) { container.style({ width: '90%' }); }
                    }
                });

                return false;
            } else {
                modal.close();
            }
        });
    });

    // Tabs
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

    body.delegate('statechangeAfter', '#navbar [data-g5-ajaxify]', function(event, element) {
        if (!$('[data-lm-root]')) { return true; }
        data = JSON.parse($('[data-lm-root]').data('lm-root'));
        builder.setStructure(data);
        builder.load();

        // -!- Popovers
        // particles picker
        //particlesPopover();

        // refresh LM eraser
        layoutmanager.eraser.element = $('[data-lm-eraseblock]');
        layoutmanager.eraser.hide();
    });

    body.delegate('input', '.sidebar-block .search input', function(event, element){
        var value = $(element).value().toLowerCase(),
            list = $('.sidebar-block [data-lm-blocktype]');
        if (!list) { return false; }

        list.style({ display: 'none' }).forEach(function(blocktype) {
            blocktype = $(blocktype);
            if (blocktype.data('lm-blocktype').toLowerCase().match(value) || blocktype.text().toLowerCase().match(value)) {
                blocktype.style({ display: 'block' });
            }
        }, this);
    });

    body.delegate('click', '[data-g5-lm-add]', function(event, element) {
        event.preventDefault();
        modal.open({
            content: 'Loading',
            remote: $(element).attribute('href') + getAjaxSuffix()
        });
    });

    // layoutmanager
    layoutmanager = new LayoutManager('body', {
        delegate: '[data-lm-root] .g-grid > .g-block > [data-lm-blocktype]:not([data-lm-nodrag]) !> .g-block, .g5-lm-particles-picker [data-lm-blocktype], [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag]), [data-lm-root] [data-lm-blocktype="section"] > [data-lm-blocktype="container"] > [data-lm-blocktype="grid"]:not(:empty):not(.no-move):not([data-lm-nodrag])',
        droppables: '[data-lm-dropzone]',
        exclude: '.section-header .button, .lm-newblocks .float-right .button, [data-lm-nodrag]',
        resize_handles: '[data-lm-root] .g-grid > .g-block:not(:last-child)',
        builder: builder,
        history: lmhistory
    });

    // Particles settings
    body.delegate('click', '[data-lm-settings]', function(event, element) {
        element = $(element);

        var blocktype = element.data('lm-blocktype'),
            settingsURL = element.data('lm-settings'),
            data = null, parent;

        // grid is a special case, since relies on pseudo elements for sorting and settings
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

                submit.on('click', function(e) {
                    e.preventDefault();
                    dataString = [];

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
                            return false;
                        } else {
                            var particle = builder.get(ID),
                                block = builder.get(parentID);

                            // particle attributes
                            particle.setAttributes(response.body.data.options);
                            particle.setTitle(response.body.data.title || 'Untitled');
                            particle.updateTitle(particle.getTitle());

                            // parent block attributes
                            if (response.body.data.block && size(response.body.data.block)) {
                                var sibling = block.block.nextSibling(),
                                    currentSize = block.getSize(),
                                    diffSize;

                                block.setAttributes(response.body.data.block);

                                diffSize = currentSize - block.getSize();

                                block.setAnimatedSize(block.getAttribute('size'));

                                if (sibling) {
                                    sibling = builder.get(sibling.data('lm-id'));
                                    sibling.setSize(sibling.getSize() + diffSize, true);
                                }
                            }

                            modal.close();
                        }
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