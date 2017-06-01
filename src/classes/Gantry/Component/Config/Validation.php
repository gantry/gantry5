<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Config;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Data validation.
 *
 * @author RocketTheme
 * @license MIT
 */
class Validation
{
    /**
     * Validate value against a blueprint field definition.
     *
     * @param $value
     * @param array $field
     * @return array
     */
    public static function validate($value, array $field)
    {
        $messages = [];

        $validate = isset($field['validate']) ? (array) $field['validate'] : [];

        // If value isn't required, we will stop validation if empty value is given.
        if (empty($validate['required']) && ($value === null || $value === '')) {
            return $messages;
        }

        if (!isset($field['type'])) {
            $field['type'] = 'input.text';
        }

        // Special case for files, value is never empty and errors with code 4 instead.
        if (empty($validate['required']) && $field['type'] == 'input.file' && isset($value['error'])
                && ($value['error'] == UPLOAD_ERR_NO_FILE || in_array(UPLOAD_ERR_NO_FILE, $value['error']))) {
            return $messages;
        }

        // Validate type with fallback type text.
        $type = (string) isset($field['validate']['type']) ? $field['validate']['type'] : $field['type'];
        $method = 'type_'.strtr($type, '-.', '__');

        if (!method_exists(__CLASS__, $method)) {
            $method = 'type_Input_Text';
        }

        $name = ucfirst(isset($field['label']) ? $field['label'] : $field['name']);
        // TODO: translate
        $message = (string) isset($field['validate']['message'])
            ? sprintf($field['validate']['message'])
            : sprintf('Invalid input in field: ') . ' "' . $name . '"';

        $success = self::$method($value, $validate, $field);

        if (!$success) {
            $messages[$field['name']][] = $message;
        }

        // Check individual rules.
        foreach ($validate as $rule => $params) {
            $method = 'validate_' . ucfirst(strtr($rule, '-.', '__'));

            if (method_exists(__CLASS__, $method)) {
                $success = self::$method($value, $params);

                if (!$success) {
                    $messages[$field['name']][] = $message;
                }
            }
        }

        return $messages;
    }

    /**
     * Filter value against a blueprint field definition.
     *
     * @param  mixed  $value
     * @param  array  $field
     * @return mixed  Filtered value.
     */
    public static function filter($value, array $field)
    {
        $validate = isset($field['validate']) ? (array) $field['validate'] : [];

        // If value isn't required, we will return null if empty value is given.
        if (empty($validate['required']) && ($value === null || $value === '')) {
            return null;
        }

        if (!isset($field['type'])) {
            $field['type'] = 'input.text';
        }

        // Special case for files, value is never empty and errors with code 4 instead.
        if (empty($validate['required']) && $field['type'] == 'input.file' && isset($value['error'])
            && ($value['error'] == UPLOAD_ERR_NO_FILE || in_array(UPLOAD_ERR_NO_FILE, $value['error']))) {
            return null;
        }

        // Validate type with fallback type text.
        $type = (string) isset($field['validate']['type']) ? $field['validate']['type'] : $field['type'];
        $method = 'filter_' . ucfirst(strtr($type, '-', '_'));

        if (!method_exists(__CLASS__, $method)) {
            $method = 'filter_Input_Text';
        }

        return self::$method($value, $validate, $field);
    }

