<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Framework;

use Gantry\Component\Layout\Layout;
use Gantry\Framework\Services\ConfigServiceProvider;
use Gantry\Joomla\Module\ModuleFinder;
use Gantry\Joomla\StyleHelper;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Exporter
{
    public function all()
    {
        return [
            'outlines' => $this->outlines(),
            'positions' => $this->positions(),
            'menus' => $this->menus()
        ];
    }

    public function positions($all = true)
    {
        $gantry = Gantry::instance();
        $positions = $gantry['outlines']->positions();
        $positions['debug'] = 'Debug';

        $finder = new ModuleFinder();
        if (!$all) {
            $finder->particle();
        }
        $modules = $finder->find()->export();
        $list = [];
        foreach ($modules as $position => $items) {
            $list[$position] = [
                'title' => $positions[$position],
                'items' => $items,
            ];
        }

        return $list;
    }

    public function outlines()
    {
        $gantry = Gantry::instance();
        $styles = StyleHelper::loadStyles($gantry['theme.name']);

        $list = [
            'default' => ['title' => 'Default'],
            '_error' => ['title' => 'Error'],
            '_offline' => ['title' => 'Offline'],
            '_body_only' => ['title' => 'Body Only'],
        ];
        $inheritance = [];

        foreach ($styles as $style) {
            $name = $base = strtolower(trim(preg_replace('|[^a-z\d_-]+|ui', '_', $style->title), '_'));
            $i = 0;
            while (isset($list[$name])) {
                $i++;
                $name = "{$base}-{$i}";
            };
            $inheritance[$style->id] = $name;
            $list[$name] = [
                'id' => (int) $style->id,
                'title' => $style->title,
                'home' => $style->home,
            ];
            if (!$style->home) {
                unset($list[$name]['home']);
            }
        }

        foreach ($list as $name => &$style) {
            $id = isset($style['id']) ? $style['id'] : $name;
            $config = ConfigServiceProvider::load($gantry, $id, false, false);

            // Update layout inheritance.
            $layout = Layout::instance($id);
            $layout->name = $name;
            foreach ($inheritance as $from => $to) {
                $layout->updateInheritance($from, $to);
            }
            $style['preset'] = $layout->preset['name'];
            $config['index'] = $layout->buildIndex();
            $config['layout'] = $layout->export();

            // Update atom inheritance.
            $atoms = $config->get('page.head.atoms');
            if (is_array($atoms)) {
                $atoms = new Atoms($atoms);
                foreach ($inheritance as $from => $to) {
                    $atoms->updateInheritance($from, $to);
                }
                $config->set('page.head.atoms', $atoms->update()->toArray());
            }

            // Add assignments.
            if (is_numeric($id)) {
                $assignments = $this->getOutlineAssignments($id);
                if ($assignments) {
                    $config->set('assignments', $this->getOutlineAssignments($id));
                }
            }
            
            $style['config'] = $config->toArray();
        }

        return $list;
    }

    public function menus()
    {
        $gantry = Gantry::instance();
        $db = \JFactory::getDbo();

        $query = $db->getQuery(true)
            ->select('id, menutype, title, description')
            ->from('#__menu_types');
        $db->setQuery($query);
        $menus = $db->loadObjectList('id');

        $list = [];
        foreach ($menus as $menu) {
            $items = $gantry['menu']->instance(['menu' => $menu->menutype])->items(false);

            array_walk(
                $items,
                function (&$item) {
                    $item['id'] = (int) $item['id'];
                    if (in_array($item['type'], ['component', 'alias'])) {
                        $item['type'] = "joomla.{$item['type']}";
                    }

                    unset($item['alias'], $item['path'], $item['parent_id'], $item['level']);
                }
            );

            $list[$menu->menutype] = [
                'id' => (int) $menu->id,
                'title' => $menu->title,
                'description' => $menu->description,
                'items' => $items
            ];
        }

        return $list;
    }

    /**
     * List all the rules available.
     *
     * @param string $configuration
     * @return array
     */
    public function getOutlineAssignments($configuration)
    {
        require_once JPATH_ADMINISTRATOR . '/components/com_menus/helpers/menus.php';
        $app = \JApplicationCms::getInstance('site');
        $menu = $app->getMenu();
        $data = \MenusHelper::getMenuLinks();

        $items = [];
        foreach ($data as $item) {
            foreach ($item->links as $link) {
                if ($link->template_style_id == $configuration) {
                    $items[$menu->getItem($link->value)->route] = 1;
                }
            }
        }

        if ($items) {
            return ['menu' => [$items]];
        }

        return [];
    }
}
