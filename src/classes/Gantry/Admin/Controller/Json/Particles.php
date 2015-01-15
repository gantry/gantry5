<?php
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
