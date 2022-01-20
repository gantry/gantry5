<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Admin;

use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use Gantry\Framework\Theme as SiteTheme;
use Grav\Common\Grav;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Content
 * @package Gantry\Admin
 */
class Content
{
    /** @var Grav */
    protected $container;
    /** @var array */
    protected $files;
    /** @var array */
    protected $content;

    /**
     * Content constructor.
     * @param Grav $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function all()
    {
        if (!$this->content) {
            $files = $this->locateBlueprints();

            $this->content = [];
            foreach ($files as $key => $file) {
                $filename = key($file);
                $file = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename);
                $this->content[$key] = $file->content();
                $file->free();
            }
        }

        return $this->content;
    }

    /**
     * @return array
     */
    public function group()
    {
        $content = $this->all();

        $list = [];
        foreach ($content as $name => $item) {
            $type = dirname($name);
            $name = Gantry::basename($name);
            $type = isset($item['type']) ? $item['type'] : ($type !== '.' ? $type : 'content');
            $list[$type][$name] = $item;
        }

        return $this->sort($list);
    }

    /**
     * @param $id
     * @return array
     */
    public function get($id)
    {
        if ($this->content[$id]) {
            return $this->content[$id];
        }

        $files = $this->locateBlueprints();

        if (empty($files[$id])) {
            throw new \RuntimeException(sprintf("Settings for '%s' not found.", $id), 404);
        }

        $filename = key($files[$id]);
        $file = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename);
        $item = (array)$file->content();
        $file->free();

        return $item;
    }

    /**
     * @param string $id
     * @return BlueprintForm
     */
    public function getBlueprintForm($id)
    {
        return BlueprintForm::instance($id, 'gantry-blueprints://content');
    }

    /**
     * @param array $blocks
     * @return array
     */
    protected function sort(array $blocks)
    {
        $list = [];

        /** @var SiteTheme $theme */
        $theme = $this->container['theme'];
        $ordering = (array) $theme->details()['admin.content'];

        ksort($blocks);

        foreach ($ordering as $name => $order) {
            if (isset($blocks[$name])) {
                $list[$name] = $this->sortItems($blocks[$name], (array) $order);
            }
        }
        $list += $blocks;

        return $list;
    }

    /**
     * @param array $items
     * @param array $ordering
     * @return array
     */
    protected function sortItems(array $items, array $ordering)
    {
        $list = [];

        ksort($items);

        foreach ($ordering as $name) {
            if (isset($items[$name])) {
                $list[$name] = $items[$name];
            }
        }
        $list += $items;

        return $list;
    }

    /**
     * @return array
     */
    protected function locateBlueprints()
    {
        if (!$this->files) {
            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];
            $paths = $locator->findResources('gantry-blueprints://content');
            if (!$paths) {
                // Deprecated in Gantry 5.1.1
                $paths = $locator->findResources('gantry-admin://blueprints/content');
            }

            $this->files = (new ConfigFileFinder)->listFiles($paths);
        }

        return $this->files;
    }
}
