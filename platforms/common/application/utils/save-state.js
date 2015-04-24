var prime      = require('prime'),
    deepClone  = require('mout/lang/deepClone');

//var objectDiff = require('objectdiff');

var SaveState = new prime({

    constructor: function(session) {
        session = deepClone(session);
        this.setSession(session);
    },

    setSession: function(session) {
        session = !session ? {}
            : {
            time: +(new Date()),
            data: deepClone(session)
        };

        this.session = session;
        return this.session;
    },

    getTime: function() {
        return this.session.time;
    },

    getData: function() {
        return this.session.data;
    },

    getSession: function() {
        return this.session;
    },

    getDiff: function(data) {
        // Unsupported at this state
        return data;
        /*
        var diff = objectDiff.diff(this.getData(), data);
        return {
            diff: diff,
            xml: objectDiff.convertToXMLString(diff)
        };
        */
    }
});

module.exports = SaveState;
