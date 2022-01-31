<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Gantry\Framework\Gantry;
use Gantry5\Loader;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

/**
 * @param Gantry|null $gantry
 * @return mixed|string
 */
$gantry_theme_name = static function (Gantry $gantry = null) {
    // First attempt to look up the theme name from Gantry.
    if ($gantry && isset($gantry['theme.name'])) {
        return $gantry['theme.name'];
    }

    // Joomla site also defines template name.
    $app = Factory::getApplication();
    if ($app->isClient('site')) {
        return $app->getTemplate();
    }

    // Finally fall back to folder name.
    $template = basename(dirname(__DIR__));
    if ($template === 'joomla') {
        // Git install.
        $template = basename(dirname(__DIR__, 2));
    }

    return $template;
};

try
{
    $gantry = null;
    if (!class_exists('Gantry5\Loader')) {
        throw new RuntimeException(Text::_('GANTRY5_THEME_INSTALL_GANTRY'));
    }

    // Setup Gantry 5 Framework or throw exception.
    Loader::setup();

    // Get Gantry instance and return it.
    $gantry = Gantry::instance();

    // Initialize the template if not done already.
    if (!isset($gantry['theme.name'])) {
        $gantry['theme.path'] = dirname(__DIR__);
        $gantry['theme.name'] = $gantry_theme_name($gantry);
    }

    // Only a single template can be loaded at any time.
    if (!isset($gantry['theme']) && file_exists(__DIR__ . '/theme.php')) {
        include_once __DIR__ . '/theme.php';
    }

    return $gantry;
}
catch (Exception $e)
{
    // Oops, something went wrong!
    header('HTTP/1.0 500 Internal Server Error');

    $template = $gantry_theme_name($gantry);
    $message = Text::sprintf('GANTRY5_THEME_LOADING_FAILED', $template, $e->getMessage());

    echo <<<html
<html>
    <head>
        <title>500 Internal Server Error</title>
        <style>
        .alert {
            padding: 8px 35px 8px 14px;
            margin-bottom: 18px;
            text-shadow: 0px 1px 0px rgba(255, 255, 255, 0.5);
            background-color: #F2DEDE;
            border-color: #EED3D7;
            color: #B94A48;
            border-radius: 4px;
            font-size: 1.2em;
        }
        </style>
    </head>
    <body>
        <div class="alert">{$message}</div>
    </body>
</html>
html;

    die();
}
