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

class plgQuickiconGantry5 extends JPlugin
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

    /**
     * Display Gantry 5 backend icon
     *
     * @param string $context
     * @return array|null
     */
    public function onGetIcons($context)
    {
        $user = JFactory::getUser();

        if ($context != $this->params->get('context', 'mod_quickicon')
            || !$user->authorise('core.manage', 'com_gantry5')) {
            return null;
        }

        try {
            $updates = null;
            if ($user->authorise('core.manage', 'com_installer'))
            {
                // Initialise Gantry.
                Gantry5\Loader::setup();
                $gantry = Gantry\Framework\Gantry::instance();
                $gantry['streams']->register();

                /** @var Gantry\Framework\Platform $platform */
                $platform = $gantry['platform'];
                $updates = $platform->updates();
            }
        } catch (Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage($e->getMessage(), 'warning');
            $updates = false;
        }

        $quickicons = array(
            array(
                'link' => JRoute::_('index.php?option=com_gantry5'),
                'image' => 'eye',
                'text' => JText::_('COM_GANTRY5'),
                'group' => 'MOD_QUICKICON_EXTENSIONS',
                'access' => array('core.manage', 'com_gantry5')
            )
        );

        if ($updates === false) {
            // Disabled
            $quickicons[] = array(
                'link' => JRoute::_('index.php?option=com_gantry5'),
                'image' => 'eye',
                'text' => JText::_('PLG_QUICKICON_GANTRY5_UPDATES_DISABLED'),
                'group' => 'MOD_QUICKICON_MAINTENANCE'
            );

        } elseif (!empty($updates)) {
            // Has updates
            $quickicons[] = array(
                'link' => JRoute::_('index.php?option=com_installer&view=update'),
                'image' => 'download',
                'text' => JText::_('PLG_QUICKICON_GANTRY5_UPDATE_NOW'),
                'group' => 'MOD_QUICKICON_MAINTENANCE'
            );
        }

        return $quickicons;
    }
}
