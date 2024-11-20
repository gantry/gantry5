<?php

/**
 * @package   Gantry 5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

use Joomla\CMS\Application\AdministratorApplication;
use Joomla\CMS\Installer\InstallerAdapter;
use Joomla\CMS\Installer\InstallerScriptInterface;
use Joomla\CMS\Language\Text;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use Joomla\Database\DatabaseInterface;
use Joomla\Filesystem\Exception\FilesystemException;
use Joomla\Filesystem\Folder;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

return new class () implements ServiceProviderInterface {
    public function register(Container $container)
    {
        $container->set(
            InstallerScriptInterface::class,
            new class (
                $container->get(AdministratorApplication::class),
                $container->get(DatabaseInterface::class)
            ) implements InstallerScriptInterface {
                private AdministratorApplication $app;
                private DatabaseInterface $db;

                public function __construct(AdministratorApplication $app, DatabaseInterface $db)
                {
                    $this->app = $app;
                    $this->db  = $db;
                }

                public function install(InstallerAdapter $parent): bool
                {
                    return true;
                }

                public function update(InstallerAdapter $parent): bool
                {
                    return true;
                }

                public function uninstall(InstallerAdapter $parent): bool
                {
                    return true;
                }

                public function preflight(string $type, InstallerAdapter $parent): bool
                {
                    return true;
                }

                public function postflight(string $type, InstallerAdapter $parent): bool
                {
                    if ($type === 'uninstall') {
                        $this->deleteFiles($parent);
                    }

                    return true;
                }

                private function deleteFiles($parent)
                {
                    // Remove all Nucleus files manually as file installer only uninstalls files.
                    $manifest = $parent->getManifest();

                    foreach ($manifest->fileset->files as $files) {
                        $target = (string) $files->attributes()->target;
                        $folder = JPATH_ROOT . '/' . $target;

                        if (\is_dir($folder)) {
                            try {
                                Folder::delete($folder);
                            } catch (FilesystemException $e) {
                                echo Text::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder) . '<br>';
                            }
                        }
                    }
                }
            }
        );
    }
};
