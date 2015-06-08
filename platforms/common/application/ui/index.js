"use strict";

var Modal = require('./modal'),
    Selectize = require('./selectize');

module.exports = {
    modal: new Modal(),
    togglers: require('./togglers'),
    collapse: require('./collapse'),
    selectize: Selectize,
    toastr: require('./toastr')
};
