<?php
namespace Gantry\Component\Menu;

use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;
use Gantry\Component\Gantry\GantryTrait;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccessWithGetters;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

abstract class AbstractMenu implements \ArrayAccess, \Iterator
{
    use GantryTrait, ArrayAccessWithGetters, Iterator, Export;

    protected $default;
    protected $base;
    protected $active;
    protected $params;
    protected $override = false;
    private $config;

    /**
     * @var array|Item[]
     */
    protected $items;

    protected $defaults = [
        'menu' => 'mainmenu',
        'base' => '/',
        'startLevel' => 1,
        'endLevel' => 0,
        'showAllChildren' => true,
        'highlightAlias' => true,
        'highlightParentAlias' => true,
        'window_open' => null
    ];

    abstract public function __construct();

    /**
     * Return list of menus.
     *
     * @return array
     */
    abstract public function getMenus();

    /**
     * Return default menu.
     *
     * @return string
     */
    abstract public function getDefaultMenuName();

    public function instance(array $params = [], Config $menu = null)
    {
        if (!isset($params['config'])) {
            $params = $this->defaults;
        }   else {
            $params = $params['config'] + $this->defaults;
        }

        $menus = $this->getMenus();

        if ($params['menu'] === null) {
            $params['menu'] = $this->getDefaultMenuName();
        }
        if (!in_array($params['menu'], $menus)) {
            throw new \RuntimeException('Menu not found', 404);
        }

        $instance = clone $this;
        $instance->params = $params;

        if ($menu) {
            $instance->override = true;
            $instance->config = $menu;
        }

        $instance->getList($params);

        return $instance;
    }

    /**
     * Get menu configuration.
     *
     * @return Config
     */
    public function config()
    {
        if (!$this->config) {
            $gantry = static::gantry();

            /** @var UniformResourceLocator $locator */
            $locator = $gantry['locator'];

            $menu = $this->params['menu'];

            $this->config = new Config(CompiledYamlFile::instance($locator("gantry-config://menu/{$menu}.yaml"))->content());
        }

        return $this->config;
    }

    public function name()
    {
        return $this->params['menu'];
    }

    public function root()
    {
        return $this->offsetGet('');
    }

    public function ordering()
    {
        $list = [];
        foreach ($this->items as $name => $item) {
            $groups = $item->groups();
            if (count($groups) == 1 && empty($groups[0])) {
                continue;
            }

            $list[$name] = [];
            foreach ($groups as $col => $children) {
                $list[$name][$col] = [];
                foreach ($children as $child) {
                    $list[$name][$col][] = $child->path;
                }
            }
        }

        return $list;
    }

    public function items()
    {
        $list = [];
        foreach ($this->items as $key => $item) {
            if ($key !== '') {
                $list[$item->path] = $item->toArray();
            }
        }

        return $list;
    }

    public function settings()
    {
        return (array) $this->config()->get('settings');
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
        if ($item->path && strpos($this->base, $item->path) === 0) {
            return true;
        }

        return false;
    }

    public function isCurrent($item)
    {
        return $item->path == $this->getActive()->path;
    }

    public function add(Item $item)
    {
        $this->items[$item->path] = $item;

        // Assign menu item to its parent.
        if (isset($this->items[$item->parent_id])) {
            $this->items[$item->parent_id]->addChild($item);
        } else {
            throw new \RuntimeException('Internal menu structure error');
        }

        return $this;
    }

    /**
     * Get menu items from the platform.
     *
     * @param int $levels
     * @return array
     */
    abstract protected function getItemsFromPlatform($levels);

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
    abstract protected function calcBase($path);

    /**
     * Get a list of the menu items.
     *
     * @param  array  $params
     */
    abstract protected function getList(array $params);

    /**
     * @param array $ordering
     * @param string $path
     */
    protected function sortAll(array &$ordering = null, $path = '')
    {
        if (!$ordering) {
            $config = $this->config();
            $ordering = $config['ordering'] ? $config['ordering'] : [];
        }

        if (!isset($this->items[$path]) || !$this->items[$path]->hasChildren()) {
            return;
        }

        $item = $this->items[$path];
        if ($this->isAssoc($ordering)) {
            $item->sortChildren($ordering);

            foreach ($ordering as $key => &$value) {
                if (is_array($value)) {
                    $this->sortAll($value, $path ? $path . '/' . $key : $key);
                }
            }
        } else {
            $item->groupChildren($ordering);

            foreach ($ordering as &$group) {
                foreach ($group as $key => &$value) {
                    if (is_array($value)) {
                        $this->sortAll($value, $path ? $path . '/' . $key : $key);
                    }
                }
            }
        }

    }

    protected function isAssoc(array $array)
    {
        return (array_values($array) !== $array);
    }
}
