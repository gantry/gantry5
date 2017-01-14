<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   MIT
 *
 * http://opensource.org/licenses/MIT
 */

namespace Gantry\Admin;

use Gantry\Framework\Gantry;
use Grav\Common\Grav;
use Grav\Common\Page\Page;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Event\EventSubscriberInterface;
use RocketTheme\Toolbox\File\YamlFile;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class EventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            'admin.global.save' => ['onGlobalSave', 0],
            'admin.styles.save' => ['onStylesSave', 0],
            'admin.settings.save' => ['onSettingsSave', 0],
            'admin.layout.save' => ['onLayoutSave', 0],
            'admin.assignments.save' => ['onAssignmentsSave', 0],
            'admin.menus.save' => ['onMenusSave', 0]
        ];
    }

    public function onGlobalSave(Event $event)
    {
        $gantry = Gantry::instance();
        /** @var UniformResourceLocator $locator */
        $locator = $gantry['locator'];

        $filename = 'config://plugins/gantry5.yaml';
        $file = YamlFile::instance($locator->findResource($filename, true, true));

        $content = $file->content();
        $content['production'] = (bool) $event->data['production'];

        $file->save($content);
        $file->free();
    }

    public function onStylesSave(Event $event)
    {
        $cookie = md5($event->theme->name);
        $this->updateCookie($cookie, false, time() - 42000);
    }

    protected function updateCookie($name, $value, $expire = 0)
    {
        // TODO: move to better place, copied from Gantry main plugin file.
        $grav = Grav::instance();
        $uri = $grav['uri'];
        $config = $grav['config'];

        $path   = $config->get('system.session.path', '/' . ltrim($uri->rootUrl(false), '/'));
        $domain = $uri->host();

        setcookie($name, $value, $expire, $path, $domain);
    }

    public function onSettingsSave(Event $event)
    {
    }

    public function onLayoutSave(Event $event)
    {
    }

    public function onAssignmentsSave(Event $event)
    {
    }

    public function onMenusSave(Event $event)
    {
        $defaults = [
            'id' => 0,
            'layout' => 'list',
            'target' => '_self',
            'dropdown' => '',
            'icon' => '',
            'image' => '',
            'subtitle' => '',
            'icon_only' => false,
            'visible' => true,
            'group' => 0,
            'columns' => [],
            'link_title' => '',
            'hash' => '',
            'class' => ''
        ];

        $menu = $event->menu;

        // Each menu level has ordering from 1..n counting all menu items in the same level.
        $ordering = $this->flattenOrdering($menu['ordering']);

        $grav = Grav::instance();
        $pages = $grav['pages'];

        // Initialize pages.
        $visible = $pages->all()->visible();
        $list = [];

        /** @var Page $page */
        foreach ($visible as $page) {
            $route = trim($page->route(), '/');
            $order = isset($ordering[$route]) ? $ordering[$route] : 0;
            $parent = $page->parent();
            if ($order) {
                $list[] = $page = $page->move($parent);
                $page->order($order);
            } else {
                $list[] = $page;
            }

            $page->title($menu["items.{$route}.title"]);

            // Remove fields stored in Grav.
            if (isset($menu["items.{$route}"])) {
                unset($menu["items.{$route}.type"], $menu["items.{$route}.link"], $menu["items.{$route}.title"]);
            }
        }

        foreach ($list as $page) {
            $page->save(true);
        }

        foreach ($menu['items'] as $key => $item) {
            // Do not save default values.
            foreach ($defaults as $var => $value) {
                if (isset($item[$var]) && $item[$var] == $value) {
                    unset($item[$var]);
                }
            }

            // Do not save derived values.
            unset($item['path'], $item['alias'], $item['parent_id'], $item['level'], $item['group'], $item['current']);

            // Particles have no link.
            if (isset($item['type']) && $item['type'] === 'particle') {
                unset($item['link']);
            }

            if ($item) {
                $event->menu["items.{$key}"] = $item;
            } else {
                unset($menu["items.{$key}"]);
            }
        }
    }

    protected function flattenOrdering(array $ordering, $parents = [], &$i = 0)
    {
        $list = [];
        $group = isset($ordering[0]);
        foreach ($ordering as $id => $children) {
            $tree = $parents;
            if (!$group && !preg_match('/^(__particle|__widget)/', $id)) {
                $tree[] = $id;
                $name = implode('/', $tree);
                $list[$name] = ++$i;
            }
            if (is_array($children)) {
                $ni = $group ? $i : 0;
                $list += $this->flattenOrdering($children, $tree, $ni);
                if ($group) {
                    $i = $ni;
                }
            }
        }

        return $list;
    }
}
