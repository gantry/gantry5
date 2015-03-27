"use strict";

var Modal = require('./modal'),
    Selectize = require('./selectize');

module.exports = {
    modal: new Modal(),
    togglers: require('./togglers'),
    selectize: Selectize,
    toastr: require('./toastr')
};
