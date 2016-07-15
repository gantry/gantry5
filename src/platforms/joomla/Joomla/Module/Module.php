<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2016 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Module;

use Gantry\Framework\Base\Gantry;
use Gantry\Joomla\Category\Category;
use Gantry\Joomla\Object\Object;
use RocketTheme\Toolbox\ArrayTraits\Export;
use RocketTheme\Toolbox\ArrayTraits\ExportInterface;

\JTable::addIncludePath(JPATH_LIBRARIES . '/legacy/table/');

class Module extends Object implements ExportInterface
{
    use Export;

    static protected $instances = [];

    static protected $table = 'Module';
    static protected $order = 'id';

    public function initialize()
    {
        if (!parent::initialize()) {
            return false;
        }

        $this->params = json_decode($this->params);

        return true;
    }

    public function toArray()
    {
        $particle = $this->module === 'mod_gantry5_particle';

        // Convert params to array.
        $params = json_decode(json_encode($this->params), true);

        $array = [
            'title' => $this->title,
            'note' => $this->note,
            'position' => $this->position,
            'ordering' => (int) $this->ordering,
            'type' => $particle ? 'gantry.particle' : 'joomla.' . $this->module,
            'published' => (bool) $this->published,
            'show_title' => (bool) $this->showtitle,
            'params' => &$params,
        ];

        if ($particle && !empty($params['particle'])) {
            $array['particle'] = $params['particle'];
            unset($params['particle']);
        }

        if ($this->content) {
            $array['content'] = $this->content;
        }

        if ($this->language !== '*') {
            $array['language'] = $this->language;
        }

        return $array;
    }

    public function create(array $array)
    {
        list ($scope, $type) = explode('.', $array['type'], 2);

        if ($scope === 'gantry' && $type === 'particle') {
            $type = 'mod_gantry5_particle';
            $array['params']['particle'] = isset($array['particle']) ? $array['particle'] : '';

        } elseif ($scope !== 'joomla') {
            return null;
        }

        $properties = [
            'title' => $array['title'],
            'note' => $array['note'],
            'position' => $array['position'],
            'ordering' => $array['ordering'],
            'module' => $type,
            'published' => (int) $array['published'],
            'show_title' => (int) $array['show_title'],
            'params' => json_decode(json_encode($array['params'])),
            'content' => isset($array['content']) ? $array['content'] : '',
            'language' => isset($array['language']) ? $array['language'] : '*',
        ];

        $object = new static();
        $object->bind($properties);

        return $object;
    }

    public function render($file)
    {
        return Gantry::instance()['theme']->render($file, ['article' => $this]);
    }

    public function compile($string)
    {
        return Gantry::instance()['theme']->compile($string, ['article' => $this]);
    }
}
