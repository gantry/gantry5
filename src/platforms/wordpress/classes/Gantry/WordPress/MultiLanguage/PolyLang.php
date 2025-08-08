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
 * Class PolyLang
 * @package Gantry\WordPress\MultiLanguage
 */
class PolyLang extends WordPress
{
    /**
     * @return bool
     */
    public static function enabled()
    {
        return function_exists('pll_current_language') && function_exists('pll_the_languages');
    }

    /*
    public function getCurrentLanguage()
    {
        return pll_current_language('slug');
    }

    public function getLanguageOptions()
    {
        $languages = pll_the_languages(['raw' => 1]);

        $items = [];
        foreach ($languages as $item) {
            $items[] = [
                'name' => $item['slug'],
                'label' => $item['name'],
            ];
        }

        return $items;
    }
    */
}
