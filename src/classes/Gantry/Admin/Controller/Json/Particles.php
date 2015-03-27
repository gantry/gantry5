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

namespace Gantry\Admin\Controller\Json;

use Gantry\Admin\Controller\Html\Settings;
use Gantry\Component\Controller\JsonController;
use Gantry\Component\Response\JsonResponse;

class Particles extends JsonController
{
    public function index()
    {
        // Set ordering of the types.
        $particles = [
            'position' => [],
            'spacer' => [],
            'pagecontent' => [],
            'particle' => [],
            'atom' => []
        ];

        $particles = array_replace($particles, $this->getParticles());
        foreach ($particles as &$group) {
            asort($group);
        }

        $response = ['particles' => $particles];
        $response['html'] = $this->container['admin.theme']->render('@gantry-admin/layouts/particles.html.twig', ['particles' => $particles]);

        return new JsonResponse($response);
    }

    protected function getParticles()
    {
        $particles = $this->container['particles']->all();

        $list = [];
        foreach ($particles as $name => $particle) {
            $type = isset($particle['type']) ? $particle['type'] : 'particle';
            $list[$type][$name] = $particle['name'];
        }

        return $list;
    }
}
