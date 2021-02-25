<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Translator\Translator as BaseTranslator;

/**
 * Class Translator
 * @package Gantry\Framework
 */
class Translator extends BaseTranslator
{
    /**
     * @param string $string
     * @return string
     */
    public function translate($string)
    {
        static $textdomain;
        static $enginedomain;

        /** @var Theme $theme */
        $theme = Gantry::instance()['theme'];

        if (null === $textdomain) {
            $textdomain = $theme->details()->get('configuration.theme.textdomain', false);
            $enginedomain = $theme->details()->get('configuration.gantry.engine', 'nucleus');
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

        return sprintf(...$args);
    }
}
