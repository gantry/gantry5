<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework) 
 * @currentDeveloper  Tiger12, LLC 
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Component\Stylesheet\Scss;

/**
 * Class Compiler
 * @package Gantry\Component\Stylesheet\Scss
 */
class Compiler extends \ScssPhp\ScssPhp\Compiler
{
    static public $currentDir;

    /**
     * Return the file path for an import url if it exists
     *
     * @param string      $url
     * @param string|null $currentDir
     *
     * @return string|null
     */
    public function findImport($url, $currentDir = null)
    {
        static::$currentDir = $currentDir;

        return parent::findImport($url, null);
    }
}
