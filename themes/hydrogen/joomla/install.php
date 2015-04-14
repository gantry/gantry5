<?php
defined('_JEXEC') or die;

class G5_HydrogenInstallerScript
{
    public function preflight($type, $parent)
    {
        if ($type == 'uninstall') {
            return true;
        }

        // Prevent installation if Gantry 5 isn't enabled.
        try {
            if (!class_exists('Gantry5\Loader')) {
                throw new RuntimeException('Please install Gantry 5 Framework!');
            }

            Gantry5\Loader::setup();

        } catch (Exception $e) {
            $app = JFactory::getApplication();
            $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');

            return false;
        }

        return true;
    }

    public function postflight($type, $parent)
    {
        if (in_array($type, array('install', 'discover_install')))
        {
            $installer = new Gantry\Joomla\TemplateInstaller($parent);

            $default = $installer->getDefaultStyle();
            switch ($default->template) {
                case 'beez3':
                case 'protostar':
                    $configuration = '_joomla_-_' . $default->template;
                    break;
                default:
                    $configuration = 'default';
            }

            // Update default style.
            $installer->updateStyle('JLIB_INSTALLER_DEFAULT_STYLE', array('configuration' => $configuration), 1);

            // Add second style for the main page and assign all home pages to it.
            $style = $installer->addStyle('TPL_G5_HYDROGEN_HOME_STYLE', array('configuration' => 'home'));
            $installer->assignHomeStyle($style);
        }
    }
}
