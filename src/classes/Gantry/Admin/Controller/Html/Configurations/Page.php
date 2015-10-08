<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2015 RocketTheme, LLC
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
use RocketTheme\Toolbox\Blueprints\Blueprints;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Page extends HtmlController
{
    protected $httpVerbs = [
        'GET' => [
            '/'                 => 'index'
        ],
        'POST' => [
            '/'                 => 'save'
        ],
        'PUT' => [
            '/'            => 'save'
        ],
        'PATCH' => [
            '/'            => 'save'
        ],
        'DELETE' => [
            '/'            => 'forbidden'
        ]
    ];

    public function index()
    {
        $configuration = $this->params['configuration'];

        if($configuration == 'default') {
            $this->params['overrideable'] = false;
        } else {
            $this->params['defaults'] = $this->container['defaults'];
            $this->params['overrideable'] = true;
        }

        $this->params['page'] = $this->container['page']->group();
        $this->params['route']  = "configurations.{$this->params['configuration']}.settings";
        $this->params['page_id'] = $configuration;

        //$this->params['layout'] = LayoutObject::instance($configuration);

        return $this->container['admin.theme']->render('@gantry-admin/pages/configurations/page/page.html.twig', $this->params);
    }

    public function save($id = null)
    {
        $data = $id ? [$id => $this->request->post->getArray()] : $this->request->post->getArray('page');

        foreach ($data as $name => $values) {
            $this->saveItem($name, $values);
        }

        // Fire save event.
        $event = new Event;
        $event->gantry = $this->container;
        $event->theme = $this->container['theme'];
        $event->controller = $this;
        $event->data = $data;
        $this->container->fireEvent('admin.page.save', $event);

        return $id ? $this->display($id) : $this->index();
    }

    protected function saveItem($id, $data)
    {
        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        // Save layout into custom directory for the current theme.
        $configuration = $this->params['configuration'];
        $save_dir = $locator->findResource("gantry-config://{$configuration}/page", true, true);
        $filename = "{$save_dir}/{$id}.yaml";

        $file = YamlFile::instance($filename);
        if (!is_array($data)) {
            if ($file->exists()) {
                $file->delete();
            }
        } else {
            $blueprints = new BlueprintsForm($this->container['page']->get($id));
            $config = new Config($data, function() use ($blueprints) { return $blueprints; });

            $file->save($config->toArray());
        }
        $file->free();
    }
}
