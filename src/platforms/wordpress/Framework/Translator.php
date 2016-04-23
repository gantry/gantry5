<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
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
        $string = \__($string, 'gantry5');

        if (func_num_args() === 1) {
            return $string;
        }

        $args = func_get_args();
        $args[0] = $string;

        return call_user_func_array('sprintf', $args);
    }
}
