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

use Gantry\Framework\Gantry;
use Gantry\Framework\ThemeInstaller;
use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Adapter\PackageAdapter;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;

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
                 * @var string
                 * */
                public $requiredGantryVersion = '5.5';

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
                    $name = Text::_($manifest->name);

                    // Prevent installation if Gantry 5 isn't enabled or is too old for this template.
                    try {
                        if (!PluginHelper::isEnabled('system', 'gantry5')) {
                            $this->app->enqueueMessage(
                                sprintf('Please install Gantry 5 Framework before installing %s template!', $name),
                                'error'
                            );

                            return false;
                        }

                        $gantry = Gantry::instance();

                        if (!method_exists($gantry, 'isCompatible') || !$gantry->isCompatible($this->requiredGantryVersion)) {
                            throw new \RuntimeException(sprintf(
                                'Please upgrade Gantry 5 Framework to v%s (or later) before installing %s template!',
                                strtoupper($this->requiredGantryVersion),
                                $name
                            ));
                        }
                    } catch (\Exception $e) {
                        Factory::getApplication()->enqueueMessage(Text::sprintf($e->getMessage()), 'error');

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
                    if ($type === 'uninstall') {
                        return true;
                    }

                    $installer = new ThemeInstaller($parent);
                    $installer->initialize();

                    // Install sample data on first install.
                    if (\in_array($type, ['install', 'discover_install'])) {
                        try {
                            $installer->installDefaults();

                            echo $installer->render('install.html.twig');
                        } catch (\Exception $e) {
                            Factory::getApplication()->enqueueMessage(Text::sprintf($e->getMessage()), 'error');
                        }
                    } else {
                        echo $installer->render('update.html.twig');
                    }

                    $installer->finalize();

                    return true;
                }
            }
        );
    }
};
