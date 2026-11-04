<?php

/**
 * @package   Gantry5
 * @author    Tiger12 http://tiger12.com
 * @originalCreator  RocketTheme (Gantry Framework)
 * @currentDeveloper  Tiger12, LLC
 * @copyright Copyright (C) 2007 - 2021 Tiger12, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\WordPress\OutlineArchive;
use Gantry\WordPress\Widgets;
use Symfony\Component\Yaml\Yaml;

/**
 * Class Importer
 * @package Gantry\Framework
 */
class Importer
{
    /** @var string|null */
    protected $folder;

    /**
     * @param string|null $folder
     */
    public function __construct($folder = null)
    {
        $this->folder = $folder;
    }

    /**
     * @return array
     */
    public function all()
    {
        if (\function_exists('current_user_can') && !\current_user_can('manage_options')) {
            throw new \RuntimeException('Forbidden', 403);
        }

        if (!$this->folder || !is_dir($this->folder)) {
            throw new \RuntimeException('Invalid export file.', 400);
        }

        $archive = new OutlineArchive();
        $archive->assertSafeTree($this->folder);

        $exportFile = $this->folder . '/export.yaml';
        if (!is_file($exportFile)) {
            throw new \RuntimeException('Invalid export file.', 400);
        }

        $raw = @file_get_contents($exportFile);
        if ($raw === false) {
            throw new \RuntimeException('Invalid export file.', 400);
        }

        try {
            $export = Yaml::parse($raw);
        } catch (\Exception $e) {
            throw new \RuntimeException('Invalid export file.', 400);
        }

        if (!\is_array($export) || !isset($export['gantry'])) {
            throw new \RuntimeException('Invalid export file.', 400);
        }

        $platform = isset($export['platform']['name']) ? $export['platform']['name'] : '';
        $isWp = $platform === 'wordpress';

        $gantry = Gantry::instance();
        /** @var \RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $target = $locator->findResource('gantry-config://', true, true);
        if (!$target) {
            throw new \RuntimeException('Unable to locate configuration folder.', 500);
        }

        $outlines = $archive->importOutlines($this->folder . '/outlines', $target, $isWp);

        if ($isWp) {
            $archive->importMenus($this->folder . '/menus', $target);
            $this->importPositions($archive->readPositions($this->folder . '/positions'));
        }

        $archive->importFiles(
            $this->folder . '/files',
            static function ($uri) use ($locator) {
                return $locator->findResource($uri, true, true);
            }
        );

        clearstatcache();

        $list = [];
        foreach ($outlines as $name) {
            $list[$name] = ucwords(trim(preg_replace(['|_|', '|/|'], [' ', ' / '], $name)));
            if ($name === 'default') {
                $list[$name] = 'Default';
            }
        }

        try {
            $gantry['theme']->updateCss($list);
        } catch (\Exception $e) {
            // Ignore CSS compile failures.
        }

        return ['outlines' => $outlines];
    }

    /**
     * @param array $data
     * @return void
     */
    public function positions(array $data)
    {
        Widgets::import($data);
    }

    /**
     * @param array $positions
     * @return void
     */
    protected function importPositions(array $positions)
    {
        if (!$positions) {
            return;
        }

        $sidebars = (array) \get_option('sidebars_widgets');

        foreach (array_keys($positions) as $sidebarId) {
            if (!isset($sidebars[$sidebarId]) || !\is_array($sidebars[$sidebarId])) {
                continue;
            }

            if (!isset($sidebars['wp_inactive_widgets']) || !\is_array($sidebars['wp_inactive_widgets'])) {
                $sidebars['wp_inactive_widgets'] = [];
            }

            foreach ($sidebars[$sidebarId] as $widgetId) {
                $sidebars['wp_inactive_widgets'][] = $widgetId;
            }

            $sidebars[$sidebarId] = [];
        }

        \update_option('sidebars_widgets', $sidebars);

        Widgets::import($positions);
    }
}
