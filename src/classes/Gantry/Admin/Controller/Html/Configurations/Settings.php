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

namespace Gantry\Admin\Controller\Html\Configurations;

use Gantry\Admin\Particles;
use Gantry\Component\Admin\HtmlController;
use Gantry\Component\Config\Config;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Services\ConfigServiceProvider;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Settings extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'                 => 'index',
            '/particles'        => 'undefined',
            '/particles/*'      => 'display',
            '/particles/*/**'   => 'formfield',
        ],
        'POST' => [
            '/'                 => 'save',
            '/particles'        => 'forbidden',
            '/particles/*'      => 'save',
            '/particles/*/**'   => 'formfield'
        ],
        'PUT' => [
            '/'            => 'save',
            '/particles'   => 'forbidden',
            '/particles/*' => 'save'
        ],
        'PATCH' => [
            '/'            => 'save',
            '/particles'   => 'forbidden',
            '/particles/*' => 'save'
        ],
        'DELETE' => [
            '/'            => 'forbidden',
            '/particles'   => 'forbidden',
            '/particles/*' => 'reset'
        ]
    ];

    public function index()
    {
        $outline = $this->params['outline'];

        if ($outline === 'default') {
            $this->params['overrideable'] = false;
            $data = $this->container['config'];
        } else {
            $this->params['overrideable'] = true;
            $this->params['defaults'] = $this->container['defaults'];
            $data = ConfigServiceProvider::load($this->container, $outline, false, false);
        }

        /** @var Particles $particles */
        $particles = $this->container['particles'];
        $this->params += [
            'data' => $data,
            'particles' => $particles->group(['atom']),
            'route'  => "configurations.{$outline}.settings",
            'page_id' => $outline
        ];

        return $this->render('@gantry-admin/pages/configurations/settings/settings.html.twig', $this->params);
    }

    public function display($id)
    {
        $outline = $this->params['outline'];

        /** @var Particles $particles */
        $particles = $this->container['particles'];

        $blueprints = $particles->getBlueprintForm($id);
        $prefix = 'particles.' . $id;

        if($outline === 'default') {
            $this->params['overrideable'] = false;
            $data = $this->container['config'];
        } else {
            $this->params['overrideable'] = true;
            $this->params['defaults'] = $this->container['defaults']->get($prefix);
            $data = ConfigServiceProvider::load($this->container, $outline, false, false);
        }

        $this->params += [
            'scope' => 'particle.',
            'particle' => $blueprints,
            'data' =>  ['particle' => $data->get($prefix)],
            'id' => $id,
            'parent' => "configurations/{$outline}/settings",
            'route'  => "configurations.{$outline}.settings.{$prefix}",
            'skip' => ['enabled']
            ];

        return $this->render('@gantry-admin/pages/configurations/settings/item.html.twig', $this->params);
    }

    public function formfield($id)
    {
        $path = func_get_args();

        $end = end($path);
        if ($end === '') {
            array_pop($path);
        }
        if (end($path) === 'validate') {
            return call_user_func_array([$this, 'validate'], $path);
        }

        /** @var Particles $particles */
        $particles = $this->container['particles'];

        // Load blueprints.
        $blueprints = $particles->getBlueprintForm($id);

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');
        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        $data = $this->request->post->getJsonArray('data');

        /** @var Config $config */
        $config = $this->container['config'];

        $offset = "particles.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $offset .= '.' . $value;
            $data = $data ?: $config->get($offset);
            $data = ['data' => $data];
            $scope = 'data.';
        } else {
            $data = $data ?: $config->get($offset);
            $scope = 'data';
        }

        $fields['is_current'] = true;

        array_pop($path);

        $outline = $this->params['outline'];
        $configuration = "configurations/{$outline}";
        $this->params = [
                'configuration' => $configuration,
                'blueprints' => $fields,
                'data' => $data,
                'scope' => $scope,
                'parent' => $path
                    ? "{$configuration}/settings/particles/{$id}/" . implode('/', $path)
                    : "{$configuration}/settings/particles/{$id}",
                'route' => "configurations.{$outline}.settings.{$offset}",
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }
        if (isset($parent['value'])) {
            $this->params['title'] = $parent['value'];
        }

        return $this->render('@gantry-admin/pages/configurations/settings/field.html.twig', $this->params);
    }

    public function validate($particle)
    {
        $path = implode('.', array_slice(func_get_args(), 1, -1));

        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        /** @var Particles $particles */
        $particles = $this->container['particles'];

        // Load particle blueprints.
        $validator = $particles->get($particle);

        // Create configuration from the defaults.
        $data = new Config(
            [],
            function () use ($validator) {
                return $validator;
            }
        );

        $data->join($path, $this->request->post->getArray('data'));

        // TODO: validate

        return new JsonResponse(['data' => $data->get($path)]);
    }

    public function save($id = null)
    {
        if (!$this->request->post->get('_end')) {
            throw new \OverflowException("Incomplete data received. Please increase the value of 'max_input_vars' variable (in php.ini or .htaccess)", 400);
        }

        $data = $id ? [$id => $this->request->post->getArray('particle')] : $this->request->post->getArray('particles');

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $outline = $this->params['outline'];
        $save_dir = $locator->findResource("gantry-config://{$outline}/particles", true, true);

        foreach ($data as $name => $values) {
            $this->saveItem($name, $values, $save_dir);
        }
        @rmdir($save_dir);

        // Fire save event.
        $event = new Event;
        $event->gantry = $this->container;
        $event->theme = $this->container['theme'];
        $event->controller = $this;
        $event->data = $data;
        $this->container->fireEvent('admin.settings.save', $event);

        return $id ? $this->display($id) : $this->index();
    }

    protected function saveItem($id, $data, $save_dir)
    {
        $filename = "{$save_dir}/{$id}.yaml";

        $file = YamlFile::instance($filename);
        if (!is_array($data)) {
            if ($file->exists()) {
                $file->delete();
            }
        } else {
            /** @var Particles $particles */
            $particles = $this->container['particles'];

            $blueprints = $particles->getBlueprintForm($id);
            $config = new Config($data, function() use ($blueprints) { return $blueprints; });

            $file->save($config->toArray());
        }
        $file->free();
    }

    public function reset($id)
    {
        $this->params += [
            'data' => [],
        ];

        return $this->display($id);
    }
}