    /**
     * HTML5 input: text
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Text($value, array $params, array $field)
    {
        if (!is_string($value)) {
            return false;
        }

        if (isset($params['min']) && strlen($value) < $params['min']) {
            return false;
        }

        if (isset($params['max']) && strlen($value) > $params['max']) {
            return false;
        }

        $min = isset($params['min']) ? $params['min'] : 0;
        if (isset($params['step']) && (strlen($value) - $min) % $params['step'] == 0) {
            return false;
        }

        if ((!isset($params['multiline']) || !$params['multiline']) && preg_match('/\R/um', $value)) {
            return false;
        }

        return true;
    }

    protected static function filter_Input_Text($value, array $params, array $field)
    {
        return (string) $value;
    }

    protected static function filter_Input_CommaList($value, array $params, array $field)
    {
        return is_array($value) ? $value : preg_split('/\s*,\s*/', $value, -1, PREG_SPLIT_NO_EMPTY);
    }

    protected static function type_Input_CommaList($value, array $params, array $field)
    {
        return is_array($value) ? true : self::type_Input_Text($value, $params, $field);
    }

    /**
     * HTML5 input: textarea
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Textarea_Textarea($value, array $params, array $field)
    {
        if (!isset($params['multiline'])) {
            $params['multiline'] = true;
        }

        return self::type_Input_Text($value, $params, $field);
    }

    /**
     * HTML5 input: password
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Password($value, array $params, array $field)
    {
        return self::type_Input_Text($value, $params, $field);
    }

    /**
     * HTML5 input: hidden
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Hidden($value, array $params, array $field)
    {
        return self::type_Input_Text($value, $params, $field);
    }

    /**
     * Custom input: checkbox list
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Checkboxes_Checkboxes($value, array $params, array $field)
    {
        return self::typeArray((array) $value, $params, $field);
    }

    protected static function filter_Checkboxes_Checkboxes($value, array $params, array $field)
    {
        return self::filterArray($value, $params, $field);
    }

    /**
     * HTML5 input: checkbox
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Checkbox($value, array $params, array $field)
    {
        $value = (string) $value;

        if (!isset($field['value'])) {
            $field['value'] = 1;
        }
        if ($value && $value != $field['value']) {
            return false;
        }

        return true;
    }

    /**
     * HTML5 input: radio
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Radio($value, array $params, array $field)
    {
        return self::typeArray((array) $value, $params, $field);
    }

    /**
     * Custom input: toggle
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Toggle_Toggle($value, array $params, array $field)
    {
        return self::typeArray((array) $value, $params, $field);
    }

    /**
     * Custom input: file
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_File($value, array $params, array $field)
    {
        return self::typeArray((array) $value, $params, $field);
    }

    protected static function filter_Input_File($value, array $params, array $field)
    {
        if (isset($field['multiple']) && $field['multiple'] === true) {
            return (array) $value;
        }

        if (is_array($value)) {
            return reset($value);
        }

        return $value;
    }

    /**
     * HTML5 input: select
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Select_Select($value, array $params, array $field)
    {
        return self::typeArray((array) $value, $params, $field);
    }

    /**
     * HTML5 input: number
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */

    public static function type_Input_Number($value, array $params, array $field)
    {
        if (!is_numeric($value)) {
            return false;
        }

        if (isset($params['min']) && $value < $params['min']) {
            return false;
        }

        if (isset($params['max']) && $value > $params['max']) {
            return false;
        }

        $min = isset($params['min']) ? $params['min'] : 0;
        if (isset($params['step']) && fmod($value - $min, $params['step']) == 0) {
            return false;
        }

        return true;
    }

    protected static function filter_Input_Number($value, array $params, array $field)
    {
        return (int) $value;
    }

    protected static function filter_Input_DateTime($value, array $params, array $field)
    {
        $converted = new \DateTime($value);
        return $converted->format('Y-m-d H:i:s');
    }


    /**
     * HTML5 input: range
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Range($value, array $params, array $field)
    {
        return self::type_Input_Number($value, $params, $field);
    }

    protected static function filter_Input_Range($value, array $params, array $field)
    {
        return self::filter_Input_Number($value, $params, $field);
    }

    /**
     * HTML5 input: color
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Color($value, array $params, array $field)
    {
        return preg_match('/^\#[0-9a-fA-F]{3}[0-9a-fA-F]{3}?$/u', $value);
    }

    /**
     * HTML5 input: email
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Email($value, array $params, array $field)
    {
        return self::type_Input_Text($value, $params, $field) && filter_var($value, FILTER_VALIDATE_EMAIL);
    }

    /**
     * HTML5 input: url
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */

    public static function type_Input_Url($value, array $params, array $field)
    {
        return self::type_Input_Text($value, $params, $field) && filter_var($value, FILTER_VALIDATE_URL);
    }

    /**
     * HTML5 input: datetime
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Datetime($value, array $params, array $field)
    {
        if ($value instanceof \DateTime) {
            return true;
        } elseif (!is_string($value)) {
            return false;
        } elseif (!isset($params['format'])) {
            return false !== strtotime($value);
        }

        $dateFromFormat = \DateTime::createFromFormat($params['format'], $value);

        return $dateFromFormat && $value === date($params['format'], $dateFromFormat->getTimestamp());
    }

    /**
     * HTML5 input: datetime-local
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_DatetimeLocal($value, array $params, array $field)
    {
        return self::type_Input_Datetime($value, $params, $field);
    }

    /**
     * HTML5 input: date
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Date($value, array $params, array $field)
    {
        $params = array($params);
        if (!isset($params['format'])) {
            $params['format'] = 'Y-m-d';
        }
        return self::type_Input_Datetime($value, $params, $field);
    }

    /**
     * HTML5 input: time
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Time($value, array $params, array $field)
    {
        $params = array($params);
        if (!isset($params['format'])) {
            $params['format'] = 'H:i';
        }
        return self::type_Input_Datetime($value, $params, $field);
    }

    /**
     * HTML5 input: month
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Month($value, array $params, array $field)
    {
        $params = array($params);
        if (!isset($params['format'])) {
            $params['format'] = 'Y-m';
        }
        return self::type_Input_Datetime($value, $params, $field);
    }

    /**
     * HTML5 input: week
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Input_Week($value, array $params, array $field)
    {
        if (!isset($params['format']) && !preg_match('/^\d{4}-W\d{2}$/u', $value)) {
            return false;
        }
        return self::type_Input_Datetime($value, $params, $field);
    }

    /**
     * Custom input: array
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function typeArray($value, array $params, array $field)
    {
        if (!is_array($value)) {
            return false;
        }

        if (isset($field['multiple'])) {
            if (isset($params['min']) && count($value) < $params['min']) {
                return false;
            }

            if (isset($params['max']) && count($value) > $params['max']) {
                return false;
            }

            $min = isset($params['min']) ? $params['min'] : 0;
            if (isset($params['step']) && (count($value) - $min) % $params['step'] == 0) {
                return false;
            }
        }

        $options = isset($field['options']) ? array_keys($field['options']) : array();
        $values = isset($field['use']) && $field['use'] == 'keys' ? array_keys($value) : $value;
        if ($options && array_diff($values, $options)) {
            return false;
        }

        return true;
    }

    protected static function filterArray($value, $params, $field)
    {
        $values = (array) $value;
        $options = isset($field['options']) ? array_keys($field['options']) : array();
        $multi = isset($field['multiple']) ? $field['multiple'] : false;

        if (count($values) == 1 && isset($values[0]) && $values[0] == '') {
            return null;
        }

        if ($options) {
            $useKey = isset($field['use']) && $field['use'] == 'keys';
            foreach ($values as $key => $value) {
                $values[$key] = $useKey ? (bool) $value : $value;
            }
        }

        if ($multi) {
            foreach ($values as $key => $value) {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $values[$key] =  array_map('trim', explode(',', $value));
            }
        }

        return $values;
    }

    public static function type_Input_Yaml($value, $params)
    {
        try {
            Yaml::parse($value);
            return true;
        } catch (ParseException $e) {
            return false;
        }
    }

    public static function filter_Input_Yaml($value, $params)
    {
        try {
            return (array) Yaml::parse($value);
        } catch (ParseException $e) {
            return null;
        }
    }

    /**
     * Custom input: ignore (will not validate)
     *
     * @param  mixed  $value   Value to be validated.
     * @param  array  $params  Validation parameters.
     * @param  array  $field   Blueprint for the field.
     * @return bool   True if validation succeeded.
     */
    public static function type_Novalidate($value, array $params, array $field)
    {
        return true;
    }

    public static function filter_Novalidate($value, array $params, array $field)
    {
        return $value;
    }

    // HTML5 attributes (min, max and range are handled inside the types)

    public static function validate_Required($value, $params)
    {
        if (is_scalar($value)) {
            return (bool) $params !== true || $value !== '';
        } else {
            return (bool) $params !== true || !empty($value);
        }
    }

    public static function validate_Pattern($value, $params)
    {
        return (bool) preg_match("`^{$params}$`u", $value);
    }


    // Internal types

    public static function validate_Alpha($value, $params)
    {
        return ctype_alpha($value);
    }

    public static function validate_Alnum($value, $params)
    {
        return ctype_alnum($value);
    }

    public static function type_Bool($value, $params)
    {
        return is_bool($value) || $value == 1 || $value == 0;
    }

    public static function validate_Bool($value, $params)
    {
        return is_bool($value) || $value == 1 || $value == 0;
    }

    protected static function filter_Bool($value, $params)
    {
        return (bool) $value;
    }

    public static function validate_Digit($value, $params)
    {
        return ctype_digit($value);
    }

    public static function validate_Float($value, $params)
    {
        return is_float(filter_var($value, FILTER_VALIDATE_FLOAT));
    }

    protected static function filter_Float($value, $params)
    {
        return (float) $value;
    }

    public static function validate_Hex($value, $params)
    {
        return ctype_xdigit($value);
    }

    public static function validate_Int($value, $params)
    {
        return is_numeric($value) && (int) $value == $value;
    }

    protected static function filter_Int($value, $params)
    {
        return (int) $value;
    }

    public static function validate_Array($value, $params)
    {
        return is_array($value)
        || ($value instanceof \ArrayAccess
            && $value instanceof \Traversable
            && $value instanceof \Countable);
    }

    public static function validate_Json($value, $params)
    {
        return (bool) (@json_decode($value));
    }
}
