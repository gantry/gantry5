"use strict";

var $             = require('../../utils/elements.utils'),
    prime         = require('prime'),
    request       = require('agent'),
    zen           = require('elements/zen'),
    domready      = require('elements/domready'),
    bind          = require('mout/function/bind'),
    rtrim         = require('mout/string/rtrim'),
    deepClone     = require('mout/lang/deepClone'),
    deepFillIn    = require('mout/object/deepFillIn'),
    modal         = require('../../ui').modal,
    getAjaxSuffix = require('../../utils/get-ajax-suffix'),
    getAjaxURL    = require('../../utils/get-ajax-url').global,
    dropzone      = require('dropzone');

var FilePicker = new prime({
    constructor: function(element) {
        var data = element.data('g5-filepicker');
        this.data = data ? JSON.parse(data) : false;

        this.colors = {
            error: '#D84747',
            success: '#9ADF87',
            small: '#aaaaaa',
            gradient: ['#9e38eb', '#4e68fc']
        };

        //console.log(this.data);
    },

    open: function() {
        modal.open({
            method: 'post',
            data: this.data,
            content: 'Loading',
            className: 'g5-dialog-theme-default g5-modal-filepicker',
            remote: getAjaxURL('filepicker') + getAjaxSuffix(),
            remoteLoaded: bind(this.loaded, this),
            afterClose: bind(function() {
                if (this.dropzone) { this.dropzone.destroy(); }
            }, this)
        });
    },

    getPath: function() {
        var actives = this.content.search('.g-folders .active'), active, path;
        if (!actives) { return null; }

        active = $(actives[actives.length - 1]);
        path = JSON.parse(active.data('folder')).pathname;
        return rtrim(path, '/') + '/';
    },

    getPreviewTemplate: function() {
        var li    = zen('li[data-file]'),
            thumb = zen('div.g-thumb[data-dz-thumbnail]').bottom(li),
            name  = zen('span.g-file-name[data-dz-name]').bottom(li),
            size  = zen('span.g-file-size[data-dz-size]').bottom(li),
            mtime = zen('span.g-file-mtime[data-dz-mtime]').bottom(li);

        zen('span.g-file-progress[data-file-uploadprogress]').html('<span class="g-file-progress-text"></span>').bottom(li);

        li.bottom('body');
        var html = li[0].outerHTML;
        li.remove();

        return html;
    },

    loaded: function(response, modalInstance) {
        var content   = modalInstance.elements.content,
            bookmarks = content.search('.g-bookmark'),
            files     = content.find('.g-files'),
            fieldData = deepClone(this.data),
            colors    = this.colors;

        this.content = content;

        if (files) {
            this.dropzone = new dropzone('body', {
                previewTemplate: this.getPreviewTemplate(),
                previewsContainer: files.find('ul:not(.g-list-labels)')[0],
                thumbnailWidth: 100,
                thumbnailHeight: 100,
                url: bind(function(file) {
                    return getAjaxURL('filepicker/upload/' + this.getPath() + file[0].name) + getAjaxSuffix();
                }, this)
            });


            this.dropzone.on('thumbnail', function(file, dataUrl) {
                $(file.previewElement).find('[data-dz-thumbnail]').attribute('style', 'background-image: url(' + dataUrl + ');');
            });

            this.dropzone.on('addedfile', function(file) {
                var element      = $(file.previewElement),
                    uploader     = element.find('[data-file-uploadprogress]'),
                    isList       = files.hasClass('g-filemode-list'),
                    progressConf = {
                        value: 0,
                        animation: false,
                        insertLocation: 'bottom'
                    };

                if (!file.type.match(/image.*/)) {
                    var ext = file.name.split('.');
                    ext = (!ext.length || ext.length == 1) ? '-' : ext.reverse()[0];
                    element.find('.g-thumb').text(ext);
                }

                progressConf = deepFillIn((isList ? {
                    size: 20,
                    thickness: 10,
                    fill: {
                        color: colors.small,
                        gradient: false
                    }
                } : {
                    size: 50,
                    thickness: 'auto',
                    fill: {
                        gradient: colors.gradient,
                        color: false
                    }
                }), progressConf);

                element.addClass('g-file-uploading');
                uploader.progresser(progressConf);
                uploader.attribute('title', 'processing...').find('.g-file-progress-text').html('&bull;&bull;&bull;').attribute('title', 'processing...');

            }).on('processing', function(file) {

                var element = $(file.previewElement).find('[data-file-uploadprogress]');
                element.find('.g-file-progress-text').text('0%').attribute('title', '0%');

            }).on('sending', function(file, xhr, formData) {

                var element = $(file.previewElement).find('[data-file-uploadprogress]');
                element.attribute('title', '0%').find('.g-file-progress-text').text('0%').attribute('title', '0%');

            }).on('uploadprogress', function(file, progress, bytesSent) {

                var element = $(file.previewElement).find('[data-file-uploadprogress]');
                element.progresser({ value: progress / 100 });
                element.attribute('title', Math.round(progress) + '%').find('.g-file-progress-text').text(Math.round(progress) + '%').attribute('title', Math.round(progress) + '%');

            }).on('complete', function(file) {

            }).on('error', function(file, error) {
                var element  = $(file.previewElement),
                    uploader = element.find('[data-file-uploadprogress]'),
                    text     = element.find('.g-file-progress-text'),
                    isList   = files.hasClass('g-filemode-list');

                element.addClass('g-file-error');

                uploader.title('Error').progresser({
                    fill: {
                        color: colors.error,
                        gradient: false
                    },
                    value: 1,
                    thickness: isList ? 10 : 25
                });

                text.title('Error').html('<i class="fa fa-exclamation"></i>').parent('[data-file-uploadprogress]').popover({
                    content: error.html ? error.html : error,
                    placement: 'auto',
                    trigger: 'mouse',
                    style: 'above-modal',
                    width: 'auto',
                    targetEvents: false
                });

            }).on('success', function(file, response, xhr) {
                var element  = $(file.previewElement),
                    uploader = element.find('[data-file-uploadprogress]'),
                    mtime    = element.find('.g-file-mtime'),
                    text     = element.find('.g-file-progress-text'),
                    thumb    = element.find('.g-thumb'),
                    isList   = files.hasClass('g-filemode-list');

                uploader.progresser({
                    fill: {
                        color: colors.success,
                        gradient: false
                    },
                    value: 1,
                    thickness: isList ? 10 : 25
                });

                text.html('<i class="fa fa-check"></i>');

                setTimeout(bind(function() {
                    uploader.animate({ opacity: 0 }, { duration: 500 });
                    thumb.animate({ opacity: 1 }, {
                        duration: 500,
                        callback: function() {
                            element.removeClass('g-file-uploading');
                            uploader.remove();
                            mtime.text('just now');
                        }
                    });
                }, this), 500);
            });
        }

        content.delegate('click', '.g-bookmark-title', function(e, element) {
            if (event && event.preventDefault) { event.preventDefault(); }
            var sibling = element.nextSibling('.g-folders'),
                parent  = element.parent('.g-bookmark');

            if (!sibling) { return; }
            sibling.slideToggle(function() {
                parent.toggleClass('collapsed', sibling.gSlideCollapsed);
            });
        });

        content.delegate('click', '[data-folder]', bind(function(event, element) {
            if (event && event.preventDefault) { event.preventDefault(); }
            var data = JSON.parse(element.data('folder'));

            fieldData.root = data.pathname;
            fieldData.subfolder = true;

            element.showIndicator('fa fa-li fa-fw fa-spin-fast fa-spinner');
            request(getAjaxURL('filepicker') + getAjaxSuffix(), fieldData).send(bind(function(error, response) {
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

                    if (response.body.files) {
                        files.empty();
                        dummy = zen('div').html(response.body.files);
                        dummy.children().bottom(files).style({ opacity: 0 }).animate({ opacity: 1 }, { duration: '250ms' });
                    } else {
                        files.find('> ul:not(.g-list-labels)').empty();
                    }

                    this.dropzone.previewsContainer = files.find('ul:not(.g-list-labels)')[0];
                }
            }, this));
        }, this));

        content.delegate('click', '[data-file]', bind(function(event, element) {
            if (event && event.preventDefault) { event.preventDefault(); }
            if (element.hasClass('g-file-error') || element.hasClass('g-file-uploading')) { return; }
            var data = JSON.parse(element.data('file'));

            files.search('[data-file]').removeClass('selected');
            element.addClass('selected');
        }, this));

        content.delegate('click', '[data-files-mode]', bind(function(event, element) {
            if (event && event.preventDefault) { event.preventDefault(); }
            if (element.hasClass('active')) { return; }

            var modes = $('[data-files-mode]');
            modes.removeClass('active');
            element.addClass('active');

            files.animate({ opacity: 0 }, {
                duration: 200,
                callback: function() {
                    var mode           = element.data('files-mode'),
                        uploadProgress = files.search('[data-file-uploadprogress]'),
                        progressConf   = (mode == 'list') ? {
                            size: 20,
                            thickness: 10,
                            fill: {
                                color: colors.small,
                                gradient: false
                            }
                        } : {
                            size: 50,
                            thickness: 'auto',
                            fill: {
                                gradient: colors.gradient,
                                color: false
                            }
                        };


                    files.attribute('class', 'g-files g-block g-filemode-' + mode);
                    if (uploadProgress) {
                        uploadProgress.forEach(function(element) {
                            element = $(element);
                            var config = deepClone(progressConf);

                            if (element.parent('.g-file-error')) {
                                config.fill = { color: colors.error };
                                config.value = 1;
                                config.thickness = mode == 'list' ? 10 : 25;
                            }

                            element.progresser(config);
                        });
                    }
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
    body.delegate('click', '[data-g5-filepicker]', function(event, element) {
        if (event && event.preventDefault) { event.preventDefault(); }
        element = $(element);
        if (!element.GantryFilePicker) {
            element.GantryFilePicker = new FilePicker(element);
        }

        element.GantryFilePicker.open();
    });
});


module.exports = FilePicker;