<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2021 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Module;

use Gantry\Framework\Gantry;
use Gantry\Framework\Theme;
use Gantry\Joomla\Object\AbstractObject;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseInterface;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;

/**
 * Class Module
 * @package Gantry\Joomla\Module
 *
 * @property $module
 * @property $params
 * @property $position
 * @property $ordering
 * @property $title
 * @property $showtitle
 * @property $note
 * @property $published
 * @property $content
 * @property $language
 */
class Module extends AbstractObject implements ExportInterface
{
    use Export;

    /** @var array */
    protected static $instances = [];

    /** @var string */
    protected static $table = 'Module';

    /** @var string */
    protected static $order = 'id';

    /** @var array */
    protected $assignments;

    /**
     * @param int[]|null $assignments
     * @return array
     */
    public function assignments($assignments = null)
    {
        if (is_array($assignments)) {
            $this->assignments = array_map('intval', array_values($assignments));
        } elseif (!isset($this->assignments)) {
            $db    = Factory::getContainer()->get(DatabaseInterface::class);
            $query = $db->createQuery();

            $query->select('menuid')->from('#__modules_menu')->where('moduleid = ' . $this->id);
            $db->setQuery($query);

            $this->assignments = array_map('intval', (array) $db->loadColumn());
        }

        return $this->assignments;
    }

    /**
     * @return bool
     */
    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->params = json_decode($this->params, false);

        return true;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $particle = $this->module === 'mod_gantry5_particle';

        // Convert params to array.
        $params = json_decode(json_encode($this->params), true);

        $array = [
            'id' => $this->id,
            'position' => $this->position,
            'ordering' => (int) $this->ordering,
            'type' => $particle ? 'particle' : 'joomla',
            'title' => $this->title,
            'chrome' => [
                'display_title' => (bool) $this->showtitle,
                'class' => !empty($params['moduleclass_sfx']) ? $params['moduleclass_sfx'] : ''
            ],
            'options' => null,
            'assignments' => $this->assignments()
        ];

        $options = array_filter(
            [
                'type' => !$particle ? $this->module : null,
                'note' => $this->note ?: null,
                'published' => (bool) $this->published,
                'content' => $this->content ?: null,
                'params' => &$params,
                'language' => $this->language !== '*' ? $this->language : null,
            ],
            [$this, 'is_not_null']
        );

        if ($particle) {
            $array['joomla'] = $options;
            $options = !empty($params['particle']) ? json_decode($params['particle'], true) : [];
            $options['type'] = $options['particle'] ?? null;
            $options['attributes'] = $options['options']['particle'] ?? [];

            unset($options['particle'], $options['options']);

            $array['options'] = $options;

            unset($params['particle']);
        } else {
            $array['options'] = $options;
        }

        return array_filter($array, [$this, 'is_not_null']);
    }

    public function exportSql()
    {
        $assignments = $this->assignments();
        foreach ($assignments as &$assignment) {
            $assignment = "(@module_id, $assignment)";
        }
        unset($assignment);

        $out = $this->getCreateSql(['id', 'asset_id', 'checked_out', 'checked_out_time', 'publish_up', 'publish_down']) . ';';
        $out .= "\nSELECT @module_id := LAST_INSERT_ID();\n";
        $out .= 'INSERT INTO `#__modules_menu` (`moduleid`, `menuid`) VALUES' . "\n";
        $out .= implode(",\n", $assignments) . ';';

        return $out;
    }

    /**
     * @param array $array
     * @return Module|null
     */
    public function create($array)
    {
        $type = $array['type'];

        if ($type === 'particle') {
            $particle = $array['options'] ?? [];
            $array['options'] = $array['joomla'] ?? [];
            $array['options']['type'] = 'mod_gantry5_particle';
            $array['options']['params']['particle'] = $particle;
        } elseif ($type !== 'joomla') {
            return null;
        }

        $options = $array['options'];

        $properties = [
            'title'        => $array['title'],
            'note'         => $options['note'] ?? '',
            'content'      => $options['content'] ?? '',
            'position'     => $array['position'],
            'ordering'     => (int) $array['ordering'],
            'published'    => (int) !empty($options['published']),
            'module'       => $options['type'],
            'showtitle'    => (int) !empty($array['chrome']['display_title']),
            'params'       => isset($options['params']) ? json_decode(json_encode($options['params']), false) : [],
            'language'     => $options['language'] ?? '*',
            '_assignments' => $array['assignments'] ?? [],
        ];

        $object = new static();
        $object->bind($properties);

        return $object;
    }

    /**
     * @param string $file
     * @return string
     */
    public function render($file)
    {
        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        return $theme->render($file, ['particle' => $this]);
    }

    /**
     * @param string $string
     * @return string
     */
    public function compile($string)
    {
        $gantry = Gantry::instance();

        /** @var Theme $theme */
        $theme = $gantry['theme'];

        return $theme->compile($string, ['particle' => $this]);
    }

    // Internal functions

    /**
     * @param mixed $val
     * @return bool
     * @internal
     */
    public function is_not_null($val)
    {
        return null !== $val;
    }

    /**
     * @param array $items
     * @return ModuleCollection
     */
    protected static function collection($items)
    {
        return new ModuleCollection($items);
    }
}
