"use strict";

var $             = require('../../utils/elements.utils'),
    prime         = require('prime'),
    request       = require('agent'),
    zen           = require('elements/zen'),
    domready      = require('elements/domready'),
    bind          = require('mout/function/bind'),
    rtrim         = require('mout/string/rtrim'),
    deepClone     = require('mout/lang/deepClone'),
    modal         = require('../../ui').modal,
    getAjaxSuffix = require('../../utils/get-ajax-suffix'),
    getAjaxURL    = require('../../utils/get-ajax-url').global;

var FileManager = new prime({
    constructor: function(element) {
        var data = element.data('g5-filemanager');
        this.data = data ? JSON.parse(data) : false;

        console.log(this.data);
    },

    open: function() {
        modal.open({
            method: 'post',
            data: this.data,
            content: 'Loading',
            className: 'g5-dialog-theme-default g5-modal-filemanager',
            remote: getAjaxURL('filemanager') + getAjaxSuffix(),
            remoteLoaded: bind(this.loaded, this)
        });
    },

    loaded: function(response, modalInstance) {
        var content = modalInstance.elements.content,
            bookmarks = content.search('.g-bookmark'),
            files = content.find('.g-files'),
            fieldData = deepClone(this.data);

        this.content = content;

        content.delegate('click', '.g-bookmark-title', function(e, element) {
            var sibling = element.nextSibling('.g-folders'),
                parent = element.parent('.g-bookmark');

            if (!sibling) { return; }
            sibling.slideToggle(function() {
                parent.toggleClass('collapsed', sibling.gSlideCollapsed);
            });
        });

        content.delegate('click', '[data-folder]', bind(function(e, element) {
            var data = JSON.parse(element.data('folder'));

            fieldData.root = data.pathname;
            fieldData.subfolder = true;

            element.showIndicator('fa fa-li fa-fw fa-spin-fast fa-spinner');
            request(getAjaxURL('filemanager') + getAjaxSuffix(), fieldData).send(bind(function(error, response) {
                element.hideIndicator();
                this.addActiveState(element);

                if (!response.body.success) {
                    modal.open({
                        content: response.body.html || response.body,
                        afterOpen: function(container) {
                            if (!response.body.html) { container.style({ width: '90%' }); }
                        }
                    });
                } else {
                    var dummy, next;
                    if (response.body.subfolder) {
                        dummy = zen('div').html(response.body.subfolder);
                        next = element.nextSibling();

                        if (next && !next.attribute('data-folder')) { next.remove(); }
                        dummy.children().after(element);
                    }

                    files.empty();
                    if (response.body.files) {
                        dummy = zen('div').html(response.body.files);
                        dummy.children().bottom(files).style({ opacity: 0 }).animate({ opacity: 1 }, { duration: '250ms' });
                    }
                    //files.children().style({opacity: 0}).animate({opacity: 1}, {duration: '250ms'});
                }
            }, this));
        }, this));

        content.delegate('click', '[data-file]', bind(function(e, element) {
            var data = JSON.parse(element.data('file'));

            files.search('[data-file]').removeClass('selected');
            element.addClass('selected');
        }, this));

        content.delegate('click', '[data-files-mode]', bind(function(e, element) {
            if (element.hasClass('active')) { return; }

            var modes = $('[data-files-mode]');
            modes.removeClass('active');
            element.addClass('active');

            files.animate({ opacity: 0 }, {
                duration: 200,
                callback: function() {
                    files.attribute('class', 'g-files g-block g-filemode-' + element.data('files-mode'));
                    files.animate({ opacity: 1 }, { duration: 200 });
                }
            });

        }, this));
    },

    addActiveState: function(element) {
        var opened = this.content.search('[data-folder].active, .g-folders > .active'), parent = element.parent();
        if (opened) { opened.removeClass('active'); }

        element.addClass('active');

        while (parent.tag() == 'ul' && !parent.hasClass('g-folders')) {
            parent.previousSibling().addClass('active');
            parent = parent.parent();
        }
    }
});

domready(function() {
    var body = $('body');
    body.delegate('click', '[data-g5-filemanager]', function(event, element) {
        element = $(element);
        if (!element.GantryFileManager) {
            element.GantryFileManager = new FileManager(element);
        }

        element.GantryFileManager.open();
    });
});


module.exports = FileManager;