<?php

/**
 * @package   Gantry 5 Theme
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Cache\CacheController;
use Joomla\CMS\Cache\CacheControllerFactoryInterface;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Table\Extension;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Folder;
use Joomla\Registry\Registry;

return new class () implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class (
                $container->get(AdministratorApplication::class),
                $container->get(DatabaseInterface::class)
            ) implements InstallerScriptInterface {
                /**
                 * @var DatabaseInterface
                 */
                private $db;

                /**
                 * @var AdministratorApplication
                 */
                private $app;

                /**
                 * List of supported versions. Newest version first!
                 * @var array
                 */
                private $versions = [
                    'PHP' => [
                        '8.1' => '8.1.0',
                        '0' => '8.3.0' // Preferred version
                    ],
                    'Joomla!' => [
                        '5.0' => '5.0.0',
                        '0' => '5.2.0' // Preferred version
                    ]
                ];

                /**
                 * List of required PHP extensions.
                 * @var array
                 */
                protected $extensions = ['pcre'];

                /**
                 * The extension name. This should be set in the installer script.
                 *
                 * @var    string
                 *
                 * @since  5.6.0
                 */
                protected $extension;

                public function __construct(AdministratorApplication $app, DatabaseInterface $db)
                {
                    $this->app = $app;
                    $this->db  = $db;
                }

                /**
                 * This method is called after extension is installed.
                 *
                 * @param   InstallerAdapter  $parent  Parent object calling object.
                 *
                 * @return  boolean True on success, false on failure.
                 *
                 * @since   5.6.0
                 */
                public function install(InstallerAdapter $parent): bool
                {
                    return true;
                }

                /**
                 * This method is called after extension is updated.
                 *
                 * @param   InstallerAdapter  $parent  Parent object calling object.
                 *
                 * @return  boolean True on success, false on failure.
                 *
                 * @since   5.6.0
                 */
                public function update(InstallerAdapter $parent): bool
                {
                    return true;
                }

                /**
                 * This method is called after extension is uninstalled.
                 *
                 * @param   InstallerAdapter  $parent  Parent object calling object.
                 *
                 * @return  boolean True on success, false on failure.
                 *
                 * @since   5.6.0
                 */
                public function uninstall(InstallerAdapter $parent): bool
                {
                    if (\is_dir(JPATH_CACHE . '/gantry5')) {
                        Folder::delete(JPATH_CACHE . '/gantry5');
                    }

                    if (\is_dir(JPATH_SITE . '/cache/gantry5')) {
                        Folder::delete(JPATH_SITE . '/cache/gantry5');
                    }

                    return true;
                }

                /**
                 * Runs right before any installation action.
                 *
                 * @param   string                           $type    Type of PostFlight action.
                 * @param   InstallerAdapter|PackageAdapter  $parent  Parent object calling object.
                 *
                 * @return  boolean True on success, false on failure.
                 *
                 * @since   5.6.0
                */
                public function preflight(string $type, InstallerAdapter $parent): bool
                {
                    if ($type === 'uninstall') {
                        return true;
                    }

                    $manifest = $parent->getManifest();

                    $errors = $this->checkRequirements($manifest->version);

                    if ($errors) {
                        foreach ($errors as $error) {
                            $this->app->enqueueMessage($error, 'error');
                        }

                        return false;
                    }

                    return true;
                }

                /**
                 * Runs right after any installation action.
                 *
                 * @param   string            $type    Type of PostFlight action. Possible values are:
                 * @param   InstallerAdapter  $parent  Parent object calling object.
                 *
                 * @return  boolean True on success, false on failure.
                 *
                 * @since   5.6.0
                 */
                public function postflight(string $type, InstallerAdapter $parent): bool
                {
                    // Clear Gantry5 cache.
                    $path = Factory::getApplication()->get('cache_path', JPATH_SITE . '/cache') . '/gantry5';

                    if (is_dir($path)) {
                        Folder::delete($path);
                    }

                    // Make sure that PHP has the latest data of the files.
                    \clearstatcache();

                    // Remove all compiled files from opcode cache.
                    if (\function_exists('opcache_reset')) {
                        @opcache_reset();
                    } elseif (\function_exists('apc_clear_cache')) {
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
                    if (\in_array($type, ['install', 'discover_install'])) {
                        $this->renderSplash('install', $manifest);
                    } else {
                        $this->renderSplash('update', $manifest);
                    }

                    return true;
                }

                /**
                 * @param string $template
                 * @param \SimpleXMLElement $manifest
                 */
                private function renderSplash($template, $manifest): void
                {
                    $name     = Text::sprintf($manifest->name);
                    $version  = $manifest->version;
                    $date     = $manifest->creationDate;
                    $edit_url = Route::_('index.php?option=com_gantry5', false);

                    include JPATH_ADMINISTRATOR . "/components/com_gantry5/install/templates/{$template}.php";
                }

                private function adjustTemplateSettings()
                {
                    $extension = new Extension($this->db);

                    if (!$extension->load(['type' => 'component', 'element' => 'com_templates'])) {
                        return;
                    }

                    $params = new Registry($extension->params);
                    $params->set('source_formats', $this->addParam($params->get('source_formats'), ['yaml', 'twig']));

                    $extension->params = $params->toString();
                    $extension->store();
                }

                /**
                 * @param $manifest
                 * @param int $state
                 */
                protected function prepareExtensions($manifest, $state = 1)
                {
                    foreach ($manifest->files->children() as $file) {
                        $attributes = $file->attributes();

                        $search = ['type' => (string) $attributes->type, 'element' => (string) $attributes->id];

                        $clientName = (string) $attributes->client;

                        if (!empty($clientName)) {
                            $client = ApplicationHelper::getClientInfo($clientName, true);
                            $search +=  ['client_id' => $client->id];
                        }

                        $group = (string) $attributes->group;

                        if (!empty($group)) {
                            $search +=  ['folder' => $group];
                        }

                        $extension = new Extension($this->db);

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

                /**
                * @param string $string
                * @param array $options
                * @return string
                */
                protected function addParam($string, array $options): string
                {
                    $items = \array_flip(\explode(',', $string)) + \array_flip($options);

                    return \implode(',', \array_keys($items));
                }

                /**
                 * @param string $gantryVersion
                 * @return array
                 */
                private function checkRequirements($gantryVersion): array
                {
                    $results = [];

                    $this->checkVersion($results, 'PHP', PHP_VERSION);
                    $this->checkVersion($results, 'Joomla!', JVERSION);
                    $this->checkExtensions($results);

                    return $results;
                }

                /**
                 * @param array $results
                 * @param string $name
                 * @param string $version
                 */
                private function checkVersion(array &$results, $name, $version): void
                {
                    $major = $minor = 0;

                    foreach ($this->versions[$name] as $major => $minor) {
                        if (!$major || \version_compare($version, $major, '<')) {
                            continue;
                        }

                        if (\version_compare($version, $minor, '>=')) {
                            return;
                        }

                        break;
                    }

                    if (!$major) {
                        $minor = \reset($this->versions[$name]);
                    }

                    $recommended = \end($this->versions[$name]);
                    $results[]   = \version_compare($recommended, $minor, '>')
                        ? sprintf(
                            '%s %s is not supported. Minimum required version is %s %s, but it is highly recommended to use %s %s or later version.',
                            $name,
                            $version,
                            $name,
                            $minor,
                            $name,
                            $recommended
                        )
                        : sprintf(
                            '%s %s is not supported. Please update to %s %s or later version.',
                            $name,
                            $version,
                            $name,
                            $minor
                        );
                }

                /**
                 * @param array $results
                  */
                private function checkExtensions(array &$results): void
                {
                    foreach ($this->extensions as $name) {
                        if (!extension_loaded($name)) {
                            $results[] = sprintf("Required PHP extension '%s' is missing. Please install it into your system.", $name);
                        }
                    }
                }
            }
        );
    }
};
