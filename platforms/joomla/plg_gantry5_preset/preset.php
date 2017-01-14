<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

class plgGantry5Preset extends JPlugin
{
    public function __construct(&$subject, $config)
    {
        // Do not load if Gantry libraries are not installed or initialised.
        if (!class_exists('Gantry5\Loader')) return;

        parent::__construct($subject, $config);

        // Always load language.
        $lang = JFactory::getLanguage();
        $lang->load('com_gantry5.sys') || $lang->load('com_gantry5.sys', JPATH_ADMINISTRATOR . '/components/com_gantry5');
        $this->loadLanguage('plg_quickicon_gantry5.sys');
    }

    public function onGantry5ThemeInit($theme)
    {
        $app = JFactory::getApplication();

        if ($app->isSite()) {
            $input = $app->input;

            $cookie = md5($theme->name);
            $presetVar = $this->params->get('preset', 'presets');
            $resetVar = $this->params->get('reset', 'reset-settings');

            if ($input->getCmd($resetVar) !== null) {
                $preset = false;
            } else {
                $preset = $input->getCmd($presetVar);
            }


            if ($preset !== null) {
                if ($preset === false) {
                    // Invalidate the cookie.
                    $this->updateCookie($cookie, false, time() - 42000);
                } else {
                    // Update the cookie.
                    $this->updateCookie($cookie, $preset, 0);
                }
            } else {
                $preset = $input->cookie->getString($cookie);
            }

            $theme->setPreset($preset);
        }
    }

    public function onGantry5UpdateCss($theme)
    {
        $cookie = md5($theme->name);

        $this->updateCookie($cookie, false, time() - 42000);
    }

    protected function updateCookie($name, $value, $expire = 0)
    {
        $app = JFactory::getApplication();
        $path   = $app->get('cookie_path', '/');
        $domain = $app->get('cookie_domain');

        $input = $app->input;
        $input->cookie->set($name, $value, $expire, $path, $domain);
    }
}
