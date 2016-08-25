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
    
    protected $_assignments;

    public function assignments($assignments = null)
    {
        if (is_array($assignments)) {
            $this->_assignments = array_map('intval', array_values($assignments));

        } elseif (!isset($this->_assignments)) {
            $db = \JFactory::getDbo();
            $query = $db->getQuery(true);
            $query->select('menuid')->from('#__modules_menu')->where('moduleid = ' . $this->id);
            $db->setQuery($query);

            $this->_assignments = array_map('intval', (array) $db->loadColumn());
        }
        
        return $this->_assignments;
    }

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
            'id' => $this->id,
            'title' => $this->title,
            'note' => $this->note ?: null,
            'position' => $this->position,
            'ordering' => (int) $this->ordering,
            'type' => $particle ? 'gantry.particle' : 'joomla.' . $this->module,
            'published' => (bool) $this->published,
            'show_title' => (bool) $this->showtitle,
            'particle' => null,
            'content' => $this->content ?: null,
            'joomla' => &$params,
            'language' => $this->language !== '*' ? $this->language : null,
            'assignments' => $this->assignments()
        ];

        if ($particle && !empty($params['particle'])) {
            $array['particle'] = json_decode($params['particle'], true);
            unset($params['particle']);
        }

        return array_filter($array, [$this, 'is_not_null']);
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
            'note' => isset($array['note']) ? $array['note'] : '',
            'position' => $array['position'],
            'ordering' => (int) $array['ordering'],
            'module' => $type,
            'published' => (int) !empty($array['published']),
            'show_title' => (int) !empty($array['show_title']),
            'params' => isset($array['joomla']) ? json_decode(json_encode($array['joomla'])) : [],
            'content' => isset($array['content']) ? $array['content'] : '',
            'language' => isset($array['language']) ? $array['language'] : '*',
            '_assignments' => isset($array['assignments']) ? $array['assignments'] : [],
        ];

        $object = new static();
        $object->bind($properties);

        return $object;
    }

    public function render($file)
    {
        return Gantry::instance()['theme']->render($file, ['particle' => $this]);
    }

    public function compile($string)
    {
        return Gantry::instance()['theme']->compile($string, ['particle' => $this]);
    }

    // Internal functions

    /**
     * @param $val
     * @return bool
     * @internal
     */
    public function is_not_null($val)
    {
        return !is_null($val);
    }

    static protected function collection($items)
    {
        return new ModuleCollection($items);
    }
}
