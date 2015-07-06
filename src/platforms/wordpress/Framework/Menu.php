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

        $this->default = reset($this->menus);
//        $this->active  = $this->getActive();
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

            // Always have a menu
            if(empty($list)) $list[] = 'main-menu';
        }

        return $list;
    }

    /**
     * Return default menu.
     *
     * @return string
     */
    public function getDefaultMenuName()
    {
        return $this->default;
    }

    /**
     * Return active menu.
     *
     * @return string
     */
    public function getActiveMenuName()
    {
        return $this->default;
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

        if($menu) {
            return $menu->items;
        }

        return null;
    }

    // FIXME is this needed or even working ?
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

        $menuItems = $this->getItemsFromPlatform($params);
        if($menuItems === null) return;

        // FIXME
//        $itemMap = [];
//        foreach ($items as $path => &$itemRef) {
//            if (isset($itemRef['id']) && is_numeric($itemRef['id'])) {
//                $itemMap[$itemRef['id']] = &$itemRef;
//            }
//        }

        // Get base menu item for this menu (defaults to active menu item).
        $this->base = $this->calcBase($params['base']);

        foreach ($menuItems as $menuItem) {
            $parent = $menuItem->menu_item_parent;

//            if (($start && $start > $menuItem->level+1)
//                || ($end && $menuItem->level+1 > $end)
//                || ($start > 1 && !in_array($tree[$start - 2], $path))) {
//                continue;
//            }

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

            $item = new Item($this, $menuItem->id, $itemParams);
            $this->add($item);

            // Placeholder page.
            if ($item->type == 'custom' && (trim($item->url) == '#' || trim($item->url) == '')) {
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
