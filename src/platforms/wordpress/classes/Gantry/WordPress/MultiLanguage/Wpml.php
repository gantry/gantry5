<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\WordPress\MultiLanguage;

/**
 * Class Wpml
 * @package Gantry\WordPress\MultiLanguage
 */
class Wpml extends WordPress
{
    /**
     * @return bool
     */
    public static function enabled()
    {
        return \apply_filters('wpml_current_language', null) !== null;
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
