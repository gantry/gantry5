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
        $items = [['name' => 'en', 'label' => 'en_US']];

        $languages = get_available_languages();
        foreach($languages as $lang) {
            $code = explode('_', $lang, 2);
            $items[] = ['name' => $code[0], 'label' => $lang];
        }

        return $items;
    }
}
