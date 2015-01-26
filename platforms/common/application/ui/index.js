/*var ready = require('elements/domready'),
    $     = require('elements');

ready(function(){
    var toggle = $('[data-sidebar-toggle]'),
        mode   = $('[data-mode-toggle] > span');
    if (!toggle && !mode) return;

    if (toggle) toggle.on('click', function(){
        var sidebar  = $('.block.sidebar-block'),
            elements = $([toggle, sidebar]);
        if (sidebar.hasClass('sidebar-closed')) elements.removeClass('sidebar-closed').removeClass('sidebar-icons');
        else if (sidebar.hasClass('sidebar-icons')) elements.removeClass('sidebar-icons').addClass('sidebar-closed');
        else elements.addClass('sidebar-icons');
    });

    if (mode) mode.on('click', function(){
        var current  = this.parent().hasClass('production') ? 'production' : 'development',
            opposite = current == 'production' ? 'development' : 'production';

        this.parent().removeClass(current).addClass(opposite);
        $('[data-mode-indicator]').data('mode-indicator', opposite);
    });
});

// enablers
ready(function(){
    var input, hiddens, radios;
    $(document).delegate('click', '.enabler .toggle', function(e, element){
        element = $(element);
        hiddens = element.find('~~ [type=hidden]');

        if (!hiddens) return true;
        if (hiddens) hiddens.value(hiddens.value() == '0' ? '1' : '0');
    });
});*/
"use strict";

var ready = require('elements/domready'),
    Modal = require('./modal'),
    Selectize = require('./selectize');

module.exports = {
    modal: new Modal(),
    togglers: require('./togglers'),
    selectize: Selectize
};
