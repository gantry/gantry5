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

use Gantry\Component\Config\BlueprintsForm;
use Gantry\Component\Config\Config;
use Gantry\Component\Controller\HtmlController;
use Gantry\Component\Filesystem\Folder;
use Gantry\Component\Request\Request;
use Gantry\Component\Response\JsonResponse;
use Gantry\Framework\Base\Gantry;
use Gantry\Framework\Outlines;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Framework\Theme;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Styles extends HtmlController
{

    protected $httpVerbs = [
        'GET' => [
            '/'              => 'index',
            '/blocks'        => 'undefined',
            '/blocks/*'      => 'display',
            '/blocks/*/**'   => 'formfield'
        ],
        'POST' => [
            '/'          => 'save',
            '/blocks'    => 'forbidden',
            '/blocks/*'  => 'save',
            '/compile'   => 'compile'
        ],
        'PUT' => [
            '/'         => 'save',
            '/blocks'   => 'forbidden',
            '/blocks/*' => 'save'
        ],
        'PATCH' => [
            '/'         => 'save',
            '/blocks'   => 'forbidden',
            '/blocks/*' => 'save'
        ],
        'DELETE' => [
            '/'         => 'forbidden',
            '/blocks'   => 'forbidden',
            '/blocks/*' => 'reset'
        ]
    ];

    public function index()
    {
        $outline = $this->params['configuration'];

        if($outline == 'default') {
            $this->params['overrideable'] = false;
            $this->params['data'] = $this->container['config'];
        } else {
            $this->params['overrideable'] = true;
            $this->params['defaults'] = $this->container['defaults'];
            $this->params['data'] = ConfigServiceProvider::load($this->container, $outline, false, false);
        }

        $this->params['blocks'] = $this->container['styles']->group();
        $this->params['route']  = "configurations.{$this->params['configuration']}.styles";

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/styles/styles.html.twig', $this->params);
    }

    public function display($id)
    {
        $outline = $this->params['configuration'];
        $style = $this->container['styles']->get($id);
        $blueprints = new BlueprintsForm($style);
        $prefix = 'styles.' . $id;

        if($outline == 'default') {
            $this->params['overrideable'] = false;
            $this->params['data'] = $this->container['config']->get($prefix);
        } else {
            $this->params['overrideable'] = true;
            $this->params['defaults'] = $this->container['defaults']->get($prefix);
            $this->params['data'] = ConfigServiceProvider::load($this->container, $outline, false, false)->get($prefix);
        }

        $this->params += [
            'block' => $blueprints,
            'id' => $id,
            'parent' => "configurations/{$this->params['configuration']}/styles",
            'route'  => "configurations.{$this->params['configuration']}.styles.{$prefix}",
            'skip' => ['enabled']
        ];

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/styles/item.html.twig', $this->params);
    }

    public function formfield($id)
    {
        $path = func_get_args();

        $outline = $this->params['configuration'];
        $style = $this->container['styles']->get($id);

        // Load blueprints.
        $blueprints = new BlueprintsForm($style);

        list($fields, $path, $value) = $blueprints->resolve(array_slice($path, 1), '/');

        if (!$fields) {
            throw new \RuntimeException('Page Not Found', 404);
        }

        $fields['is_current'] = true;

        // Get the prefix.
        $prefix = "styles.{$id}." . implode('.', $path);
        if ($value !== null) {
            $parent = $fields;
            $fields = ['fields' => $fields['fields']];
            $prefix .= '.' . $value;
        }
        array_pop($path);

        if($outline == 'default') {
            $this->params['overrideable'] = false;
            $this->params['data'] = $this->container['config']->get($prefix);
        } else {
            $this->params['overrideable'] = true;
            $this->params['defaults'] = $this->container['defaults']->get($prefix);
            $this->params['data'] = ConfigServiceProvider::load($this->container, $outline, false, false)->get($prefix);
        }

        $this->params = [
                'blueprints' => $fields,
                'parent' => $path
                    ? "configurations/{$this->params['configuration']}/styles/blocks/{$id}/" . implode('/', $path)
                    : "configurations/{$this->params['configuration']}/styles/blocks/{$id}",
                'route' => 'styles.' . $prefix
            ] + $this->params;

        if (isset($parent['key'])) {
            $this->params['key'] = $parent['key'];
        }

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/styles/field.html.twig', $this->params);
    }

    public function reset($id)
    {
        $this->params += [
            'data' => [],
        ];

        return $this->display($id);
    }


    public function compile()
    {
        // Validate only exists for JSON.
        if (empty($this->params['ajax'])) {
            $this->undefined();
        }

        $warnings = $this->compileSettings();

        if ($warnings) {
            $this->params += ['warnings' => $warnings];
            return new JsonResponse(
                [
                    'html'    => $this->container['admin.theme']->render('@gantry-admin/layouts/css-warnings.html.twig', $this->params),
                    'warning' => true,
                    'title'   => 'CSS Compiled With Warnings',
                ]
            );
        } else {
            return new JsonResponse(['html' => 'The CSS was successfully compiled', 'title' => 'CSS Compiled']);
        }
    }

    public function save($id = null)
    {
        /** @var Config $config */
        $config = $this->container['config'];

        if ($id) {
            $data = (array) $config->get('styles');
            $data[$id] = $this->request->post->getArray();
        } else {
            $data = $this->request->post->getArray('styles');
        }

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $outline = $this->params['configuration'];
        $save_dir = $locator->findResource("gantry-config://{$outline}", true, true);
        $filename = "{$save_dir}/styles.yaml";

        $file = YamlFile::instance($filename);
        $file->save($data);
        $file->free();

        // Fire save event.
        $event = new Event;
        $event->gantry = $this->container;
        $event->theme = $this->container['theme'];
        $event->controller = $this;
        $event->data = $data;
        $this->container->fireEvent('admin.styles.save', $event);

        // Compile CSS.
        $warnings = $this->compileSettings();

        if (empty($this->params['ajax'])) {
            // FIXME: HTML request: Output compiler warnings!!
            return $id ? $this->display($id) : $this->index();
        }

        if ($warnings) {
            $this->params += ['warnings' => $warnings];
            return new JsonResponse(
                [
                    'html'    => $this->container['admin.theme']->render('@gantry-admin/layouts/css-warnings.html.twig', $this->params),
                    'warning' => true,
                    'title'   => 'CSS Compiled With Warnings',
                ]
            );
        } else {
            return new JsonResponse(['html' => 'The CSS was successfully compiled', 'title' => 'CSS Compiled']);
        }
    }

    /**
     * @returns array
     */
    protected function compileSettings()
    {
        /** @var Theme $theme */
        $theme = $this->container['theme'];
        $outline = $this->params['configuration'];

        return $theme->updateCss($outline !== 'default' ? [$outline => ucfirst($outline)] : null);
    }
}
