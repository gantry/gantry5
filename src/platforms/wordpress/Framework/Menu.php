<?php
namespace Gantry\Framework;

use Gantry\Component\Gantry\GantryTrait;
use Gantry\Component\Menu\AbstractMenu;
use Gantry\Component\Menu\Item;

class Menu extends AbstractMenu
{
    use GantryTrait;

    protected $menus;

    public function __construct()
    {
        $this->menus = $this->getMenus();
    }

    /**
     * Return list of menus.
     *
     * @return array
     */
    public function getMenus($args = [])
    {
        static $list;

        if($list === null) {
            $defaults = [
                'orderby' => 'name'
            ];

            $args = wp_parse_args($args, $defaults);
            $get_menus = wp_get_nav_menus(apply_filters('g5_menu_get_menus_args', $args));

            foreach($get_menus as $menu) {
                $list[] = $menu->slug;
            }
        }

        return $list;
    }

    /**
     * Get menu items from the platform.
     *
     * @param int $levels
     * @return array    List of routes to the pages.
     */
    protected function getItemsFromPlatform($params)
    {
        $menu = new \TimberMenu($params['menu']);

        if ($menu) {
            return $menu->get_items();
        }

        return null;
    }

    public function isActive($item) {
//        if($item->current)
//            return true;

        return false;
    }

    public function isCurrent($item)
    {
        return $item->current;
    }

    /**
     * Get base menu item.
     *
     * If itemid is not specified or does not exist, return active menu item.
     * If there is no active menu item, fall back to home page for the current language.
     * If there is no home page, return null.
     *
     * @param   int  $itemid
     *
     * @return  object|null
     */
    protected function calcBase($itemid = null)
    {
        // Use active menu item or fall back to default menu item.
        $base = $this->active ?: $this->default;

        // Return base menu item.
        return $base;
    }

    protected function buildList($menuItems, $tree = [])
    {
        $list = [];

        if (!$menuItems) {
            return $list;
        }

        foreach ($menuItems as $menuItem) {
            $menuItem->level = count($tree) + 1;
            $menuItem->tree = array_merge($tree, [$menuItem->id]);
            $menuItem->path = implode('/', $menuItem->tree);
            $list[] = $menuItem;

            if ($menuItem->children) {
                $list = array_merge($list, $this->buildList($menuItem->children, $menuItem->tree));
            }
        }

        return $list;
    }

    /**
     * Get a list of the menu items.
     *
     * Logic has been mostly copied from Joomla 3.4 mod_menu/helper.php (joomla-cms/staging, 2014-11-12).
     * We should keep the contents of the function similar to Joomla in order to review it against any changes.
     *
     * @param  array  $params
     * @param  array  $items
     */
    public function getList(array $params, array $items)
    {
        $start   = $params['startLevel'];
        $end     = $params['endLevel'];

        $menuItems = $this->buildList($this->getItemsFromPlatform($params));
        if($menuItems === null) return;

        $itemMap = [];
        foreach ($items as $path => &$itemRef) {
            if (isset($itemRef['id']) && is_numeric($itemRef['id'])) {
                $itemMap[$itemRef['id']] = &$itemRef;
            }
        }

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        foreach ($menuItems as $menuItem) {
            $parent = $menuItem->menu_item_parent;

            // TODO: Path is menu path to the current page..
            $tree = [];

            if (($start && $start > $menuItem->level+1)
                || ($end && $menuItem->level+1 > $end)
                || ($start > 1 && !in_array($menuItem->tree[$start - 2], $tree))) {
                continue;
            }

            // These params always come from WordPress.
            $itemParams = [
                'id' => $menuItem->id,
                'type' => $menuItem->type,
                'alias' => $menuItem->title(),
                'link' => $menuItem->link(),
                'attr_title' => $menuItem->attr_title,
                'xfn' => $menuItem->xfn,
                'parent_id' => $menuItem->menu_item_parent,
                'current'   => $menuItem->current
            ];

            // Rest of the items will come from saved configuration.
            if (isset($itemMap[$menuItem->id])) {
                // ID found, use it.
                $itemParams += $itemMap[$menuItem->id];
            }

            // And if not available in configuration, default to WordPress.
            $itemParams += [
                'title' => $menuItem->title(),
                'target' => $menuItem->target ?: '_self'
            ];

            $item = new Item($this, $menuItem->path, $itemParams);
            $this->add($item);

            // Placeholder page.
            if ($item->type == 'custom' && (trim($item->link) == '#' || trim($item->link) == '')) {
                $item->type = 'separator';
            }

            switch ($item->type) {
                case 'separator':
                    // Separator and heading have no link.
                    $item->url(null);
                    break;

                case 'custom':
                    $item->url($item->link);
                    break;

                default:
                    $item->url($item->link);
                    break;
            }
        }
    }

}
