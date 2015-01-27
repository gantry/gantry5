"use strict";

var $             = require('../../utils/elements.moofx'),
    prime         = require('prime'),
    domready      = require('elements/domready'),
    modal         = require('../../ui').modal,
    getAjaxSuffix = require('../../utils/get-ajax-suffix'),
    getAjaxURL    = require('../../utils/get-ajax-url').global;

var FileManager = new prime({
    constructor: function(element){
        var data = element.data('g5-filemanager');
        this.data = data ? JSON.parse(data) : false;

        console.log(this.data);
    },

    open: function(){
        modal.open({
            method: 'post',
            data: this.data,
            content: 'Loading',
            className: 'g5-dialog-theme-default g5-modal-filemanager',
            remote: getAjaxURL('filemanager') + getAjaxSuffix()
        });
    }
});

domready(function(){
    $('body').delegate('click', '[data-g5-filemanager]', function(event, element){
        element = $(element);
        if (!element.GantryFileManager){
            element.GantryFileManager = new FileManager(element);
        }

        element.GantryFileManager.open();
    });
});


module.exports = FileManager;