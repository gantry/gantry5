<?php
/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

class G5_HydrogenInstallerScript
{
    public $requiredGantryVersion = '5.3.2';

    public function preflight($type, $parent)
    {
        if ($type == 'uninstall') {
            return true;
        }

        $manifest = $parent->getManifest();
        $name = JText::_($manifest->name);

        // Prevent installation if Gantry 5 isn't enabled.
        try {
            if (!class_exists('Gantry5\Loader')) {
                throw new RuntimeException(sprintf('Please install Gantry 5 Framework before installing %s template!', $name));
            }

            Gantry5\Loader::setup();

            $gantry = Gantry\Framework\Gantry::instance();

            if (!method_exists($gantry, 'isCompatible') || !$gantry->isCompatible($this->requiredGantryVersion)) {
              throw new \RuntimeException(sprintf('Please upgrade Gantry 5 Framework to v%s (or later) before installing %s template!', strtoupper($this->requiredGantryVersion), $name));
            }

        } catch (Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        $installer = new Gantry\Joomla\TemplateInstaller($parent);

        if (in_array($type, array('install', 'discover_install'))) {
            try {
                // Detect default style used in Joomla!
                $default = $installer->getDefaultStyle();
                switch ($default->template) {
                    case 'beez3':
                    case 'protostar':
                        $outline = '_joomla_-_' . $default->template;
                        break;
                    default:
                        $outline = 'default';
                }

                // Update default style.
                $installer->updateStyle('JLIB_INSTALLER_DEFAULT_STYLE', array('configuration' => $outline), 1);

                // Install menus and styles from demo data.
                $installer->installMenus();

            } catch (Exception $e) {
                $app = JFactory::getApplication();
                $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');
            }
        }

        $installer->cleanup();
    }
}
