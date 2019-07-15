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
use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Response\JsonResponse;

/**
 * Class Layouts
 * @package Gantry\Admin\Controller\Json
 */
class Layouts extends JsonController
{
    protected $httpVerbs = [
        'GET' => [
            '/' => 'index',
            '/*' => 'index',
            '/particle' => 'particle'
        ],
        'POST' => [
            '/' => 'index',
            '/*' => 'index',
            '/particle' => 'particle'
        ]
    ];
    
    public function index()
    {
        $path = implode('/', func_get_args());

        $post = $this->request->request;

        $outline = $post['outline'];
        $type = $post['type'];
        $subtype = $post['subtype'];
        $inherit = $post['inherit'];
        $clone = $post['mode'] === 'clone';
        $id = $post['id'];

        $this->container['outline'] = $outline;
        $this->container['configuration'] = $outline;

        $layout = Layout::instance($outline);
        if ($inherit) {
            $layout->inheritAll();
        }

        if ($path == 'list' && !$layout->isLayoutType($type)) {
            $instance = $this->getParticleInstances($outline, $subtype, null);
            $id = $instance['selected'];
        }

        $item = $layout->find($id);
        $type = isset($item->type) ? $item->type : $type;
        $subtype = isset($item->subtype) ? $item->subtype : $subtype;
        $item->attributes = isset($item->attributes) ? (array) $item->attributes : [];
        $block = $layout->block($id);
        $block = isset($block->attributes) ? (array) $block->attributes : [];

        $params = [
            'gantry'        => $this->container,
            'parent'        => 'settings',
            'route'         => "configurations.{$outline}.settings",
            'inherit'       => $inherit ? $outline : null,
        ];

        if ($layout->isLayoutType($type)) {
            $name = $type;
            $particle = false;
            $defaults = [];
            $blueprints = BlueprintForm::instance("layout/{$name}.yaml", 'gantry-admin://blueprints');
        } else {
            $name = $subtype;
            $particle = true;
            $defaults = $this->container['config']->get("particles.{$name}");
            $item->attributes = $item->attributes + $defaults;
            $blueprints = $this->container['particles']->getBlueprintForm($name);
            $blueprints->set('form/fields/_inherit', ['type' => 'gantry.inherit']);
        }

        $paramsParticle = [
            'title'         => isset($item->title) ? $item->title : '',
            'blueprints'    => $blueprints->get('form'),
            'item'          => $item,
            'data'          => ['particles' => [$name => $item->attributes]],
            'defaults'      => ['particles' => [$name => $defaults]],
            'prefix'        => "particles.{$name}.",
            'editable'      => $particle,
            'overrideable'  => $particle,
            'skip'          => ['enabled']
        ] + $params;

        $html['g-settings-particle'] = $this->render('@gantry-admin/pages/configurations/layouts/particle-card.html.twig',  $paramsParticle);
        $html['g-settings-block-attributes'] = $this->renderBlockFields($block, $params);
        if ($path == 'list') {
            $html['g-inherit-particle'] = $this->renderParticlesInput($inherit || $clone ? $outline : null, $subtype, $post['selected']);
        }

        return new JsonResponse(['json' => $item, 'html' => $html]);
    }

    public function particle()
    {
        $post = $this->request->request;

        $outline = $post['outline'];
        $id = $post['id'];

        $this->container['outline'] = $outline;
        $this->container['configuration'] = $outline;

        $layout = Layout::instance($outline);

        $particle = clone $layout->find($id);
        if (!isset($particle->type)) {
            throw new \RuntimeException('Particle was not found from the outline', 404);
        }

        $particle->block = $layout->block($id);

        $name = $particle->subtype;
        $prefix = "particles.{$name}";
        $defaults = (array) $this->container['config']->get($prefix);
        $attributes = (array) $particle->attributes + $defaults;

        $particleBlueprints = $this->container['particles']->getBlueprintForm($name);
        $particleBlueprints->set('form/fields/_inherit', ['type' => 'gantry.inherit']);

        $blockBlueprints = BlueprintForm::instance('layout/block.yaml', 'gantry-admin://blueprints');

        // TODO: Use blueprints to merge configuration.
        $particle->attributes = (object) $attributes;

        $this->params['id'] = $name;
        $this->params += [
            'extra'         => $blockBlueprints,
            'item'          => $particle,
            'data'          => ['particles' => [$name => $attributes]],
            'defaults'      => ['particles' => [$name => $defaults]],
            'prefix'        => "particles.{$name}.",
            'particle'      => $particleBlueprints,
            'parent'        => 'settings',
            'route'         => "configurations.{$outline}.settings",
            'action'        => str_replace('.', '/', 'configurations.' . $outline . '.layout.' . $prefix . '.validate'),
            'skip'          => ['enabled'],
            'editable'      => false,
            'overrideable'  => true,
        ];

        $html = $this->render('@gantry-admin/pages/configurations/layouts/particle-preview.html.twig', $this->params);

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Render block settings.
     *
     * @param array $block
     * @param array $params
     * @return string
     */
     protected function renderBlockFields(array $block, array $params)
     {
         $blockBlueprints = BlueprintForm::instance('layout/block.yaml', 'gantry-admin://blueprints');

         $paramsBlock = [
                 'title' => $this->container['translator']->translate('GANTRY5_PLATFORM_BLOCK'),
                 'blueprints' => ['fields' => $blockBlueprints->get('form/fields/block_container/fields')],
                 'data' => ['block' => $block],
                 'prefix' => 'block.',
             ] + $params;

         return $this->render('@gantry-admin/forms/fields.html.twig',  $paramsBlock);
     }

    /**
     * Gets the list of available particle instances for an outline
     *
     * @param string $outline
     * @param string $particle
     * @param string $selected
     * @return string
     */

    protected function getParticleInstances($outline, $particle, $selected)
    {
        $list = $outline ? $this->container['outlines']->getParticleInstances($outline, $particle, false) : [];
        $selected = isset($list[$selected]) ? $selected : key($list);

        return ['list' => $list, 'selected' => $selected];
    }

    /**
     * Render input field for particle picker.
     *
     * @param string $outline
     * @param string $particle
     * @param string $selected
     * @return string
     */
    protected function renderParticlesInput($outline, $particle, $selected)
    {
        $instances = $this->getParticleInstances($outline, $particle, $selected);

        $params = [
            'layout' => 'input',
            'scope' => 'inherit.',
            'field' => [
                'name' => 'particle',
                'type' => 'gantry.particles',
                'id' => 'g-inherit-particle',
                'outline' => $outline,
                'particles' => $instances['list'],
                'particle' => $particle
            ],
            'value' => $instances['selected']
        ];

        return $this->render('@gantry-admin/forms/fields/gantry/particles.html.twig', $params);
    }
}
