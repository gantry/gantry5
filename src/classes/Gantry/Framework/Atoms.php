<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   Dual License: MIT or GNU/GPLv2 and later
 *
 * http://opensource.org/licenses/MIT
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Gantry Framework code that extends GPL code is considered GNU/GPLv2 and later
 */

namespace Gantry\Framework;

use Gantry\Component\Config\BlueprintForm;
use Gantry\Component\Config\Config;
use Gantry\Component\File\CompiledYamlFile;
use RocketTheme\Toolbox\ArrayTraits\ArrayAccess;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;
use RocketTheme\Toolbox\ArrayTraits\Iterator;
use RocketTheme\Toolbox\ResourceLocator\UniformResourceLocator;

class Atoms implements \ArrayAccess, \Iterator, ExportInterface
{
    use ArrayAccess, Iterator, Export;

    /**
     * @var  string
     */
    protected $name;

    /**
     * @var array
     */
    protected $items;

    /**
     * @var array
     */
    protected $ids;

    /**
     * @var array|static[]
     */
    protected static $instances;

    protected $inherit = false;

    /**
     * @param string $outline
     * @return static
     */
    public static function instance($outline)
    {
        if (!isset(static::$instances[$outline])) {
            $file = CompiledYamlFile::instance("gantry-theme://config/{$outline}/page/head.yaml");
            $head = $file->content();
            static::$instances[$outline] = new static(isset($head['atoms']) ? $head['atoms'] : [], $outline);
            $file->free();

            static::$instances[$outline]->init();
        }

        return static::$instances[$outline];
    }

    /**
     * Atoms constructor.
     * @param array $atoms
     * @param string $name
     */
    public function __construct(array $atoms = [], $name = null)
    {
        $this->name = $name;
        $this->items = array_filter($atoms);
        $this->inherit = file_exists('gantry-admin://blueprints/layout/inheritance/atom.yaml');

        foreach ($this->items as &$item) {
            if (!empty($item['id'])) {
                $this->ids[$item['id']] = $item;
            }
        }
    }

