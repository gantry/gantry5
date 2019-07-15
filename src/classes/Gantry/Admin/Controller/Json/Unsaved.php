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

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Admin\JsonController;
use Gantry\Component\Response\JsonResponse;

class Unsaved extends JsonController
{
    protected $httpVerbs = [
        'GET' => [
            '/' => 'index'
        ]
    ];

    public function index()
    {
        $response = ['html' => $this->render('@gantry-admin/ajax/unsaved.html.twig')];
        return new JsonResponse($response);
    }
}
