<?php

/**
 * @package   Gantry 6
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Plugin\Gantry5\Preset\Extension;

use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Event\Event;
use Joomla\Event\SubscriberInterface;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Class Preset
 */
final class Preset extends CMSPlugin implements SubscriberInterface
{
    use DatabaseAwareTrait;

    /**
     * @inheritDoc
     */
    protected $allowLegacyListeners = false;

    /**
     * Load the language file on instantiation.
     *
     * @var boolean
     */
    protected $autoloadLanguage = true;

    /**
     * Returns an array of events this subscriber will listen to.
     *
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'onGantry5ThemeInit' => 'onGantry5ThemeInit',
            'onGantry5UpdateCss' => 'onGantry5UpdateCss',
        ];
    }

    /**
     * The `onGantry5ThemeInit` method handle.
     *
     * @param   Event  $event  The `onGantry5ThemeInit` event.
     *
     * @return  void
     */
    public function onGantry5ThemeInit(Event $event)
    {
        $theme = $event->getArgument('theme');

        if ($this->getApplication()->isClient('site')) {
            $input = $this->getApplication()->getInput();

            $cookie    = \md5($theme->name);
            $presetVar = $this->params->get('preset', 'presets');
            $resetVar  = $this->params->get('reset', 'reset-settings');
            $preset    = $input->getCmd($resetVar) !== null ? false : $preset = $input->getCmd($presetVar);

            if ($preset !== null) {
                if ($preset === false) {
                    $this->updateCookie($cookie, false, \time() - 42000);
                } else {
                    $this->updateCookie($cookie, $preset, 0);
                }
            } else {
                $preset = $input->cookie->getString($cookie);
            }

            $theme->setPreset($preset);
        }
    }

    /**
     * The `onGantry5UpdateCss` method handle.
     *
     * @param   Event  $event  The `onGantry5UpdateCss` event.
     *
     * @return  void
     */
    public function onGantry5UpdateCss(Event $event)
    {
        $theme = $event->getArgument('theme');

        $cookie = \md5($theme->name);

        $this->updateCookie($cookie, false, \time() - 42000);
    }

    /**
     * @param string $name
     * @param string $value
     * @param int $expire
     */
    protected function updateCookie($name, $value, $expire = 0)
    {
        $path   = $this->getApplication()->get('cookie_path', '/');
        $domain = $this->getApplication()->get('cookie_domain');

        $input = $this->getApplication()->getInput();
        $input->cookie->set($name, $value, $expire, $path, $domain);
    }
}
