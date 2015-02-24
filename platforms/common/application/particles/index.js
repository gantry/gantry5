"use strict";
var ready = require('elements/domready'),
    $ = require('elements'),
    zen = require('elements/zen'),
    modal = require('../ui').modal,
    toastr = require('../ui').toastr,
    request = require('agent'),

    trim = require('mout/string/trim'),

    getAjaxSuffix = require('../utils/get-ajax-suffix');


ready(function () {
    var body = $('body');

    body.delegate('click', '#settings [data-collection-add]', function (event, element) {
        event.preventDefault();

        var content = $('.collection-list'),
            titleEdit = element,
            titleKey = element.data('[data-title-edit]'),
            title = content.find('[data-collection-title-' + titleKey + ']'),
            titleValue;

        if (title && titleEdit) {
            titleEdit.on('click', function () {
                title.attribute('contenteditable', 'true');
                title[0].focus();

                var range = document.createRange(), selection;
                range.selectNodeContents(title[0]);
                selection = window.getSelection();
                selection.removeAllRanges();
                selection.addRange(range);

                titleValue = trim(title.text());
            });

            title.on('keydown', function (event) {

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
            }).on('blur', function () {
                title.attribute('contenteditable', null);
                title.data('collection-title', trim(title.text()));
                window.getSelection().removeAllRanges();
            });
        }

    });

    body.delegate('click', '#settings [data-collection-editall]', function (event, element) {
        event.preventDefault();

        var data = {};

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: $(element).attribute('href') + getAjaxSuffix(),
            remoteLoaded: function (response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) {
                    return true;
                }

                // Particle Settings apply
                submit.on('click', function (e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showSpinner();

                    $(form[0].elements).forEach(function (input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) {
                            return;
                        }
                        dataString.push(name + '=' + value);
                    });

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function (error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function (container) {
                                    if (!response.body.html) {
                                        container.style({width: '90%'});
                                    }
                                }
                            });
                        } else {
                            /*if (response.body.path) {
                             menumanager.items[response.body.path] = response.body.item;
                             } else {
                             menumanager.settings = response.body.settings;
                             }

                             if (response.body.html) {
                             var parent = element.parent('[data-mm-id]');
                             if (parent) {
                             parent.html(response.body.html);
                             }
                             }*/

                            modal.close();
                            toastr.success('Test save', 'Settings Applied');
                        }

                        submit.hideSpinner();
                    });
                });
            }
        });


    });

    body.delegate('click', '#settings [data-collection-edit-title]', function (event, element) {
        event.preventDefault();

        var titleEdit = element,
            titleKey = element.data('collection-edit-title'),
            collection = element.parent('[data-field-name]'),
            title = collection.find('[data-collection-edit-title-' + titleKey + ']'),
            titleValue;

        console.log( titleEdit+' titleEdit');
        console.log(titleKey+' titleKey');
        console.log(collection+' collection');
        console.log(title+' title');

        if (title && titleEdit) {
            title.attribute('contenteditable', 'true');
            title[0].focus();

            var range = document.createRange(), selection;
            range.selectNodeContents(title[0]);
            selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);

            titleValue = trim(title.text());

            title.on('keydown', function (event) {

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
            }).on('blur', function () {
                title.attribute('contenteditable', null);
                title.data('collection-title', trim(title.text()));
                window.getSelection().removeAllRanges();
            });
        }

    });

    body.delegate('click', '#settings [data-collection-edit]', function (event, element) {
        event.preventDefault();

        var data = {};

        modal.open({
            content: 'Loading',
            method: 'post',
            data: data,
            remote: $(element).attribute('href') + getAjaxSuffix(),
            remoteLoaded: function (response, content) {
                var form = content.elements.content.find('form'),
                    submit = content.elements.content.find('input[type="submit"], button[type="submit"]'),
                    dataString = [];

                if (!form || !submit) {
                    return true;
                }

                // Particle Settings apply
                submit.on('click', function (e) {
                    e.preventDefault();
                    dataString = [];

                    submit.showSpinner();

                    $(form[0].elements).forEach(function (input) {
                        input = $(input);
                        var name = input.attribute('name'),
                            value = input.value();

                        if (!name) {
                            return;
                        }
                        dataString.push(name + '=' + value);
                    });

                    request(form.attribute('method'), form.attribute('action') + getAjaxSuffix(), dataString.join('&'), function (error, response) {
                        if (!response.body.success) {
                            modal.open({
                                content: response.body.html || response.body,
                                afterOpen: function (container) {
                                    if (!response.body.html) {
                                        container.style({width: '90%'});
                                    }
                                }
                            });
                        } else {
                            /*if (response.body.path) {
                                menumanager.items[response.body.path] = response.body.item;
                            } else {
                                menumanager.settings = response.body.settings;
                            }

                            if (response.body.html) {
                                var parent = element.parent('[data-mm-id]');
                                if (parent) {
                                    parent.html(response.body.html);
                                }
                            }*/

                            modal.close();
                            toastr.success('Test save', 'Settings Applied');
                        }

                        submit.hideSpinner();
                    });
                });
            }
        });
    });
});

module.exports = {
    colorpicker: require('./colorpicker'),
    fonts: require('./fonts'),
    menu: require('./menu'),
    icons: require('./icons'),
    filepicker: require('./filepicker')
};