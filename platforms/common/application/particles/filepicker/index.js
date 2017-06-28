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
    parseAjaxURI  = require('../../utils/get-ajax-url').parse,
    getAjaxURL    = require('../../utils/get-ajax-url').global,
    translate     = require('../../utils/translate'),
    dropzone      = require('dropzone');

var FilePicker = new prime({
    constructor: function(element) {
        var data = element.data('g5-filepicker'), value;
        this.data = data ? JSON.parse(data) : false;

        if (this.data && !this.data.value) {
            this.data.value = $(this.data.field).value();
        }

        this.colors = {
            error: '#D84747',
            success: '#9ADF87',
            small: '#aaaaaa',
            gradient: ['#9e38eb', '#4e68fc']
        };

        //console.log(this.data);
    },

    open: function() {
        if (this.data) {
            this.data.value = $(this.data.field).value();
        }

        modal.open({
            method: 'post',
            data: this.data,
            content: translate('GANTRY5_PLATFORM_JS_LOADING'),
            className: 'g5-dialog-theme-default g5-modal-filepicker',
            remote: parseAjaxURI(getAjaxURL('filepicker') + getAjaxSuffix()),
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
        return path.replace(/\/$/, '') + '/';
    },

    getPreviewTemplate: function() {
        var li    = zen('li[data-file]'),
            del   = zen('span.g-file-delete[data-g-file-delete][data-dz-remove]').html('<i class="fa fa-fw fa-trash-o" aria-hidden="true"></i>').bottom(li),
            thumb = zen('div.g-thumb[data-dz-thumbnail]').bottom(li),
            name  = zen('span.g-file-name[data-dz-name]').bottom(li),
            size  = zen('span.g-file-size[data-dz-size]').bottom(li),
            mtime = zen('span.g-file-mtime[data-dz-mtime]').bottom(li);

        zen('span.g-file-progress[data-file-uploadprogress]').html('<span class="g-file-progress-text"></span>').bottom(li);
        zen('div').bottom(thumb);

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
            colors    = this.colors,
            self      = this;

        this.content = content;

        if (files) {
            this.dropzone = new dropzone('body', {
                previewTemplate: this.getPreviewTemplate(),
                previewsContainer: files.find('ul:not(.g-list-labels)')[0],
                thumbnailWidth: 100,
                thumbnailHeight: 100,
                clickable: '[data-upload]',
                acceptedFiles: this.acceptedFiles(this.data.filter) || '',
                accept: bind(function(file, done) {
                    if (!this.data.filter) { done(); }
                    else {
                        if (file.name.toLowerCase().match(this.data.filter)) { done(); }
                        else { done('<code>' + file.name + '</code> ' + translate('GANTRY5_PLATFORM_JS_FILTER_MISMATCH') + ': <br />  <code>' + this.data.filter + '</code>'); }
                    }
                }, this),
                url: bind(function(file) {
                    return parseAjaxURI(getAjaxURL('filepicker/upload/' + global.btoa(encodeURIComponent(this.getPath() + file[0].name))) + getAjaxSuffix());
                }, this)
            });

            // dropzone events
            this.dropzone.on('thumbnail', function(file, dataUrl) {
                var ext = file.name.split('.');
                ext = (!ext.length || ext.length == 1) ? '-' : ext.reverse()[0];
                $(file.previewElement).addClass('g-image g-image-' + ext.toLowerCase()).find('[data-dz-thumbnail] > div').attribute('style', 'background-image: url(' + encodeURI(dataUrl) + ');');
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

                var ext = file.name.split('.');
                ext = (!ext.length || ext.length == 1) ? '-' : ext.reverse()[0];

                if (!file.type.match(/image.*/)) {
                    element.find('.g-thumb').text(ext);
                } else {
                    element.find('.g-thumb').addClass('g-image g-image-' + ext.toLowerCase());
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
                uploader.attribute('title', translate('GANTRY5_PLATFORM_JS_PROCESSING')).find('.g-file-progress-text').html('&bull;&bull;&bull;').attribute('title', translate('GANTRY5_PLATFORM_JS_PROCESSING'));

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
                self.refreshFiles(content);
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

                text.title('Error').html('<i class="fa fa-exclamation" aria-hidden="true"></i>').parent('[data-file-uploadprogress]').popover({
                    content: error.html ? error.html : (error.error && error.error.message ? error.error.message : error),
                    placement: 'auto',
                    trigger: 'mouse',
                    style: 'filepicker, above-modal',
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

                text.html('<i class="fa fa-check" aria-hidden="true"></i>');

                setTimeout(bind(function() {
                    uploader.animate({ opacity: 0 }, { duration: 500 });
                    thumb.animate({ opacity: 1 }, {
                        duration: 500,
                        callback: function() {
                            element.data('file', JSON.stringify(response.finfo)).data('file-url', response.url).removeClass('g-file-uploading');
                            element.dropzone = file;
                            uploader.remove();
                            mtime.text(translate('GANTRY5_PLATFORM_JUST_NOW'));
                        }
                    });
                }, this), 500);
            });
        }

        // g5 events
        content.delegate('click', '.g-bookmark-title', function(event, element) {
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
            var data     = JSON.parse(element.data('folder')),
                selected = $('[data-file].selected');

            fieldData.root = data.pathname;
            fieldData.value = selected ? selected.data('file-url') : false;
            fieldData.subfolder = true;

            element.showIndicator('fa fa-li fa-fw fa-spin-fast fa-spinner');
            request(parseAjaxURI(getAjaxURL('filepicker') + getAjaxSuffix()), fieldData).send(bind(function(error, response) {
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

        content.delegate('click', '[data-g-file-preview]', bind(function(event, element) {
            event.preventDefault();
            event.stopPropagation();
            var parent    = element.parent('[data-file]'),
                data      = JSON.parse(parent.data('file'));

            if (data.isImage) {
                var thumb = parent.find('.g-thumb > div');
                modal.open({
                    className: 'g5-dialog-theme-default g5-modal-filepreview center',
                    content: '<img src="' + thumb[0].style.backgroundImage.slice(4, -1).replace(/"/g, '') + '" />'
                });
            }
        }, this));

        content.delegate('click', '[data-g-file-delete]', bind(function(event, element) {
            event.preventDefault();
            var parent    = element.parent('[data-file]'),
                data      = JSON.parse(parent.data('file')),
                deleteURI = parseAjaxURI(getAjaxURL('filepicker/' + global.btoa(encodeURIComponent(data.pathname)) + getAjaxSuffix()));

            if (!data.isInCustom) { return false; }

            request('delete', deleteURI, function(error, response) {
                if (!response.body.success) {
                    modal.open({
                        content: response.body.html || response.body,
                        afterOpen: function(container) {
                            if (!response.body.html) { container.style({ width: '90%' }); }
                        }
                    });
                } else {
                    parent.addClass('g-file-deleted');
                    setTimeout(function() {
                        parent.remove();

                        self.refreshFiles(content);
                    }, 210);
                }
            });
        }, this));

        content.delegate('click', '[data-file]', bind(function(event, element) {
            if (event && event.preventDefault) { event.preventDefault(); }
            var target = $(event.target),
                remove = target.data('g-file-delete') !== null || target.parent('[data-g-file-delete]'),
                preview = target.data('g-file-preview') !== null || target.parent('[data-g-file-preview]');

            if (element.hasClass('g-file-error') || element.hasClass('g-file-uploading') || remove || preview) { return; }
            var data = JSON.parse(element.data('file'));

            files.search('[data-file]').removeClass('selected');
            element.addClass('selected');
        }, this));

        content.delegate('click', '[data-select]', bind(function(event, element) {
            if (event && event.preventDefault) { event.preventDefault(); }
            var selected = files.find('[data-file].selected'),
                value    = selected ? selected.data('file-url') : '';

            $(this.data.field).value(value);
            modal.close();
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
    },

    acceptedFiles: function(filter) {
        var attr = '';
        switch (filter) {
            case '.(jpe?g|gif|png|svg)$':
                attr = '.jpg,.jpeg,.gif,.png,.svg,.JPG,.JPEG,.GIF,.PNG,.SVG';
                break;
            case '.(mp4|webm|ogv|mov)$':
                attr = '.mp4,.webm,.ogv,.mov,.MP4,.WEBM,.OGV,.MOV';
                break;
        }

        return attr;
    },

    refreshFiles: function(content) {
        var active = $('[data-folder].active'),
            folder = active[active.length - 1];
        if (folder) {
            content.emit('click', { target: $(folder) });
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
