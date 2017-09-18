<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\WordPress\MultiLanguage;

use Gantry\Framework\Gantry;

class WordPress implements MultiLantuageInterface
{
    public static function enabled()
    {
        return true;
    }

    public function getCurrentLanguage()
    {
        $code = Gantry::instance()['site']->language;
        $code = explode('-', $code, 2);

        return $code[0];
    }

    public function getLanguageOptions()
    {
        $code = $this->getCurrentLanguage();

        return [['name' => $code, 'label' => Gantry::instance()['site']->language]];
    }
}
