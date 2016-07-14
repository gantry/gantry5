<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
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
        if (!$this->container->authorize('updates.manage')) {
            $this->forbidden();
        }

        if (class_exists('\Gantry\Joomla\TemplateInstaller')) {
            $installer = new TemplateInstaller;
        }

        if (isset($installer)) {
            $installer->initialized = true;
            $installer->loadExtension($this->container['theme.name']);
            $installer->installDefaults();
            $installer->installSampleData();
            $installer->finalize();

            $this->params['content'] = $installer->render('sampledata.html.twig');
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/install/install.html.twig', $this->params);
    }
}
