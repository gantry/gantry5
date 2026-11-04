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

/**
 * Class Exporter
 * @package Gantry\Framework
 */
class Exporter
{
    /**
     * @return array
     */
    public function all()
    {
        if (\function_exists('current_user_can') && !\current_user_can('manage_options')) {
            throw new \RuntimeException('Forbidden', 403);
        }

        $gantry = Gantry::instance();
        /** @var Theme $theme */
        $theme = $gantry['theme'];
        $details = $theme->details();
        /** @var \RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator $locator */
        $locator = $gantry['locator'];
        $dirs = $locator->findResources('gantry-config://');

        $archive = new OutlineArchive();
        $outlines = $archive->collectOutlines($dirs);
        $menus = $archive->collectMenus($dirs);

        foreach ($menus as $name => &$menu) {
            if (\function_exists('wp_get_nav_menu_object')) {
                $obj = \wp_get_nav_menu_object($name);
                if (\is_object($obj) && !empty($obj->name)) {
                    $menu['title'] = $obj->name;
                }
            }
        }
        unset($menu);

        $exists = static function ($uri) use ($locator) {
            $f = $locator->findResource($uri);

            return $f && is_file($f);
        };

        $files = $archive->collectFileRefs($outlines, $menus, $exists);

        return [
            'export' => [
                'gantry' => [
                    'version' => \defined('GANTRY5_VERSION') && GANTRY5_VERSION !== '@version@' ? GANTRY5_VERSION : 'GIT',
                    'format' => 1,
                ],
                'platform' => [
                    'name' => 'wordpress',
                    'version' => \function_exists('get_bloginfo') ? \get_bloginfo('version') : '',
                ],
                'theme' => [
                    'name' => $details->get('name'),
                    'title' => $details->get('details.name'),
                    'version' => $details->get('details.version'),
                    'date' => $details->get('details.date'),
                    'author' => $details->get('details.author'),
                    'copyright' => $details->get('details.copyright'),
                    'license' => $details->get('details.license'),
                    'updates' => $details->get('details.updates.server'),
                ],
            ],
            'outlines' => $outlines,
            'positions' => $this->positions(),
            'menus' => $menus,
            'content' => [],
            'categories' => [],
            'files' => $files,
        ];
    }

    /**
     * @param bool $all
     * @return array
     */
    public function positions($all = true)
    {
        global $wp_registered_sidebars;

        $titles = [];
        if (\is_array($wp_registered_sidebars)) {
            foreach ($wp_registered_sidebars as $id => $sidebar) {
                if (isset($sidebar['name'])) {
                    $titles[$id] = $sidebar['name'];
                }
            }
        }

        $particlesOnly = !$all ? true : false;

        return (new OutlineArchive())->positionsForExport(Widgets::export($particlesOnly), $titles);
    }
}
