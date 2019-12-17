<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Framework;

use Gantry\Component\Translator\Translator as BaseTranslator;
use Grav\Common\Grav;
use Grav\Common\Language\Language;

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
