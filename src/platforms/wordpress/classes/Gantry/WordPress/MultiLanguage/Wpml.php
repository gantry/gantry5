<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\WordPress\MultiLanguage;

class Wpml extends WordPress
{
    public static function enabled()
    {
        return apply_filters('wpml_current_language', null) !== null;
    }

    /*
    public function getCurrentLanguage()
    {
        return apply_filters('wpml_current_language', null);
    }

    public function getLanguageOptions()
    {
        $languages = (array) apply_filters('wpml_active_languages', null);

        $items = [];
        foreach ($languages as $language) {
            $items[] = [
                'name' => $language['language_code'],
                'label' => $language['translated_name'],
            ];
        }

        return $items;
    }
    */
}
