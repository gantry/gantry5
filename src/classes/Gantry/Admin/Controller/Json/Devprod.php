<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin\Controller\Json;

use Gantry\Admin\Events\Event;
use Gantry\Component\Admin\JsonController;
use Gantry\Component\Response\JsonResponse;

/**
 * Class Devprod
 * @package Gantry\Admin\Controller\Json
 */
class Devprod extends JsonController
{
    /**
     * @return JsonResponse
     */
    public function store()
    {
        $production = (int)(bool)$this->request->post['mode'];

        // Fire save event.
        $event = new Event();
        $event->gantry = $this->container;
        $event->controller = $this;
        $event->data = ['production' => $production];

        $this->container->fireEvent('admin.global.save', $event);

        $response = [
            'mode' => $production,
            'title' => $production ? 'Production' : 'Development',
            'html' => $production ? 'Production mode enabled' : 'Development mode enabled',
        ];

        return new JsonResponse($response);
    }
}
