"use strict";

var $ = require('elements');

var fieldValidation = function(field) {
    field = $(field);
    var _field = field[0],
        tag = field.tag(),
        type = field.type(),
        isValid = true;

    // only validate input, textarea, select
    if (!~['input', 'textarea', 'select'].indexOf(tag)) { return isValid; }

    // use native validation if available
    if (typeof _field.willValidate !== 'undefined') {
        if (tag == 'input' && (_field.type.toLowerCase() !== type || field.hasClass('custom-validation-field'))) {
            // type not supported or custom, fallback validation
            _field.setCustomValidity(validate(field) ? '' : 'The field value is invalid');
        }

        // native validity check
        _field.checkValidity();

    } else {
        _field.validity = _field.validity || {};
        _field.validity.valid = validate(field);
    }

    isValid = _field.validity.valid;

    return isValid;
};

var validate = function(field) {
    field = $(field);

    var isValid = true,
        value = field.value(),
        type = field.type(),
        isCheckbox = (type == 'checkbox' || type == 'radio'),
        disabled = field.attribute('disabled'),
        required = field.attribute('required'),
        minlength = field.attribute('minlength'),
        maxlength = field.attribute('maxlength'),
        min = field.attribute('min'),
        max = field.attribute('max'),
        pattern = field.attribute('pattern');

    // disabled fields should not be validated
    if (disabled) { return isValid; }

    // required
    isValid = isValid && (!required || (isCheckbox && field.checked()) || (!isCheckbox && value));

    // minlength / maxlength
    isValid = isValid && (isCheckbox || ((!minlength || value.length >= minlength) && (!maxlength || value.length <= maxlength)));

    // pattern
    if (isValid && pattern) {
        pattern = new RegExp(pattern);
        isValid = pattern.test(value);
    }

    // min / max
    if (isValid && (min !== null || max !== null)) {
        if (min !== null) {
            isValid = parseFloat(value) >= parseFloat(min);
        }

        if (max !== null) {
            isValid = parseFloat(value) <= parseFloat(max);
        }
    }

    return isValid;
};

module.exports = fieldValidation;
