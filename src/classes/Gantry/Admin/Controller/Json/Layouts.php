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
        $inherit = $post['inherit'] === 'true';

        $layout = Layout::instance($outline);
        $item = $layout->find($section);
        $title = isset($item->title) ? $item->title : '';
        $attributes = isset($item->attributes) ? $item->attributes : [];
        $block = $layout->block($section);

        $file = CompiledYamlFile::instance("gantry-admin://blueprints/layout/{$item->type}.yaml");
        $blueprints = new BlueprintsForm($file->content());
        $file->free();

        $params = [
            'gantry'        => $this->container,
            'title'         => $title,
            'blueprints'    => $blueprints->get('form'),
            'data'          => ['particles' => [$item->type => $attributes]],
            'prefix'        => "particles.{$item->type}.",
            'inherit'       => $inherit ? $outline : null,
            'parent'        => 'settings',
            'route'         => 'configurations.section.settings'
        ];

        $html['g-settings-particle'] = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/section-card.html.twig',  $params);

        $file = CompiledYamlFile::instance("gantry-admin://blueprints/layout/block.yaml");
        $blockBlueprints = new BlueprintsForm($file->content());
        $file->free();

        $paramsBlock = [
                'title' => $this->container['translator']->translate('GANTRY5_PLATFORM_BLOCK'),
                'blueprints' => $blockBlueprints->get('form'),
                'data' => ['block' => isset($block->attributes) ? $block->attributes : []],
                'prefix' => 'block.'
            ] + $params;

        $html['g-settings-block-attributes'] = $this->container['admin.theme']->render('@gantry-admin/pages/configurations/layouts/section-card.html.twig',  $paramsBlock);

        return new JsonResponse(['json' => $item, 'html' => $html]);
    }
}
