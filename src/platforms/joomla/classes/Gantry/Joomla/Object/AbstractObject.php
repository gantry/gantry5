<?php
/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2017 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Object;

/**
 * Abstract base class for database objects.
 *
 *
 */
abstract class AbstractObject extends \JObject
{
    /**
     * If you don't have global instance ids, override this in extending class.
     * @var array
     */
    static protected $instances = [];

    /**
     * Override table class in your own class.
     * @var string
     */
    static protected $table;

    /**
     * JTable class prefix, override if needed.
     * @var string
     */
    static protected $tablePrefix = 'JTable';

    /**
     * Override table in your own class.
     * @var string
     */
    static protected $order;

    /**
     * @var int
     */
    public $id;

    /**
     * Is object stored into database?
     * @var boolean
     */
    protected $_exists = false;

    /**
     * Readonly object.
     * @var bool
     */
    protected $_readonly = false;

    /**
     * @var bool
     */
    protected $_initialized = false;

    /**
     * Class constructor, overridden in descendant classes.
     *
     * @param int $identifier Identifier.
     */
    public function __construct($identifier = null)
    {
        parent::__construct();

        if ($identifier) {
            $this->load($identifier);
        }
    }

    /**
     * Override this function if you need to initialize object right after creating it.
     *
     * Can be used for example if the database fields need to be converted to array or JRegistry.
     *
     * @return bool True if initialization was done, false if object was already initialized.
     */
    public function initialize()
    {
        $initialized = $this->_initialized;
        $this->_initialized = true;

        return !$initialized;
    }

    /**
     * Make instance as read only object.
     */
    public function readonly()
    {
        $this->_readonly = true;
    }

    /**
     * Returns the global instance to the object.
     *
     * Note that using array of fields will always make a query to the database, but it's very useful feature if you want to search
     * one item by using arbitrary set of matching fields. If there are more than one matching object, first one gets returned.
     *
     * @param  int|array  $keys        An optional primary key value to load the object by, or an array of fields to match.
     * @param  boolean    $reload      Force object reload from the database.
     *
     * @return  Object
     */
    static public function getInstance($keys = null, $reload = false)
    {
        // If we are creating or loading a new item or we load instance by alternative keys,
        // we need to create a new object.
        if (!$keys || is_array($keys) || !isset(static::$instances[(int) $keys])) {
            $c = get_called_class();
            $instance = new $c($keys);
            /** @var Object $instance */
            if (!$instance->exists()) return $instance;

            // Instance exists: make sure that we return the global instance.
            $keys = $instance->id;
        }

        // Return global instance from the identifier, possibly reloading it first.
        $instance = static::$instances[(int) $keys];
        if ($reload) $instance->load($keys);

        return $instance;
    }

    /**
     * Removes all or selected instances from the object cache.
     *
     * @param null|int|array  $ids
     */
    static public function freeInstances($ids = null)
    {
        if ($ids === null) {
            $ids = array_keys(static::$instances);
        }
        $ids = (array) $ids;

        foreach ($ids as $id) {
            unset(static::$instances[$id]);
        }
    }

    /**
     * Returns true if the object exists in the database.
     *
     * @param   boolean  $exists  Internal parameter to change state.
     *
     * @return  boolean  True if object exists in database.
     */
    public function exists($exists = null)
    {
        $return = $this->_exists;
        if ($exists !== null) $this->_exists = (bool) $exists;
        return $return;
    }

    /**
     * Tests if dynamically defined property has been defined.
     *
     * @param string $property
     * @param bool   $defined
     * @return bool
     */
    public function defined($property, $defined = true)
    {
        $property = '_' . $property;

        return $defined ? isset($this->{$property}) : !isset($this->{$property});
    }

    /**
     * Returns an associative array of object properties.
     *
     * @param   boolean  $public  If true, returns only the public properties.
     *
     * @return  array
     */
    public function getProperties($public = true)
    {
        if ($public) {
            $getProperties = function($obj) { return get_object_vars($obj); };
            return $getProperties($this);
        }

        return get_object_vars($this);
    }

    /**
     * Method to bind an associative array to the instance.
     *
     * This method optionally takes an array of properties to ignore or allow when binding.
     *
     * @param   array    $src     An associative array or object to bind to the JTable instance.
     * @param   array    $fields  An optional array list of properties to ignore / include only while binding.
     * @param   boolean  $include  True to include only listed fields, false to ignore listed fields.
     *
     * @return  boolean  True on success.
     */
    public function bind(array $src = null, array $fields = null, $include = false)
    {
        if (empty($src)) return false;

        if (!empty($fields)) {
            $src = $include ? array_intersect_key($src, array_flip($fields)) : array_diff_key($src, array_flip($fields));
        }
        $this->setProperties ( $src );
        return true;
    }

