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

class G5_BaseInstallerScript
{
    public $requiredGantryVersion = '5.3.2';

    /**
     * @param string $type
     * @param object $parent
     * @return bool
     * @throws Exception
     */
    public function preflight($type, $parent)
    {
        if ($type == 'uninstall') {
            return true;
        }

        $manifest = $parent->getManifest();
        $name = JText::_($manifest->name);

        // Prevent installation if Gantry 5 isn't enabled or is too old for this template.
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

    /**
     * @param string $type
     * @param object $parent
     * @throws Exception
     */
    public function postflight($type, $parent)
    {
        $installer = new Gantry\Joomla\TemplateInstaller($parent);
        $installer->initialize();

        // Install sample data on first install.
        if (in_array($type, array('install', 'discover_install'))) {
            try {
                $installer->installDefaults();

                echo $installer->render('install.html.twig');

            } catch (Exception $e) {
                $app = JFactory::getApplication();
                $app->enqueueMessage(JText::sprintf($e->getMessage()), 'error');
            }
        } else {
            echo $installer->render('update.html.twig');
        }

        $installer->finalize();
    }

    /**
     * Called by TemplateInstaller to customize post-installation.
     *
     * @param \Gantry\Joomla\TemplateInstaller $installer
     */
    public function installDefaults(Gantry\Joomla\TemplateInstaller $installer)
    {
        // Create default outlines etc.
        $installer->createDefaults();
    }

    /**
     * Called by TemplateInstaller to customize sample data creation.
     *
     * @param \Gantry\Joomla\TemplateInstaller $installer
     */
    public function installSampleData(Gantry\Joomla\TemplateInstaller $installer)
    {
        // Create sample data.
        $installer->createSampleData();
    }
}
