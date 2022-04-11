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
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Table;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;

/**
 * Gantry 5 package installer script.
 */
class Pkg_Gantry5InstallerScript
{
    /**
     * List of supported versions. Newest version first!
     * @var array
     */
    protected $versions = array(
        'PHP' => array (
            '5.6' => '5.6.20',
            '0' => '7.4.28' // Preferred version
        ),
        'Joomla!' => array (
            '3.9' => '3.9.0',
            '0' => '3.10.8' // Preferred version
        )
    );
    /**
     * List of required PHP extensions.
     * @var array
     */
    protected $extensions = array('pcre');

    /**
     * @param InstallerAdapter $parent
     * @return bool
     */
    public function install($parent)
    {
        return true;
    }

    /**
     * @param InstallerAdapter $parent
     * @return bool
     */
    public function discover_install($parent)
    {
        return self::install($parent);
    }

    /**
     * @param InstallerAdapter $parent
     * @return bool
     */
    public function update($parent)
    {
        return self::install($parent);
    }

    /**
     * @param InstallerAdapter $parent
     * @return bool
     */
    public function uninstall($parent)
    {
        // Hack.. Joomla really doesn't give any information from the extension that's being uninstalled..
        $manifestFile = JPATH_MANIFESTS . '/packages/pkg_gantry5.xml';
        if (is_file($manifestFile)) {
            $manifest = simplexml_load_file($manifestFile);
            $this->prepareExtensions($manifest, 0);
        }

        // Clear cached files.
        if (is_dir(JPATH_CACHE . '/gantry5')) {
            Folder::delete(JPATH_CACHE . '/gantry5');
        }
        if (is_dir(JPATH_SITE . '/cache/gantry5')) {
            Folder::delete(JPATH_SITE . '/cache/gantry5');
        }

        return true;
    }

    /**
     * @param string $type
     * @param InstallerAdapter $parent
     * @return bool
     */
    public function preflight($type, $parent)
    {
        $manifest = $parent->getManifest();

        if ($type !== 'uninstall') {
            // Prevent installation if requirements are not met.
            $errors = $this->checkRequirements($manifest->version);
            if ($errors) {
                /** @var CMSApplication $app */
                $app = Factory::getApplication();

                foreach ($errors as $error) {
                    $app->enqueueMessage($error, 'error');
                }
                return false;
            }
        }

        // Disable and unlock existing extensions to prevent fatal errors (in the site).
        $this->prepareExtensions($manifest, 0);

        return true;
    }

    /**
     * @param string $type
     * @param InstallerAdapter $parent
     * @return bool
     */
    public function postflight($type, $parent)
    {
        // Clear Joomla system cache.
        /** @var JCache|JCacheController $cache */
        $cache = Factory::getCache();
        $cache->clean('_system');

        // Clear Gantry5 cache.
        $path = Factory::getConfig()->get('cache_path', JPATH_SITE . '/cache') . '/gantry5';
        if (is_dir($path)) {
            Folder::delete($path);
        }

        // Make sure that PHP has the latest data of the files.
        clearstatcache();

        // Remove all compiled files from opcode cache.
        if (function_exists('opcache_reset')) {
            @opcache_reset();
        } elseif (function_exists('apc_clear_cache')) {
            @apc_clear_cache();
        }

        if ($type === 'uninstall') {
            return true;
        }

        $manifest = $parent->getManifest();

        // Enable and lock extensions to prevent uninstalling them individually.
        $this->prepareExtensions($manifest, 1);

        // Make sure that all file formats used by Gantry 5 are editable from template manager.
        $this->adjustTemplateSettings();

        // Install sample data on first install.
        if (in_array($type, array('install', 'discover_install'))) {
            $this->renderSplash('install', $manifest);
        } else {
            $this->renderSplash('update', $manifest);
        }

        return true;
    }

    // Internal functions

        /**
     * @param string $template
     * @param \SimpleXMLElement $manifest
     */
    public function renderSplash($template, $manifest)
    {
        // Define variables for the template file.
        $name = Text::sprintf($manifest->name);
        $version = $manifest->version;
        $date = $manifest->creationDate;
        $edit_url = Route::_('index.php?option=com_gantry5', false);

        include JPATH_ADMINISTRATOR . "/components/com_gantry5/install/templates/{$template}.php";
    }

    /**
     * @param $manifest
     * @param int $state
     */
    protected function prepareExtensions($manifest, $state = 1)
    {
        foreach ($manifest->files->children() as $file) {
            $attributes = $file->attributes();

            $search = array('type' => (string) $attributes->type, 'element' => (string) $attributes->id);

            $clientName = (string) $attributes->client;
            if (!empty($clientName)) {
                $client = JApplicationHelper::getClientInfo($clientName, true);
                $search +=  array('client_id' => $client->id);
            }

            $group = (string) $attributes->group;
            if (!empty($group)) {
                $search +=  array('folder' => $group);
            }

            $extension = Table::getInstance('extension');

            if (!$extension->load($search)) {
                continue;
            }

            $extension->protected = 0;

            if (isset($attributes->enabled)) {
                $extension->enabled = $state ? (int) $attributes->enabled : 0;
            }

            $extension->store();
        }
    }

    protected function adjustTemplateSettings()
    {
        $extension = Table::getInstance('extension');
        if (!$extension->load(array('type' => 'component', 'element' => 'com_templates'))) {
            return;
        }

        $params = new Registry($extension->params);
        $params->set('source_formats', $this->addParam($params->get('source_formats'), array('scss', 'yaml', 'twig')));
        $params->set('font_formats', $this->addParam($params->get('font_formats'), array('eot', 'svg')));

        $extension->params = $params->toString();
        $extension->store();
    }

    /**
     * @param string $string
     * @param array $options
     * @return string
     */
    protected function addParam($string, array $options)
    {
        $items = array_flip(explode(',', $string)) + array_flip($options);

        return implode(',', array_keys($items));
    }

    /**
     * @param string $gantryVersion
     * @return array
     */
    protected function checkRequirements($gantryVersion)
    {
        $results = array();
        $this->checkVersion($results, 'PHP', PHP_VERSION);
        $this->checkVersion($results, 'Joomla!', JVERSION);
        $this->checkExtensions($results, $this->extensions);

        return $results;
    }

    /**
     * @param array $results
     * @param string $name
     * @param string $version
     */
    protected function checkVersion(array &$results, $name, $version)
    {
        $major = $minor = 0;
        foreach ($this->versions[$name] as $major => $minor) {
            if (!$major || version_compare($version, $major, '<')) {
                continue;
            }

            if (version_compare($version, $minor, '>=')) {
                return;
            }
            break;
        }

        if (!$major) {
            $minor = reset($this->versions[$name]);
        }

        $recommended = end($this->versions[$name]);

        if (version_compare($recommended, $minor, '>')) {
            $results[] = sprintf(
                '%s %s is not supported. Minimum required version is %s %s, but it is highly recommended to use %s %s or later version.',
                $name,
                $version,
                $name,
                $minor,
                $name,
                $recommended
            );
        } else {
            $results[] = sprintf(
                '%s %s is not supported. Please update to %s %s or later version.',
                $name,
                $version,
                $name,
                $minor
            );
        }
    }

    /**
     * @param array $results
     * @param array $extensions
     */
    protected function checkExtensions(array &$results, $extensions)
    {
        foreach ($extensions as $name) {
            if (!extension_loaded($name)) {
                $results[] = sprintf("Required PHP extension '%s' is missing. Please install it into your system.", $name);
            }
        }
    }
}
