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

namespace Gantry\Framework;

use Gantry\Component\Translator\Translator as BaseTranslator;
use Grav\Common\Grav;
use Grav\Common\Language\Language;

/**
 * Class Translator
 * @package Gantry\Framework
 */
class Translator extends BaseTranslator
{
    public function __construct()
    {
        /** @var Language $language */
        $language = Grav::instance()['language'];

        if ($language->enabled()) {
            $this->active($language->getLanguage());
        }
    }
}
