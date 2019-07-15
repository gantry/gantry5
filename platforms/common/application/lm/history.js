var prime      = require('prime'),
    Emitter    = require('prime/emitter'),
    slice      = require('mout/array/slice'),
    merge      = require('mout/object/merge'),
    deepEquals = require('mout/lang/deepEquals'),
    deepDiff   = require('deep-diff').diff;

var History = new prime({

    inherits: Emitter,

    constructor: function(session, preset) {
        this.index = 0;
        session = merge({}, session);
        preset = merge({}, preset);
        this.setSession(session, preset);
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

    push: function(session, preset) {
        session = merge({}, session);
        preset = merge({}, preset);
        var sliced = this.index < this.session.length - 1;
        if (this.index < this.session.length - 1) this.session = slice(this.session, 0, -(this.session.length - 1 - this.index));
        session = {
            time: +(new Date()),
            data: session,
            preset: preset
        };

        if (this.equals(session.data)) { return session; }

        this.session.push(session);
        this.index = this.session.length - 1;

        this.emit('push', session, this.index, sliced);
        return session;
    },

    get: function(index) {
        return this.session[typeof index !== 'undefined' ? index : this.index] || false;
    },

    equals: function(session, compare) {
        if (!compare) { compare = this.get().data; }

        return deepEquals(session, compare);
    },

    diff: function(obj1, obj2) {
        if (!obj1 && !obj2 && this.session.length <= 1) { return 'Not enough sessions to diff'; }
        if (!obj2) { obj2 = this.get(); }
        if (!obj1) { obj1 = this.get(this.index - 1); }

        return deepDiff(obj1, obj2);
    },

    setSession: function(session, preset) {
        session = !session ? []
            : [{
            time: +(new Date()),
            data: merge({}, session),
            preset: preset
        }];

        this.session = session;
        this.index = 0;
        return this.session;
    },

    import: function() {},
    export: function() {}
});

module.exports = History;
