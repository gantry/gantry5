var ready       = require('elements/domready'),
    json        = require('./json_test'),
    $           = require('elements/attributes'),

    Builder     = require('./builder'),
    DropManager = require('./drop'),
    History     = require('./history');

var builder, dropmanager, history;

ready(function(){
    builder     = new Builder(json).load();
    history     = new History(builder.serialize());

    ready(function(){
    dropmanager = new DropManager('#main', {
        delegate:       '[data-lm-root="page"] .section, [data-lm-root="section"] .section > .grid [data-lm-blocktype]:not([data-lm-nodrag]), .lm-newblocks [data-lm-blocktype]',
        droppables:     '[data-lm-dropzone]',
        exclude:        '.section-header .button, .lm-newblocks .float-right .button',
        resize_handles: '[data-lm-root="section"] .grid > .block:not(:last-child)',
        builder:        builder,
        history:        history
    });
    });
    //console.log('Serialized: ', builder.serialize());
    //console.log('Map: ', builder.map);
});

ready(function(){
    var HM = {
        back:    $('[data-lm-back]'),
        forward: $('[data-lm-forward]')
    };

    if (!HM.back && !HM.forward) return;

    HM.back.on('click', function(){
        if ($(this).hasClass('disabled')) return false;
        history.undo();
    });

    HM.forward.on('click', function(){
        if ($(this).hasClass('disabled')) return false;
        history.redo();
    });

    /* history events */
    history.on('push', function(session, index, reset){
        if (index && HM.back.hasClass('disabled')) HM.back.removeClass('disabled');
        if (reset && !HM.forward.hasClass('disabled')) HM.forward.addClass('disabled');
    });
    history.on('undo', function(session, index){
        builder.reset(session.data);
        HM.forward.removeClass('disabled');
        if (!index) HM.back.addClass('disabled');
        dropmanager.singles('disable');
    });
    history.on('redo', function(session, index){
        builder.reset(session.data);
        HM.back.removeClass('disabled');
        if (index == this.session.length - 1) HM.forward.addClass('disabled');
        dropmanager.singles('disable');
    });

});

module.exports = {
    builder:     builder,
    dropmanager: dropmanager,
    history:     history,
    $:           $
};
