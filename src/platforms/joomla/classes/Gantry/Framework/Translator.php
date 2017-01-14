<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Translator\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    public function translate($string)
    {
        if (func_num_args() == 1) {
            return \JText::_($string);
        }

        $args = func_get_args();
        return call_user_func_array(['JText', 'sprintf'], $args);
    }
}
