<?php
namespace Gantry\Framework;

use Gantry\Component\Config\Config;
use Gantry\Component\Filesystem\Folder;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccess;
use RocketTheme\Toolbox\ArrayTraits\Iterator;

class Menu implements \ArrayAccess, \Iterator
{
    use ArrayAccess, Iterator;

    protected $app;

    protected $default;
    protected $base;
    protected $active;
    protected $params;

    /**
     * @var array
     */
    protected $items;

    protected $defaults = [
        'menu' => null,
        'base' => 0,
        'startLevel' => 1,
        'endLevel' => 0,
        'showAllChildren' => false,
        'highlightAlias' => true,
        'highlightParentAlias' => true,
        'window_open' => null
    ];

    public function __construct()
    {
        $this->default = 'home';
        $this->active  = PAGE_PATH;
    }

    public function instance(array $params = null)
    {
        $params = $params ?: [];
        $params += $this->defaults;

        $instance = clone $this;
        $instance->params = $params;

        $instance->items = $instance->getList($params);

        return $instance;
    }

    /**
     * @return object
     */
    public function getBase()
    {
        return $this->offsetGet($this->base);
    }

    /**
     * @return object
     */
    public function getDefault()
    {
        return $this->offsetGet($this->default);
    }

    /**
     * @return object
     */
    public function getActive()
    {
        return $this->offsetGet($this->active);
    }

    public function isActive($item)
    {
        if (strpos($this->base, $item->path) === 0) {
            return true;
        }

        return false;
    }

    public function isCurrent($item)
    {
        return $item->path == $this->getActive()->path;
    }

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   string  $path
     *
     * @return  string
     */
    protected function calcBase($path)
    {
        if (!$path || !is_file(PRIME_ROOT . "/pages/{$path}.html.twig")) {
            // Use active menu item or fall back to default menu item.
            $path = $this->active ?: $this->default;
        }

        // Return base menu item.
        return $path;
    }

    /**
     * Get a list of the menu items.
     *
     * Logic has been mostly copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
     * We should keep the contents of the function similar to Joomla in order to review it against any changes.
     *
     * @param  array  $params
     *
     * @return array
     */
    protected function getList(array $config)
    {
        $items = (array) isset($config['items']) ? $config['items'] : null;
        $params = (array) isset($config['config']) ? $config['config'] : null;
        $ordering = new Config((array) isset($config['ordering']) ? $config['ordering'] : []);

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        $path    = $this->base;
        $start   = $params['startLevel'];
        $end     = $params['endLevel'];
        $showAll = $params['showAllChildren'];

        $options = [
            'levels' => $end - $start,
            'pattern' => '|\.html\.twig|',
            'filters' => ['value' => '|\.html\.twig|']
        ];

        $folder = PRIME_ROOT . '/pages';
        if (!is_dir($folder)) {
            return [];
        }
        $menuItems = Folder::all($folder, $options);

        $all = $tree = [];
        foreach ($menuItems as $name) {
            $level = substr_count($name, '/') + 1;
            if (($start && $start > $level)
                || ($end && $level > $end)
                || (!$showAll && $level > 1 && strpos(dirname($name), $path) !== 0)
                || ($start > 1 && strpos(dirname(dirname($name)), $path) !== 0)
                || ($name[0] == '_' || strpos($name, '_'))) {
                continue;
            }

            $item = isset($items[$name]) ? $items[$name] : [];
            $item += [
                'id' => preg_replace('|[^a-z0-9]|i', '-', $name),
                'type' => 'link',
                'path' => $name,
                'title' => ucfirst(basename($name)),
                'link' => $name != 'home' ? $name : '',
                'parent' => dirname($name) != '.' ? dirname($name) : '',
                'children' => [],
                'browserNav' => 0,
                'menu_text' => true
            ];

            $item = (object) $item;

            // Placeholder page.
            if ($item->type == 'link' && !is_file(PRIME_ROOT . "/pages/{$item->path}.html.twig")) {
                $item->type = 'separator';
            }

            switch ($item->type) {
                case 'separator':
                case 'heading':
                    // Separator and heading has no link.
                    $item->link = null;
                    break;

                case 'url':
                    break;

                case 'alias':
                default:
                    $item->link = '/' . trim(PRIME_URI . '/' . THEME . '/' . $item->link, '/');
            }

            switch ($item->browserNav)
            {
                default:
                case 0:
                    // Target window: Parent.
                    $item->anchor_attributes = '';
                    break;
                case 1:
                    // Target window: New with navigation.
                    $item->anchor_attributes = ' target="_blank"';
                    break;
                case 2:
                    // Target window: New without navigation.
                    $item->anchor_attributes = ' onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes' . ($params['window_open'] ? ',' . $params['window_open'] : '') . '\');return false;"';
                    break;
            }

            // Build nested tree structure.
            if (isset($all[$item->parent])) {
                $all[$item->parent]->children[$item->path] = $item;
            } else {
                $tree[$item->path] = $item;
            }
            $all[$item->path] = $item;
        }

        foreach ($all as $item) {
            $item->children = $this->sort($item->children, $ordering->get($item->path, null, '/'), $item->path . '/');

        }
        $tree = $this->sort($tree, $ordering->toArray());

        return $tree;
    }

    protected function sort(array $items, $ordering, $path = '')
    {
        if (!$ordering) {
            return $items;
        }
        $list = [];
        foreach ($ordering as $key => $value) {
            if (isset($items[$path . $key])) {
                $list[$path . $key] = $items[$path . $key];
            }
        }
        return $list + $items;
    }
}
