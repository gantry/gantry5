<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Event\DispatcherInterface;

// Quick check to prevent fatal error in unsupported Joomla admin.
if (!class_exists(CMSPlugin::class)) {
    return;
}

/**
 * Class plgGantry5Preset
 */
class plgGantry5Preset extends CMSPlugin
{
    /** @var CMSApplication */
    protected $app;

    /**
     * plgGantry5Preset constructor.
     * @param DispatcherInterface $subject
     * @param array $config
     */
    public function __construct(&$subject, $config = array())
    {
        // Do not load if Gantry libraries are not installed or initialised.
        if (!class_exists('Gantry5\Loader')) return;

        parent::__construct($subject, $config);

        // Get the application if not done by JPlugin. This may happen during upgrades from Joomla 2.5.
        if (!$this->app) {
            $this->app = Factory::getApplication();
        }

        // Always load language.
        $language = $this->app->getLanguage();

        $language->load('com_gantry5.sys')
        || $language->load('com_gantry5.sys', JPATH_ADMINISTRATOR . '/components/com_gantry5');

        $this->loadLanguage('plg_quickicon_gantry5.sys');
    }

    /**
     * @param object $theme
     * @throws Exception
     */
    public function onGantry5ThemeInit($theme)
    {
        if ($this->app->isClient('site')) {
            $input = $this->app->input;

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

    /**
     * @param object $theme
     */
    public function onGantry5UpdateCss($theme)
    {
        $cookie = md5($theme->name);

        $this->updateCookie($cookie, false, time() - 42000);
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expire
     * @throws Exception
     */
    protected function updateCookie($name, $value, $expire = 0)
    {
        $path   = $this->app->get('cookie_path', '/');
        $domain = $this->app->get('cookie_domain');

        $input = $this->app->input;
        $input->cookie->set($name, $value, $expire, $path, $domain);
    }
}
