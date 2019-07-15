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

namespace Gantry\Admin\Controller\Html;

use Gantry\Component\Admin\HtmlController;
use Gantry\Component\Config\BlueprintSchema;
use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Position\Module;
use Gantry\Component\Position\Position;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Assignments;
use Gantry\Framework\Positions as PositionsObject;

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

        return $this->render('@gantry-admin/pages/positions/positions.html.twig', $this->params);
    }

    public function create()
    {
        // Create only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        /** @var PositionsObject $position */
        $positions = $this->container['positions'];

        $title = (string) $this->request->post->get('title', 'Untitled');
        $key = (string) $this->request->post['key'];

        $id = $positions->create($title, $key);

        $html = $this->render('@gantry-admin/layouts/position.html.twig', ['position' => ['name' => $id, 'title' => $title]]);

        return new JsonResponse(['html' => sprintf("Position '%s' created.", $id), 'id' => "position-{$id}", 'key' => $id, 'position' => $html]);
    }

    public function rename($old)
    {
        // Rename only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        $title = (string) $this->request->post['title'];
        $key = (string) $this->request->post['key'];
        $data = (string) $this->request->post['data'];

        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];
        $position = $positions[$old];
        $exists = isset($position);

        if (!$exists) {
            if (!$data) {
                throw new \RuntimeException(sprintf("Position '%s' not found", $old), 404);
            }

            $position = new Position($key ?: $old);
        }

        if (strlen($title)) {
            $position->title = (string) $title;
        }
        if ($exists && strlen($key)) {
            $position = $position->rename($key);
        } else {
            $position->save();
        }

        if ($data) {
            $data = ['title' => $position->title] + json_decode($data, true);

            $position = new Position($position->name, $data);
        }

        $html = $this->render('@gantry-admin/layouts/position.html.twig', ['position' => $position]);

        return new JsonResponse(['html' => sprintf("Position saved"), 'id' => "position-{$position->name}", 'key' => $position->name, 'position' => $html]);
    }

    public function duplicate($position)
    {
        // Duplicate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $id = $positions->duplicate($position);

        return new JsonResponse(['html' => sprintf("Position duplicated as '%s'.", $id)]);
    }

    public function delete($position)
    {
        // Delete only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        /** @var PositionsObject $positions */
        $positions = $this->container['positions'];

        $positions->delete($position);

        return new JsonResponse(['html' => sprintf("Position '%s' deleted.", $position), 'position' => $position]);
    }

    public function save()
    {
        // Save only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        $data = $this->request->post->getJsonArray('positions');

        /** @var PositionsObject $position */
        $positions = $this->container['positions'];
        $positions->import($data);

        return new JsonResponse(['html' => sprintf("Positions saved.")]);
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

        $blueprints = $this->container['particles']->getBlueprintForm($name);

        $chromeBlueprints = BlueprintForm::instance('position/chrome.yaml', 'gantry-admin://blueprints');

        $data['title'] = isset($data['title']) ? $data['title'] : $blueprints['name'];
        $data['chrome'] = isset($data['chrome']) ? $data['chrome'] : [];
        $data['options'] = isset($data['options']) ? $data['options'] : [];
        $data['options']['type'] = $name;
        $attributes = isset($data['options']['attributes']) ? $data['options']['attributes'] : [];
        $assignments = new Assignments();

        $this->params += [
            'item'          => $data,
            'data'          => [
                'particles' => [$name => $attributes],
                'chrome'    => $data['chrome'],
                'assignments' => isset($data['assignments']) ? $data['assignments'] : 'all'
            ],
            'particle'      => $blueprints,
            'chrome'        => $chromeBlueprints,
            'assignments'   => $assignments->get(),
            'parent'        => 'settings',
            'prefix'        => "particles.{$name}.",
            'route'         => "configurations.default.settings",
            'action'        => "positions/{$position}/edit/particle/{$name}"
        ];

        return $this->render('@gantry-admin/pages/positions/particle.html.twig', $this->params);
    }


    public function validateParticle($position, $name)
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        if (!$this->request->post->get('_end')) {
            throw new \OverflowException("Incomplete data received. Please increase the value of 'max_input_vars' variable (in php.ini or .htaccess)", 400);
        }

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

        $data->set('position', $position);
        $data->set('id', $id = $this->request->post['id']);
        $data->set('type', 'particle');
        $data->set('title', $this->request->post['title'] ?: $blueprints->post['name']);
        $data->set('chrome', $this->request->post->getArray('chrome'));
        $data->set('options.type', $name);
        $data->set('options.attributes', $this->request->post->getArray("particles.{$name}"));
        $data->def('options.attributes.enabled', 1);

        $assignments = (new Assignments())->filter($this->request->post->getArray('assignments'), true);

        if (!$assignments) {
            // Use special syntax for no assignments.
            $assignments = 'none';
        } elseif ($assignments === ['page' => [true]]) {
            // Use special syntax for assigned to all pages. This is a special case and hardcoded for now.
            $assignments = 'all';
        }

        $data->set('assignments', $assignments);

        // TODO: validate

        // Fill parameters to be passed to the template file.
        $this->params['position'] = $position;
        $this->params['item'] = (object) $data->toArray();
        $this->params['module'] = new Module($id, $position, $data->toArray());

        $html = $this->render('@gantry-admin/pages/positions/item.html.twig', $this->params);

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

        return $this->render('@gantry-admin/modals/particle-picker.html.twig', $this->params);
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