    public function init()
    {
        foreach ($this->items as &$item) {
            if (!empty($item['inherit']['outline']) && !empty($item['inherit']['atom'])) {
                $inherited = static::instance($item['inherit']['outline']);
                $test = $inherited->id($item['inherit']['atom']);
                if (isset($test['attributes'])) {
                    $item['attributes'] = $test['attributes'];
                } else {
                    unset($item['inherit']);
                }
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function update()
    {
        foreach ($this->items as &$item) {
            if (empty($item['id'])) {
                $item['id'] = $this->createId($item);
            }
            if (!empty($item['inherit']['outline']) && !empty($item['inherit']['atom'])) {
                unset($item['attributes']);
            } else {
                unset($item['inherit']);
            }
        }

        return $this;
    }

    /**
     * @param string $outline
     * @return $this
     */
    public function inheritAll($outline)
    {
        foreach ($this->items as &$item) {
            if (!empty($item['id'])) {
                $item['inherit'] = [
                    'outline' => $outline,
                    'atom' => $item['id'],
                    'include' => ['attributes']
                ];
            }
        }

        return $this;
    }

    /**
     * @param string $old
     * @param string $new
     * @param array  $ids
     * @return $this
     */
    public function updateInheritance($old, $new = null, $ids = null)
    {
        $this->init();

        foreach ($this->items as &$item) {
            if (!empty($item['inherit']['outline']) && $item['inherit']['outline'] == $old && isset($item['inherit']['atom'])) {
                if ($new && ($ids === null || isset($ids[$item['inherit']['atom']]))) {
                    $item['inherit']['outline'] = $new;
                } else {
                    unset($item['inherit']);
                }
            }
        }

        return $this;
    }

    public function save()
    {
        if ($this->name) {
            /** @var UniformResourceLocator $locator */
            $locator = Gantry::instance()['locator'];

            $loadPath = $locator->findResource("gantry-theme://config/{$this->name}/page/head.yaml");
            $savePath = $locator->findResource("gantry-theme://config/{$this->name}/page/head.yaml", true, true);

            if ($loadPath && $savePath) {
                $file = CompiledYamlFile::instance($loadPath);
                $head = $file->content();
                $head['atoms'] = $this->update()->toArray();
                $file->free();

                $file = CompiledYamlFile::instance($savePath);
                $file->save($head);
                $file->free();
            }
        }
    }

    /**
     * @param string $id
     * @return array
     */
    public function id($id)
    {
        return isset($this->ids[$id]) ? $this->ids[$id] : [];
    }

    /**
     * @param string $type
     * @return array
     */
    public function type($type)
    {
        $list = [];
        foreach ($this->items as $item) {
            if ($item['type'] === $type) {
                $list[] = $item;
            }
        }

        return $list;
    }

    /**
     * @param string $type
     * @param array $data
     * @return Config
     */
    public function createAtom($type, array $data = [])
    {
        $self = $this;

        $callable = function () use ($self, $type) {
            return $self->getBlueprint($type);
        };

        // Create configuration from the data.
        $item = new Config($data, $callable);
        $item->def('id', null);
        $item->def('type', $type);
        if (!isset($item['title'])) {
            $item->def('title', $item->blueprint()->get('name'));
        }
        $item->def('attributes', []);
        $item->def('inherit', []);

        return $item;
    }

    /**
     * @param string $type
     * @return BlueprintForm
     */
    public function getBlueprint($type)
    {
        $blueprint = BlueprintForm::instance($type, 'gantry-blueprints://particles');

        if ($this->inherit) {
            $blueprint->set('form/fields/_inherit', ['type' => 'gantry.inherit']);
        }

        return $blueprint;
    }

    /**
     * @param string $type
     * @param string $id
     * @param bool $force
     * @return BlueprintForm|null
     */
    public function getInheritanceBlueprint($type, $id = null, $force = false)
    {
        if (!$this->inherit) {
            return null;
        }

        $inheriting = $id ? $this->getInheritingOutlines($id) : [];
        $list = $this->getOutlines($type, false);

        if ($force || (empty($inheriting) && $list)) {
            $inheritance = BlueprintForm::instance('layout/inheritance/atom.yaml', 'gantry-admin://blueprints');
            $inheritance->set('form/fields/outline/filter', array_keys($list));
            $inheritance->set('form/fields/atom/atom', $type);

        } elseif (!empty($inheriting)) {
            // Already inherited by other outlines.
            $inheritance = BlueprintForm::instance('layout/inheritance/messages/inherited.yaml', 'gantry-admin://blueprints');
            $inheritance->set(
                'form/fields/_note/content',
                sprintf($inheritance->get('form/fields/_note/content'), 'atom', ' <ul><li>' . implode('</li> <li>', $inheriting) . '</li></ul>')
            );

        } elseif ($this->name === 'default') {
            // Base outline.
            $inheritance = BlueprintForm::instance('layout/inheritance/messages/default.yaml', 'gantry-admin://blueprints');

        } else {
            // Nothing to inherit from.
            $inheritance = BlueprintForm::instance('layout/inheritance/messages/empty.yaml', 'gantry-admin://blueprints');
        }

        return $inheritance;
    }

    /**
     * @param string $id
     * @return array
     */
    public function getInheritingOutlines($id = null)
    {
        /** @var Outlines $outlines */
        $outlines = Gantry::instance()['outlines'];

        return $outlines->getInheritingOutlinesWithAtom($this->name, $id);
    }

    /**
     * @param string $type
     * @param bool $includeInherited
     * @return array
     */
    public function getOutlines($type, $includeInherited = true)
    {
        if ($this->name !== 'default') {
            /** @var Outlines $outlines */
            $outlines = Gantry::instance()['outlines'];

            $list = $outlines->getOutlinesWithAtom($type, $includeInherited);
            unset($list[$this->name]);
        } else {
            $list = [];
        }

        return $list;
    }

    /**
     * @param array $item
     * @return string
     */
    protected function createId(array &$item)
    {
        $type = $item['type'];

        while ($num = rand(1000, 9999)) {
            if (!isset($this->ids["{$type}-{$num}"])) {
                break;
            }
        }

        $id = "{$type}-{$num}";

        $this->ids[$id] = $item;

        return $id;
    }
}