    /**
     * Method to load object from the database.
     *
     * @param   mixed    $keys   An optional primary key value to load the object by, or an array of fields to match. If not
     *                           set the instance key value is used.
     *
     * @return  boolean  True on success, false if the object doesn't exist.
     */
    public function load($keys = null)
    {
        if ($keys !== null && !is_array($keys)) {
            $keys = array('id'=>(int) $keys);
        }

        // Create the table object.
        $table = static::getTable ();

        // Make sure we set the given keys to the object even if it is not loaded.
        $table->reset();
        if ($keys !== null) $table->bind($keys);

        // Load the object based on the keys.
        $this->_exists = $table->load($keys, false);

        // Work around Joomla 3.1.1 bug on load() returning true if keys didn't exist.
        if ($table->id == 0) $this->_exists = false;

        // Assuming all is well at this point lets bind the data.
        $this->setProperties($table->getProperties());

        if ($this->id) {
            if (!isset(static::$instances[$this->id])) {
                static::$instances[$this->id] = $this;
            }
        }
        $this->initialize();

        return $this->_exists;
    }

    /**
     * Method to save the object to the database.
     *
     * Before saving the object, this method checks if object can be safely saved.
     * It will also trigger onContentBeforeSave and onContentAfterSave events.
     *
     * @return  boolean  True on success.
     */
    public function save()
    {
        // Check the object.
        if ($this->_readonly || !$this->check()) {
            return false;
        }

        $isNew = !$this->_exists;

        // Initialize table object.
        $table = static::getTable ();
        $table->bind($this->getProperties());

        // Check the table object.
        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        // Include the content plugins for the on save events.
        $dispatcher = \JEventDispatcher::getInstance();
        \JPluginHelper::importPlugin('content');

        // Trigger the onContentBeforeSave event.
        $result = $dispatcher->trigger('onContentBeforeSave', array("com_gantry5.".get_called_class(), $table, $isNew));
        if (in_array(false, $result, true)) {
            $this->setError($table->getError());
            return false;
        }

        // Store the data.
        if (!$table->store()) {
            $this->setError($table->getError());
            return false;
        }

        // If item was created, load the object.
        if ($isNew) {
            $this->load($table->id);

            if (!isset(static::$instances[$this->id])) {
                static::$instances[$this->id] = $this;
            }
        }

        // Trigger the onContentAfterSave event.
        $dispatcher->trigger('onContentAfterSave', array("com_gantry5.".get_called_class(), $table, $isNew));

        return true;
    }

    /**
     * Method to delete the object from the database.
     *
     * @return	boolean	True on success.
     */
    public function delete()
    {
        if ($this->_readonly) {
            return false;
        }

        if (!$this->_exists) {
            return true;
        }

        // Initialize table object.
        $table = static::getTable();
        $table->bind($this->getProperties());

        // Include the content plugins for the on save events.
        $dispatcher = \JEventDispatcher::getInstance();
        \JPluginHelper::importPlugin('content');

        // Trigger the onContentBeforeDelete event.
        $result = $dispatcher->trigger('onContentBeforeDelete', array("com_gantry5.".get_called_class(), $table));
        if (in_array(false, $result, true)) {
            $this->setError($table->getError());
            return false;
        }

        if (!$table->delete()) {
            $this->setError($table->getError());
            return false;
        }
        $this->_exists = false;

        // Trigger the onContentAfterDelete event.
        $dispatcher->trigger('onContentAfterDelete', array("com_gantry5.".get_called_class(), $table));

        return true;
    }

    /**
     * Method to perform sanity checks on the instance properties to ensure
     * they are safe to store in the database.
     *
     * Child classes should override this method to make sure the data they are storing in
     * the database is safe and as expected before storage.
     *
     * @return  boolean  True if the instance is sane and able to be stored in the database.
     */
    public function check()
    {
        return true;
    }

    static public function getAvailableInstances()
    {
        return static::collection(static::$instances);
    }

    static public function getInstances(array $ids, $readonly = true)
    {
        if (!$ids) {
            return array();
        }

        $results = array();
        $list = array();

        foreach ($ids as $id) {
            if (!isset(static::$instances[$id])) {
                $list[] = $id;
            }
        }

        if ($list) {
            $query = static::getQuery();
            $query->where('id IN (' . implode(',', $list) . ')');
            static::loadInstances($query);
        }

        foreach ($ids as $id) {
            if (isset(static::$instances[$id])) {
                if ($readonly) {
                    $results[$id] = clone static::$instances[$id];
                } else {
                    $results[$id] = static::$instances[$id];
                }
            }
        }

        return static::collection($results);
    }

    // Internal functions

    static protected function collection($items)
    {
        return new Collection($items);
    }

    /**
     * Method to get the table object.
     *
     * @return  \JTable  The table object.
     */
    static protected function getTable()
    {
        return \JTable::getInstance(static::$table, static::$tablePrefix);
    }

    /**
     * @return \JDatabaseQuery
     */
    static protected function getQuery()
    {
        $table = static::getTable();
        $db = \JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.*')->from($table->getTableName().' AS a')->order(static::$order);

        return $query;
    }

    /**
     * @param \JDatabaseQuery|string $query
     */
    static protected function loadInstances($query = null)
    {
        if (!$query) {
            $query = static::getQuery();
        }

        $db = \JFactory::getDbo();
        $db->setQuery($query);

        /** @var Object[] $items */
        $items = (array) $db->loadObjectList('id', get_called_class());

        foreach ($items as $item) {
            if (!isset(static::$instances[$item->id])) {
                $item->exists(true);
                $item->initialize();
            }
        }

        static::$instances += $items;
    }
}
