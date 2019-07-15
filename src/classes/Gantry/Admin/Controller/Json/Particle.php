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
use Gantry\Component\Config\BlueprintSchema;
use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Response\JsonResponse;

class Particle extends JsonController
{
    protected $httpVerbs = [
        'GET'    => [
            '/'                  => 'selectParticle',
            '/module'            => 'selectModule'
        ],
        'POST'   => [
            '/'                  => 'undefined',
            '/*'                 => 'particle',
            '/*/validate'        => 'validate',
        ],
        'PUT'    => [
            '/*' => 'replace'
        ],
        'PATCH'  => [
            '/*' => 'update'
        ],
        'DELETE' => [
            '/*' => 'destroy'
        ]
    ];

    /**
     * Return a modal for selecting a particle.
     *
     * @return string
     */
    public function selectParticle()
    {
        $groups = [
            'Particles' => ['particle' => []],
        ];

        $particles = [
            'position'    => [],
            'spacer'      => [],
            'system'      => [],
            'particle'    => [],
        ];

        $particles = array_replace($particles, $this->getParticles());
        unset($particles['atom'], $particles['position']);

        foreach ($particles as &$group) {
            asort($group);
        }

        foreach ($groups as $section => $children) {
            foreach ($children as $key => $child) {
                $groups[$section][$key] = $particles[$key];
            }
        }

        $this->params['particles'] = $groups;
        return new JsonResponse(['html' => $this->render('@gantry-admin/modals/particle-picker.html.twig', $this->params)]);
    }

    /**
     * Return a modal content for selecting module.
     *
     * @return mixed
     */
    public function selectModule()
    {
        return new JsonResponse(['html' => $this->render('@gantry-admin/modals/module-picker.html.twig', $this->params)]);
    }

    /**
     * Return form for the particle (filled with data coming from POST).
     *
     * @param string $name
     * @return mixed
     */
    public function particle($name)
    {
        $data = $this->request->post['item'];
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data = $this->request->post->getArray();
        }

        // TODO: add support for other block types as well, like menu.
        // $block = BlueprintForm::instance('layout/block.yaml', 'gantry-admin://blueprints');
        $blueprints = $this->container['particles']->getBlueprintForm($name);

        // Load particle blueprints and default settings.
        $validator = $this->loadBlueprints('menu');
        $callable = function () use ($validator) {
            return $validator;
        };

        // Create configuration from the defaults.
        $item = new Config($data, $callable);
        $item->def('type', 'particle');
        $item->def('title', $blueprints->get('name'));
        $item->def('options.type', $blueprints->get('type', 'particle'));
        $item->def('options.particle', []);
        $item->def('options.block', []);

        $this->params += [
            'item'          => $item,
            // 'block'         => $block,
            'data'          => ['particles' => [$name => $item->options['particle']]],
            'particle'      => $blueprints,
            'parent'        => 'settings',
            'prefix'        => "particles.{$name}.",
            'route'         => "configurations.default.settings",
            'action'        => "particle/{$name}/validate"
        ];

        return new JsonResponse(['html' => $this->render('@gantry-admin/modals/particle.html.twig', $this->params)]);
    }

    /**
     * Validate data for the particle.
     *
     * @param string $name
     * @return JsonResponse
     */
    public function validate($name)
    {
        // Load particle blueprints and default settings.
        $validator = new BlueprintSchema;
        $validator->embed('options', $this->container['particles']->get($name));

        $blueprints = $this->container['particles']->getBlueprintForm($name);

        // Create configuration from the defaults.
        $data = new Config([],
            function () use ($validator) {
                return $validator;
            }
        );

        $data->set('type', 'particle');
        $data->set('particle', $name);
        $data->set('title', $this->request->post['title'] ?: $blueprints->get('name'));
        $data->set('options.particle', $this->request->post->getArray("particles.{$name}"));
        $data->def('options.particle.enabled', 1);

        $block = $this->request->post->getArray('block');
        foreach ($block as $key => $param) {
            if ($param === '') {
                unset($block[$key]);
            }
        }

        if ($block) {
            $data->join('options.block', $block);
        }

        // TODO: validate

        // Fill parameters to be passed to the template file.
        $this->params['item'] = (object) $data->toArray();

        return new JsonResponse(['item' => $data->toArray()]);
    }

    protected function getParticles()
    {
        $particles = $this->container['particles']->all();

        $list = [];
        foreach ($particles as $name => $particle) {
            $type = isset($particle['type']) ? $particle['type'] : 'particle';
            $particleName = isset($particle['name']) ? $particle['name'] : $name;
            $particleIcon = isset($particle['icon']) ? $particle['icon'] : null;
            $list[$type][$name] = ['name' => $particleName, 'icon' => $particleIcon];
        }

        return $list;
    }

    /**
     * Load blueprints.
     *
     * @param string $name
     *
     * @return BlueprintForm
     */
    protected function loadBlueprints($name = 'menu')
    {
        return BlueprintForm::instance("menu/{$name}.yaml", 'gantry-admin://blueprints');
    }
}
