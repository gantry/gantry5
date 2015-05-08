<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Filesystem\Folder;
use Gantry\Joomla\TemplateInstaller;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Install extends HtmlController
{
    public function index()
    {
        if (class_exists('\Gantry\Joomla\TemplateInstaller')) {
            $installer = new TemplateInstaller;
            $installer->loadExtension($this->container['theme.name']);
            $installer->installMenus();
            $installer->cleanup();
        }

        return new JsonResponse(['html' => 'Menus have been installed!', 'title' => 'Installed']);
    }

    public function display($id)
    {
        if (class_exists('\Gantry\Joomla\TemplateInstaller')) {
            $installer = new TemplateInstaller;
            $installer->loadExtension($this->container['theme.name']);
            $installer->installMenus();
            $installer->cleanup();
        }

        return new JsonResponse(['html' => 'Menus have been installed!', 'title' => 'Installed']);
    }
}
