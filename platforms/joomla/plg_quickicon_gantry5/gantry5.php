<?php
/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2019 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */
defined('_JEXEC') or die;

use Gantry\Component\Filesystem\Streams;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry5\Loader;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Language\Text;
use Joomla\Event\DispatcherInterface;


/**
 * Class plgQuickiconGantry5
 */
class plgQuickiconGantry5 extends CMSPlugin
{
    /**
     * plgQuickiconGantry5 constructor.
     * @param DispatcherInterface $subject
     * @param array $config
     */
    public function __construct(&$subject, $config = array())
    {
        // Do not load if Gantry libraries are not installed or initialised.
        if (!class_exists('Gantry5\Loader')) {
            return;
        }

        parent::__construct($subject, $config);

        // Always load language.
        $language = Factory::getLanguage();

        $language->load('com_gantry5.sys')
        || $language->load('com_gantry5.sys', JPATH_ADMINISTRATOR . '/components/com_gantry5');

        $this->loadLanguage('plg_quickicon_gantry5.sys');
    }

    /**
     * Display Gantry 5 backend icon
     *
     * @param string $context
     * @return array|null
     */
    public function onGetIcons($context)
    {
        $user = Factory::getUser();

        if ($context !== $this->params->get('context', 'mod_quickicon')
            || !$user->authorise('core.manage', 'com_gantry5')) {
            return null;
        }

        try {
            $updates = null;
            if ($user->authorise('core.manage', 'com_installer'))
            {
                // Initialise Gantry.
                Loader::setup();
                $gantry = Gantry::instance();

                /** @var Streams $streams */
                $streams = $gantry['streams'];
                $streams->register();

                /** @var Platform $platform */
                $platform = $gantry['platform'];
                $updates = $platform->updates();
            }
        } catch (Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage($e->getMessage(), 'warning');
            $updates = false;
        }

        $quickicons = array(
            array(
                'link' => Route::_('index.php?option=com_gantry5'),
                'image' => 'eye',
                'text' => Text::_('COM_GANTRY5'),
                'group' => 'MOD_QUICKICON_EXTENSIONS',
                'access' => array('core.manage', 'com_gantry5')
            )
        );

        if ($updates === false) {
            // Disabled
            $quickicons[] = array(
                'link' => Route::_('index.php?option=com_gantry5'),
                'image' => 'eye',
                'text' => Text::_('PLG_QUICKICON_GANTRY5_UPDATES_DISABLED'),
                'group' => 'MOD_QUICKICON_MAINTENANCE'
            );

        } elseif ($updates) {
            // Has updates
            $quickicons[] = array(
                'link' => Route::_('index.php?option=com_installer&view=update'),
                'image' => 'download',
                'text' => Text::_('PLG_QUICKICON_GANTRY5_UPDATE_NOW'),
                'group' => 'MOD_QUICKICON_MAINTENANCE'
            );
        }

        return $quickicons;
    }
}
