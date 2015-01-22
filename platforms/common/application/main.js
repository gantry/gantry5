var elements = require('elements');
require('elements/attributes');
require('elements/events');
require('elements/delegation');
require('elements/insertion');
require('elements/traversal');
require('./ui/popover');
require('./utils/ajaxify-links');


/*
 var zen      = require('elements/zen'),
 domready = require('elements/domready'),
 $        = require('elements/attributes');

 /*
 var lm       = require('./lm');
 */
/*var drag = require('./lm/ui/drag');

 domready(function(){
 var d = new drag('#test');
 d.create();
 d.on('dragbase:move', function(event){
 var root = $(event.target).data('lm-root');
 if (root != null){ d.setCurrent(event.target); }
 });

 $('[data-lm-root]').on('dragbase:enter', function(e){
 console.log(e);
 });
 });
 */
module.exports = {
    /*mout    : require('mout'),
     prime   : require('prime'),
     "$"     : elements,
     zen     : zen,
     domready: domready,
     agent   : require('agent'),*/
    lm: require('./lm'),
    ui: require('./ui'),
    styles: require('./styles'),
    "$": elements,
    domready: require('elements/domready'),
    particles: require('./particles')
};


//module.exports = require('./lm');

//module.exports = require('./lm');
