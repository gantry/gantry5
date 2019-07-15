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

use Gantry\Admin\ThemeList;
use Gantry\Component\Admin\HtmlController;

class Themes extends HtmlController
{
    public function index()
    {
        $this->params['themes'] = (new ThemeList)->getThemes();

        return $this->render('@gantry-admin/pages/themes/themes.html.twig', $this->params);
    }
}
