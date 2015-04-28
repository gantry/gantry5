<?php
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
                $cookie_path   = $app->get('cookie_path', '/');
                $cookie_domain = $app->get('cookie_domain');

                if ($preset === false) {
                    // Remove the cookie.
                    $input->cookie->set($cookie, false, time() - 42000, $cookie_path, $cookie_domain);
                } else {
                    // Create the cookie.
                    $input->cookie->set($cookie, $preset, 0, $cookie_path, $cookie_domain);
                }
            } else {
                $preset = $input->cookie->getString($cookie);
            }

            $theme->setPreset($preset);
        }
    }
}
