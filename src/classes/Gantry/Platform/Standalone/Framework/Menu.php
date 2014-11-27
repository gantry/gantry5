<?php
namespace Gantry\Framework;

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
        if (strpos($item->id, $this->getBase()->id) === 0) {
            return true;
        }

        return false;
    }

    public function isCurrent($item)
    {
        return $item->id == $this->getActive()->id;
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
        if (!is_file(STANDALONE_ROOT . "/{$path}.html.twig")) {
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
    protected function getList(array $params)
    {
        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        // Make sure that the menu item exists.
        if (!$this->base) {
            return [];
        }

        $path    = $this->base;
        $start   = $params['startLevel'];
        $end     = $params['endLevel'];
        $showAll = $params['showAllChildren'];

        $params = [
            'levels' => $end - $start,
            'pattern' => '|\.html\.twig|',
            'filters' => ['value' => '|\.html\.twig|']
        ];

        $menuItems = Folder::all(STANDALONE_ROOT . '/pages/' . dirname($path), $params);

        $all = $tree = [];
        foreach ($menuItems as $item) {
            $level = substr_count($item, '/') + 1;
            if (($start && $start > $level)
                || ($end && $level > $end)
                || (!$showAll && $level > 1 && strpos(dirname($item), $path) !== 0)
                || ($start > 1 && strpos(dirname(dirname($item)), $path) !== 0)
                || ($item[0] == '_' || strpos($item, '_'))) {
                continue;
            }

            $item = (object) [
                'id' => $item,
                'type' => 'default',
                'link' => $item,
                'parent' => dirname($item) ?: ($item != 'home' ? 'home' : null),
                'children' => [],
                'active' => false,
                'title' => ucfirst(basename($item)),
                'browserNav' => 0,
                'params' => []
            ];

            switch ($item->type) {
                case 'separator':
                case 'heading':
                    // Separator and heading has no link.
                    $link = null;
                    break;

                case 'url':
                    $link = $item->link;
                    break;

                case 'alias':
                    // If this is an alias use the item id stored in the parameters to make the link.
                    $link = STANDALONE_URI . '/' . THEME . '/' . $item->params['alias'];
                    break;

                default:
                    $link = STANDALONE_URI . '/' . THEME . ($item->link != 'home' ? '/' . $item->link : '');
            }

            $item->link = $link;

            // We prevent the double encoding because for some reason the $item is shared for menu modules and we get double encoding
            // when the cause of that is found the argument should be removed
            $item->title        = htmlspecialchars($item->title, ENT_COMPAT, 'UTF-8', false);
            $item->anchor_css   = ''; //htmlspecialchars($item->params->get('menu-anchor_css', ''), ENT_COMPAT, 'UTF-8', false);
            $item->anchor_title = ''; //htmlspecialchars($item->params->get('menu-anchor_title', ''), ENT_COMPAT, 'UTF-8', false);
            $item->menu_image   = ''; //$item->params->get('menu_image', '') ? htmlspecialchars($item->params->get('menu_image', ''), ENT_COMPAT, 'UTF-8', false) : '';
            $item->menu_text    = true; //(bool) $item->params->get('menu_text', true);

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
                $all[$item->parent]->children[] = $item;
            } else {
                $tree[$item->id] = $item;
            }
            $all[$item->id] = $item;

        }

        return $tree;
    }
}
