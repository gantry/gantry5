<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Admin;

use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\ConfigFileFinder;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Framework\Gantry;
use Gantry\Framework\Platform;
use Gantry\Framework\Theme as SiteTheme;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

/**
 * Class Particles
 * @package Gantry\Admin
 */
class Particles
{
    /** @var Gantry */
    protected $container;
    /** @var array|null */
    protected $files;
    /** @var array|null */
    protected $particles;

    /**
     * Particles constructor.
     * @param Gantry $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param string $outline
     * @param string|null $particle
     * @return bool
     */
    public function overrides($outline, $particle = null)
    {
        if ($outline === 'default') {
            return true;
        }

        /** @var UniformResourceLocator $locator */
        $locator = $this->container['locator'];

        if ($particle) {
            $resource = $locator->findResources("gantry-theme://config/{$outline}/particles/{$particle}.yaml");
            return !empty($resource);
        }

        $resource = $locator->findResources("gantry-theme://config/{$outline}/particles");
        return !empty($resource);
    }

    /**
     * @return array
     */
    public function all()
    {
        if (null ===$this->particles) {
            /** @var Platform $platform */
            $platform = $this->container['platform'];
            $files = $this->locateParticles();

            $this->particles = [];
            foreach ($files as $key => $fileArray) {
                $filename = key($fileArray);
                $file = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename);
                $particle = (array)$file->content();
                $file->free();

                if (empty($particle['dependencies']) || $platform->checkDependencies($particle['dependencies'])) {
                    $this->particles[$key] = $particle;
                }
            }
        }

        return $this->particles;
    }

    /**
     * @param array $exclude
     * @return array
     */
    public function group($exclude = [])
    {
        $particles = $this->all();

        $list = [];
        foreach ($particles as $name => $particle) {
            $type = isset($particle['type']) ? $particle['type'] : 'particle';
            if (in_array($type, $exclude)) {
                continue;
            }
            if (in_array($type, ['spacer', 'system'])) {
                $type = 'position';
            }
            $list[$type][$name] = $particle;
        }

        return $this->sort($list);
    }

    /**
     * @param string $id
     * @return array
     */
    public function get($id)
    {
        if (isset($this->particles[$id])) {
            return $this->particles[$id];
        }

        $files = $this->locateParticles();

        if (empty($files[$id])) {
            throw new \RuntimeException(sprintf("Settings for '%s' not found.", $id), 404);
        }

        $filename = key($files[$id]);
        $file = CompiledYamlFile::instance(GANTRY5_ROOT . '/' . $filename);
        $particle = (array)$file->content();
        $particle['subtype'] = $id; // TODO: can this be done better or is it fine like that?
        $file->free();

        return $particle;
    }

    /**
     * @param string $id
     * @return BlueprintForm
     */
    public function getBlueprintForm($id)
    {
        return BlueprintForm::instance($id, 'gantry-blueprints://particles');
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
        $ordering = (array) $theme->details()['admin.settings'] ?: [
                'particle' => [],
                'position' => ['position', 'spacer', 'messages', 'content'],
                'atom' => []
            ];

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
    protected function locateParticles()
    {
        if (!$this->files) {
            /** @var UniformResourceLocator $locator */
            $locator = $this->container['locator'];
            $paths = $locator->findResources('gantry-blueprints://particles');

            $this->files = (new ConfigFileFinder)->listFiles($paths);
        }

        return $this->files;
    }
}
