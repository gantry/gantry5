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

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Controller\JsonController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Layout\Layout;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Base\Gantry;
use RocketTheme\Toolbox\File\JsonFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Atoms
 * @package Gantry\Admin\Controller\Json
 */
class Atoms extends JsonController
{
    protected $httpVerbs = [
        'GET' => [
            '/' => 'index',
            '/*' => 'index',
            '/instance' => 'atom'
        ],
        'POST' => [
            '/' => 'index',
            '/*' => 'index',
            '/instance' => 'atom'
        ]
    ];
    
    public function index()
    {
        $path = implode('/', func_get_args());

        $post = $this->request->request;

        $outline = $post['outline'];
        $type = $post['subtype'];
        $inherit = $post['inherit'];
        $id = $post['id'];

        $this->container['configuration'] = $outline;

        $atoms = new \Gantry\Framework\Atoms((array) $this->container['config']->get('header.atoms'));
        if ($inherit) {
            $atoms->inheritAll($outline);
        }

        $item = (object) $atoms->id($id);

        if ($path === 'list') {
            $list = $atoms->type($type);
            if (empty($item->id)) {
                $item = (object)reset($list);
            }
        }

        $type = isset($item->type) ? $item->type : $type;
        $item->attributes = isset($item->attributes) ? (array) $item->attributes : [];

        $blueprints = new BlueprintsForm($this->container['particles']->get($type));
        $blueprints->set('form.fields._inherit', ['type' => 'gantry.inherit']);

        $params = [
            'gantry'        => $this->container,
            'parent'        => 'settings',
            'route'         => "configurations.{$outline}.settings",
            'inherit'       => $inherit ? $outline : null,
            'title'         => isset($item->title) ? $item->title : '',
            'blueprints'    => $blueprints->get('form'),
            'item'          => $item,
            'data'          => ['particles' => [$type => $item->attributes]],
            'prefix'        => "particles.{$type}.",
            'editable'      => true,
            'overrideable'  => true,
            'skip'          => ['enabled']
        ];

        $html['g-settings-atom'] = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/particle-card.html.twig',  $params);
        if (isset($list)) {
            $html['g-inherit-atom'] = $this->renderAtomsInput($inherit ? $outline : null, $type, $post['selected'], $list);
        }

        return new JsonResponse(['json' => $item, 'html' => $html]);
    }

    public function atom()
    {
        $post = $this->request->request;

        $outline = $post['outline'];
        $id = $post['id'];

        $this->container['configuration'] = $outline;

        $atoms = new \Gantry\Framework\Atoms((array) $this->container['config']->get('header.atoms'));

        $item = (object) $atoms->id($id);
        if (empty($item->id)) {
            throw new \RuntimeException('Atom was not found from the outline', 404);
        }

        $name = $item->type;

        $prefix = "particles.{$name}";

        $blueprints = new BlueprintsForm($this->container['particles']->get($name));
        $blueprints->set('form.fields._inherit', ['type' => 'gantry.inherit']);

        $item->attributes = isset($item->attributes) ? (array) $item->attributes : [];

        $this->params['id'] = $name;
        $this->params += [
            'item'          => $item,
            'data'          => ['particles' => [$name => $item->attributes]],
            'prefix'        => "particles.{$name}.",
            'particle'      => $blueprints,
            'parent'        => 'settings',
            'route'         => "configurations.{$outline}.settings",
            'action'        => str_replace('.', '/', 'configurations.' . $outline . '.layout.' . $prefix . '.validate'),
            'skip'          => ['enabled'],
            'editable'      => false,
            'overrideable'  => true,
        ];

        $html = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/particle-preview.html.twig', $this->params);

        return new JsonResponse(['html' => $html]);
    }

    /**
     * Render input field for particle picker.
     *
     * @param string $outline
     * @param string $type
     * @param string $selected
     * @param array $instances
     * @return string
     */
    protected function renderAtomsInput($outline, $type, $selected, array $instances)
    {
        $params = [
            'layout' => 'input',
            'scope' => 'inherit.',
            'field' => [
                'name' => 'atom',
                'type' => 'gantry.atoms',
                'id' => 'g-inherit-atom',
                'outline' => $outline,
                'atoms' => $instances,
                'atom' => $type
            ],
            'value' => $selected
        ];

        return $this->container['admin.theme']->render('@gantry-admin/forms/fields/gantry/atoms.html.twig', $params);
    }
}
