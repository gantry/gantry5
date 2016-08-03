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

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Position\Module;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\HtmlResponse;
use Gantry\Component\Response\JsonResponse;
use Gantry\Component\Response\Response;
use Gantry\Framework\Positions as PositionsObject;
use RocketTheme\Toolbox\Blueprints\Blueprints;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Positions extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'                   => 'index',
            '/*'                  => 'undefined',
            '/*/add'              => 'selectParticle',
        ],
        'POST' => [
            '/'                   => 'save',
            '/create'             => 'create',
            '/*'                  => 'undefined',
            '/*/rename'           => 'rename',
            '/*/duplicate'        => 'duplicate',
            '/*/delete'           => 'delete',
            '/*/edit'             => 'undefined',
            '/*/edit/particle'    => 'particle',
            '/*/edit/particle/*'  => 'validateParticle',
            '/edit'               => 'undefined',
            '/edit/particle'      => 'particle',
        ]
    ];

    public function index()
    {
        $this->params['positions'] = $this->container['positions'];

        return $this->container['admin.theme']->render('@gantry-admin/pages/positions/positions.html.twig', $this->params);
    }

    public function create()
    {
        /** @var PositionsObject $position */
        $positions = $this->container['positions'];

        $title = $this->request->post->get('title', 'Untitled');

        $id = $positions->create($title);

        $html = $this->container['admin.theme']->render(
            '@gantry-admin/layouts/position.html.twig',
            ['name' => $id, 'title' => $title]
        );

        return new JsonResponse(['html' => sprintf("Position '%s' created.", $id), 'id' => "position-{$id}", 'position' => $html]);
    }

    public function rename($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $title = $this->request->post['title'];
        $position = $positions[$position];
        $position->rename($title);

        $html = $this->container['admin.theme']->render(
            '@gantry-admin/layouts/position.html.twig',
            ['name' => $position->name, 'title' => $title]
        );

        return new JsonResponse(['html' => sprintf("Position title changed to '%s'.", $title), 'id' => "position-{$position->name}", 'position' => $html]);
    }

    public function duplicate($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $id = $positions->duplicate($position);

        return new JsonResponse(['html' => sprintf("Position duplicated as '%s'.", $id)]);
    }

    public function delete($position)
    {
        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $positions->delete($position);

        return new JsonResponse(['html' => sprintf("Position '%s' deleted.", $position), 'position' => $position]);
    }

    public function save()
    {
        $data = $this->request->post->getJsonArray('positions');

        /** @var PositionsObject $position */
        $positions = $this->container['positions'];

        print_r($data);

        foreach ($data as $pos) {
            $position = $positions[$pos['name']];

            /*
            foreach ($pos as $item) {
                if (!empty($item['id']) && !empty($item['position'])) {
                    $module = $position[$item['position']]->get($item['id']);
                } else {
                    $module = new Module($item['id'], $position, $item);
                }
                print_r($item);
                print_r($module);
            }
            */
        }
        die();
    }

    public function particle($position = null)
    {
        if (!$position) {
            $position = $this->request->post['position'];
        }
        $data = $this->request->post['item'];
        if ($data) {
            $data = json_decode($data, true);
        } else {
            $data = $this->request->post->getArray();
        }
        $name = isset($data['options']['type']) ? $data['options']['type'] : (isset($data['particle']) ? $data['particle'] : null);

        $blueprints = new BlueprintsForm($this->container['particles']->get($name));

        $data['title'] = isset($data['title']) ? $data['title'] : $blueprints['name'];
        $data['options'] = isset($data['options']) ? $data['options'] : [];
        $data['options']['type'] = $name;
        $attributes = isset($data['options']['attributes']) ? $data['options']['attributes'] : [];

        $this->params += [
            'item'          => $data,
            'data'          => ['particles' => [$name => $attributes]],
            'particle'      => $blueprints,
            'parent'        => 'settings',
            'prefix'        => "particles.{$name}.",
            'route'         => "configurations.default.settings",
            'action'        => "positions/{$position}/edit/particle/{$name}"
        ];

        return $this->container['admin.theme']->render('@gantry-admin/pages/positions/particle.html.twig', $this->params);
    }


    public function validateParticle($position, $name)
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        // Load particle blueprints and default settings.
        $validator = new Blueprints;
        $validator->embed('options', $this->container['particles']->get($name));

        $blueprints = new BlueprintsForm($this->container['particles']->get($name));

        // Create configuration from the defaults.
        $data = new Config([],
            function () use ($validator) {
                return $validator;
            }
        );

        $data->set('position', $position);
        $data->set('id', $id = $this->request->post['id']);
        $data->set('type', 'particle');
        $data->set('particle', $name);
        $data->set('title', $this->request->post['title'] ?: $blueprints->post['name']);
        $data->set('options.attributes', $this->request->post->getArray("particles.{$name}"));
        $data->def('options.attributes.enabled', 1);

        // FIXME: implement
        /*
        $assignments = $this->request->post->getArray('assignments');
        foreach ($assignments as $key => $param) {
            if ($param === '') {
                unset($assignments[$key]);
            }
        }
        $assignments = $assignments ?: 'none';
        */
        $assignments = 'all';

        $data->set('options.assignments', $assignments);

        // TODO: validate

        // Fill parameters to be passed to the template file.
        $this->params['position'] = $position;
        $this->params['item'] = (object) $data->toArray();
        $this->params['module'] = new Module($id, $position, $data->toArray());

        $html = $this->container['admin.theme']->render('@gantry-admin/pages/positions/item.html.twig', $this->params);

        return new JsonResponse(['item' => $data->toArray(), 'html' => $html, 'position' => $position]);
    }

    public function selectParticle($position)
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

        $this->params += [
            'particles' => $groups,
            'route' => "positions/{$position}/edit/particle",
        ];

        return $this->container['admin.theme']->render('@gantry-admin/modals/particle-picker.html.twig', $this->params);
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
}
