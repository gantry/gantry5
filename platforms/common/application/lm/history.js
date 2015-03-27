var prime      = require('prime'),
    Emitter    = require('prime/emitter'),
    slice      = require('mout/array/slice'),
    merge      = require('mout/object/merge'),
    deepEquals = require('mout/lang/deepEquals');

var History = new prime({

    inherits: Emitter,

    constructor: function(session) {
        this.index = 0;
        session = merge({}, session);
        this.setSession(session);
    },

    undo: function() {
        if (!this.index) return;
        this.index--;

        var session = this.get();
        this.emit('undo', session, this.index);
        return session;
    },

    redo: function() {
        if (this.index == this.session.length - 1) return;
        this.index++;

        var session = this.get();
        this.emit('redo', session, this.index);
        return session;
    },

    reset: function() {
        this.index = 0;

        var session = this.get();
        this.emit('reset', session, this.index);
        return session;
    },

    push: function(session) {
        session = merge({}, session);
        var sliced = this.index < this.session.length - 1;
        if (this.index < this.session.length - 1) this.session = slice(this.session, 0, -(this.session.length - 1 - this.index));
        session = {
            time: +(new Date()),
            data: session
        };

        if (this.equals(session.data)) { return session; }

        this.session.push(session);
        this.index = this.session.length - 1;

        this.emit('push', session, this.index, sliced);
        return session;
    },

    get: function(index) {
        return this.session[index || this.index] || false;
    },

    equals: function(session, compare) {
        if (!compare) { compare = this.get().data; }

        return deepEquals(session, compare);
    },

    setSession: function(session) {
        session = !session ? []
            : [{
            time: +(new Date()),
            data: merge({}, session)
        }];

        this.session = session;
        this.index = 0;
        return this.session;
    },

    import: function() {},
    export: function() {}
});

module.exports = History;
