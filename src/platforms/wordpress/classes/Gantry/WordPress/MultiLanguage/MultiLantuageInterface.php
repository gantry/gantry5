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
 * Interface MultiLantuageInterface
 * @package Gantry\WordPress\MultiLanguage
 */
interface MultiLantuageInterface
{
    /**
     * @return bool
     */
    public static function enabled();

    /**
     * @return string
     */
    public function getCurrentLanguage();

    /**
     * @return array
     */
    public function getLanguageOptions();
}
