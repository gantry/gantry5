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

namespace Gantry\Component\Admin;

use Gantry\Component\Controller\JsonController as BaseController;

abstract class JsonController extends BaseController
{
    /**
     * @param string|array $file
     * @param array $context
     * @return string
     */
    public function render($file, array $context = [])
    {
        return $this->container['admin.theme']->render($file, $context);
    }

    /**
     * @param string $action
     * @param string $id
     * @return boolean
     */
    public function authorize($action, $id = null)
    {
        return $this->container['platform']->authorize($action, $id);
    }
}
