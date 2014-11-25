<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Component\Controller\JsonController;
use Gantry\Component\Response\JsonResponse;

class Particles extends JsonController
{
    public function index(array $params)
    {
        // FIXME: This needs to be dynamic, right now is hardcoded.
        $particles = [
            'position' => ['Position'],
            'spacer' => ['Spacer'],
            'pagecontent' => ['Page Content'],
            'particle' => ['Logo', 'Menu', 'Social Buttons', 'Feed Buttons'],
            'atom' => ['Accent Colors', 'Secondary Colors', 'Google Analytics']
        ];

        $response = ['particles' => $particles];
        $response['html'] = $this->container['admin.theme']->render('@gantry-admin/layouts/particles.html.twig', ['particles' => $particles]);

        return new JsonResponse($response);
    }
}
