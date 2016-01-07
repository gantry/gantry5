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

namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Response\JsonResponse;
use RocketTheme\Toolbox\Event\Event;

class Devprod extends JsonController
{
    public function store()
    {
        $global = $this->container['global'];

        $production = ($global->get('production', 0) + 1) & 1;

        // Fire save event.
        $event = new Event;
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
