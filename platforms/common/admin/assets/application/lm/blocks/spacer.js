var prime = require('prime'),
    Position  = require('./position');

var UID = 0;

var Spacer = new prime({
    inherits: Position,
    options: {
        type: 'spacer'
    },

    constructor: function(options){
        ++UID;
        Position.call(this, options);
    },

    getTitle: function(){
        return 'Spacer';
    }
});

module.exports = Spacer;
