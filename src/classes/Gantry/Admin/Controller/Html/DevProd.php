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
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;

class DevProd extends HtmlController
{
    protected $httpVerbs = [
        'POST' => [
            '/'            => 'switchMode',
        ],
    ];

    public function switchMode()
    {
        $mode = $this->request->post['mode']; // 0: dev | 1: prod
        $title = !$mode ? 'Development' : 'Production';

        return new JsonResponse(['html' => 'Successfully changed Gantry into <strong>' . $title . '</strong> mode.', 'title' => $title . ' Mode', 'mode' => $mode]);
    }
}
