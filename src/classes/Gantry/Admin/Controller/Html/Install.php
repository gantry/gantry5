<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Admin\HtmlController;
use Gantry\Framework\ThemeInstaller;

class Install extends HtmlController
{
    public function index()
    {
        if (!$this->authorize('updates.manage') || !class_exists('\Gantry\Framework\ThemeInstaller')) {
            $this->forbidden();
        }

        $installer = new ThemeInstaller();
        $installer->initialized = true;
        $installer->loadExtension($this->container['theme.name']);
        $installer->installDefaults();
        $installer->installSampleData();
        $installer->finalize();

        $this->params['actions'] = $installer->actions;
        
        return $this->render('@gantry-admin/pages/install/install.html.twig', $this->params);
    }
}
