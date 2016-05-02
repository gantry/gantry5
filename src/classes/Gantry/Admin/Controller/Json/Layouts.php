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
 * Class Layouts
 * @package Gantry\Admin\Controller\Json
 * @deprecated
 */
class Layouts extends JsonController
{
    protected $httpVerbs = [
        'GET' => [
            '/' => 'index'
        ],
        'POST' => [
            '/' => 'index'
        ]
    ];
    
    public function index()
    {
        $post = $this->request->request;

        $outline = $post['outline'];
        $section = $post['section'];
        $name = $this->container['configurations']->name($outline);

        $layout = Layout::instance($outline);
        $item = $layout->find($section);

        $file = CompiledYamlFile::instance("gantry-admin://blueprints/layout/section.yaml");
        $blueprints = new BlueprintsForm($file->content());
        $file->free();

        $file = CompiledYamlFile::instance("gantry-admin://blueprints/layout/block.yaml");
        $block = new BlueprintsForm($file->content());
        $file->free();


        $params = [
            'blueprints'    => $blueprints->get('form'),
            'data'          => ['particles' => ['section' => $item->attributes]],
            'prefix'        => 'particles.section.',
            'inherit'       => $name,
            'parent'        => 'settings',
            'route'         => 'configurations.section.settings'
        ];

        if (isset($item->block)) {
            $paramsBlock = [
                    'blueprints' => $block->get('form'),
                    'data' => ['block' => $item->block],
                    'prefix' => 'block.'
                ] + $params;
        }

        $html['g-settings-particle'] = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/section-card.html.twig',  $params);
        if (isset($paramsBlock)) {
            $html['g-settings-block'] = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/section-card.html.twig',  $paramsBlock);
        }

        return new JsonResponse(['json' => $layout->find($section), 'html' => $html]);
    }
}
