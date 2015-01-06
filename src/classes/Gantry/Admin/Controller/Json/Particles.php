<?php
namespace Gantry\Admin\Controller\Json;

use Gantry\Admin\Controller\Html\Settings;
use Gantry\Component\Controller\JsonController;
use Gantry\Component\Response\JsonResponse;

class Particles extends JsonController
{
    public function index()
    {
        // FIXME: This needs to be fully dynamic, right now some parts are hardcoded.
        $particles = [
            'position' => [
                'position' => 'Position'
            ],
            'spacer' => [
                'spacer' => 'Spacer'
            ],
            'pagecontent' => [
                'mainbody' => 'Page Content'
            ],
            'particle' => [
                'social-buttons' => 'Social Buttons',
                'feed-buttons' => 'Feed Buttons'
            ],
            'atom' => [
                'accent-colors' => 'Accent Colors',
                'secondary-colors' => 'Secondary Colors',
                'google-analytics' => 'Google Analytics'
            ]
        ];

        $particles = array_merge_recursive($particles, $this->getParticles());
        foreach ($particles as &$group) {
            asort($group);
        }

        $response = ['particles' => $particles];
        $response['html'] = $this->container['admin.theme']->render('@gantry-admin/layouts/particles.html.twig', ['particles' => $particles]);

        return new JsonResponse($response);
    }

    public function edit($id)
    {
        // FIXME: hardcoded!
        $settings = (new Settings($this->container))->setParams($this->params);

        $response = [
            'html' => $settings->display('menu')
//            'html' => $this->container['admin.theme']->render('@gantry-admin/layouts/particles_edit.html.twig', ['id' => $id])
            ];

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
