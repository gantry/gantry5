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
        static $textdomain;
        static $enginedomain;

        if (null === $textdomain) {
            $textdomain = Gantry::instance()['theme']->details()->get('configuration.theme.textdomain', false);
            $enginedomain = Gantry::instance()['theme']->details()->get('configuration.gantry.engine', 'nucleus');
        }

        $translated = $textdomain ? \__($string, $textdomain) : $string;

        if ($translated === $string) {
            $translated = \__($string, $enginedomain);
        }

        if ($translated === $string) {
            $translated = \__($string, 'gantry5');
        }

        if ($translated === $string) {
            // Create WP compatible translation string.
            $string = parent::translate($string);

            $translated = $textdomain ? \__($string, $textdomain) : $string;
            if ($translated === $string) {
                $translated = \__($string, 'gantry5');
            }
        }

        if (\func_num_args() === 1) {
            return $translated;
        }

        $args = \func_get_args();
        $args[0] = $translated;

        return call_user_func_array('sprintf', $args);
    }
}
