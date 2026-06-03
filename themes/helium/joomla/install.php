<?php

/**
 * @package   Gantry 5 Theme
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework)
 * @currentDeveloper  Tiger12, LLC
 * @copyright Copyright (C) 2007 - 2022 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

defined('_JEXEC') or die;

use Gantry\Framework\Gantry;
use Gantry\Framework\ThemeInstaller;
use Gantry5\Loader;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\TemplateAdapter;
use Joomla\CMS\Language\Text;
use Joomla\Filesystem\Folder;

/**
 * Class G5_HeliumInstallerScript
 */
class G5_HeliumInstallerScript
{
    /** @var string */
    public $requiredGantryVersion = '5.5';

    /**
     * @param string $type
     * @param object $parent
     * @return bool
     * @throws Exception
     */
    public function preflight($type, $parent)
    {
        if ($type === 'uninstall') {
            return true;
        }

        $manifest = $parent->getManifest();
        $name = Text::_($manifest->name);

        // Prevent installation if Gantry 5 isn't enabled or is too old for this template.
        try {
            if (!class_exists('Gantry5\Loader')) {
                throw new RuntimeException(sprintf('Please install Gantry 5 Framework before installing %s template!', $name));
            }

            Loader::setup();

            $gantry = Gantry::instance();

            if (!method_exists($gantry, 'isCompatible') || !$gantry->isCompatible($this->requiredGantryVersion)) {
                throw new \RuntimeException(sprintf('Please upgrade Gantry 5 Framework to v%s (or later) before installing %s template!', strtoupper($this->requiredGantryVersion), $name));
            }

        } catch (Exception $e) {
            $app = Factory::getApplication();
            $app->enqueueMessage(Text::sprintf($e->getMessage()), 'error');

            return false;
        }

        return true;
    }

    /**
     * @param string $type
     * @param TemplateAdapter $parent
     * @return bool
     * @throws Exception
     */
    public function postflight($type, $parent)
    {
        if ($type === 'uninstall') {
            return true;
        }

        $app = Factory::getApplication();
        $manifest = $parent->getManifest();
        $template = $parent->getName();

        // Auto-cleanup duplicates (old templates without proper element/version)
        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('*')
            ->from('#__extensions')
            ->where($db->quoteName('name') . ' = ' . $db->quote((string) $manifest->name))
            ->where($db->quoteName('type') . ' = ' . $db->quote('template'))
            ->where($db->quoteName('client_id') . ' = 0')
            ->where($db->quoteName('element') . ' != ' . $db->quote($template));
        $oldTemplates = $db->setQuery($query)->loadObjectList();

        foreach ($oldTemplates as $old) {
            if (version_compare((string) $old->version, (string) $manifest->version, '<')) {
                $installer = new \Joomla\CMS\Installer\Installer($this);
                $installer->uninstall('template', $old->extension_id);
                $app->enqueueMessage("Removed old duplicate: {$old->name} v{$old->version}", 'info');
            }
        }
        // Delete previous jQuery overrides, those just break things.
        $search = JPATH_ROOT . "/templates/{$template}/js/jui";
        if (Folder::exists($search)) {
            Folder::delete($search);
        }

        $installer = new ThemeInstaller($parent);
        $installer->initialize();

        // Install sample data on first install.
        if (in_array($type, array('install', 'discover_install'))) {
            try {
                $installer->installDefaults();

                echo $installer->render('install.html.twig');

            } catch (Exception $e) {
                $app = Factory::getApplication();
                $app->enqueueMessage(Text::sprintf($e->getMessage()), 'error');
            }
        } else {
            echo $installer->render('update.html.twig');
        }

        $installer->finalize();

        return true;
    }

    /**
     * Called by TemplateInstaller to customize post-installation.
     *
     * @param ThemeInstaller $installer
     * @return void
     */
    public function installDefaults(ThemeInstaller $installer)
    {
        // Create default outlines etc.
        $installer->createDefaults();
    }

    /**
     * Called by TemplateInstaller to customize sample data creation.
     *
     * @param ThemeInstaller $installer
     * @return void
     */
    public function installSampleData(ThemeInstaller $installer)
    {
        // Create sample data.
        $installer->createSampleData();
    }
}
