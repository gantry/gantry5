<?php

/**
 * @package   Gantry5
 * @author    RocketTheme http://www.rockettheme.com
 * @copyright Copyright (C) 2007 - 2022 RocketTheme, LLC
 * @license   GNU/GPLv2 and later
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

namespace Gantry\Joomla\Object;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Table\Table;
use Joomla\Database\DatabaseQuery;

/**
 * Abstract base class for database objects.
 */
abstract class AbstractObject extends \JObject
{
    /** @var array If you don't have global instance ids, override this in extending class. */
    static protected $instances = [];
    /** @var string Override table class in your own class. */
    static protected $table;
    /** @var string Table class prefix, override if needed. */
    static protected $tablePrefix = 'JTable';
    /** @var string Override table in your own class. */
    static protected $order;

    /** @var int */
    public $id;

    /** @var boolean Is object stored into database? */
    protected $_exists = false;
    /** @var bool Readonly object. */
    protected $_readonly = false;
    /** @var bool */
    protected $_initialized = false;

    /**
     * Class constructor, overridden in descendant classes.
     *
     * @param int|array $properties Identifier.
     */
    public function __construct($properties = null)
    {
        if (null === $properties || is_array($properties)) {
            $identifier = null;
        } else {
            $identifier = $properties;
            $properties = null;
        }

        parent::__construct($properties);

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
     * @param  bool    $reload      Force object reload from the database.
     *
     * @return  Object
     */
    public static function getInstance($keys = null, $reload = false)
    {
        // If we are creating or loading a new item or we load instance by alternative keys,
        // we need to create a new object.
        if (!$keys || \is_array($keys) || !isset(static::$instances[(int) $keys])) {
            $class = \get_called_class();
            $instance = new $class($keys);
            /** @var Object $instance */
            if (!$instance->exists()) return $instance;

            // Instance exists: make sure that we return the global instance.
            $keys = $instance->id;
        }

        // Return global instance from the identifier, possibly reloading it first.
        $instance = static::$instances[(int) $keys];
        if ($reload) {
            $instance->load($keys);
        }

        return $instance;
    }

    /**
     * Removes all or selected instances from the object cache.
     *
     * @param null|int|int[]  $ids
     */
    public static function freeInstances($ids = null)
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
     * @param   bool  $public  If true, returns only the public properties.
     *
     * @return  array
     */
    public function getProperties($public = true)
    {
        if ($public) {
            $getProperties = static function($obj) { return get_object_vars($obj); };
            return $getProperties($this);
        }

        return get_object_vars($this);
    }

    /**
     * Method to bind an associative array to the instance.
     *
     * This method optionally takes an array of properties to ignore or allow when binding.
     *
     * @param   array    $src     An associative array or object to bind to the Table instance.
     * @param   array    $fields  An optional array list of properties to ignore / include only while binding.
     * @param   boolean  $include  True to include only listed fields, false to ignore listed fields.
     *
     * @return  bool  True on success.
     */
    public function bind(array $src = null, array $fields = null, $include = false)
    {
        if (empty($src)) {
            return false;
        }

        if (!empty($fields)) {
            $src = $include ? array_intersect_key($src, array_flip($fields)) : array_diff_key($src, array_flip($fields));
        }
        $this->setProperties($src);

        return true;
    }

    /**
     * Method to load object from the database.
     *
     * @param   mixed    $keys   An optional primary key value to load the object by, or an array of fields to match. If not
     *                           set the instance key value is used.
     *
     * @return  bool  True on success, false if the object doesn't exist.
     */
    public function load($keys = null)
    {
        if ($keys !== null && !is_array($keys)) {
            $keys = ['id' => (int)$keys];
        }

        // Create the table object.
        $table = static::getTable();

        // Make sure we set the given keys to the object even if it is not loaded.
        $table->reset();
        if ($keys !== null) {
            $table->bind($keys);
        }

        // Load the object based on the keys.
        $this->_exists = $table->load($keys, false);

        // Work around Joomla 3.1.1 bug on load() returning true if keys didn't exist.
        if ($table->id == 0) {
            $this->_exists = false;
        }

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
     * @return  bool  True on success.
     */
    public function save()
    {
        // Check the object.
        if ($this->_readonly || !$this->check()) {
            return false;
        }

        $isNew = !$this->_exists;

        // Initialize table object.
        $table = static::getTable();
        $table->bind($this->getProperties());

        // Check the table object.
        if (!$table->check()) {
            $this->setError($table->getError());
            return false;
        }

        /** @var CMSApplication $application */
        $application = Factory::getApplication();

        // Include the content plugins for the on save events.
        PluginHelper::importPlugin('content');

        // Trigger the onContentBeforeSave event.
        $result = $application->triggerEvent('onContentBeforeSave', ['com_gantry5.' . static::class, $table, $isNew]);
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
        $application->triggerEvent('onContentAfterSave', ['com_gantry5.' . static::class, $table, $isNew]);

        return true;
    }

    /**
     * Method to delete the object from the database.
     *
     * @return bool True on success.
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

        /** @var CMSApplication $application */
        $application = Factory::getApplication();

        // Include the content plugins for the on save events.
        PluginHelper::importPlugin('content');

        // Trigger the onContentBeforeDelete event.
        $result = $application->triggerEvent('onContentBeforeDelete', ['com_gantry5.' . static::class, $table]);
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
        $application->triggerEvent('onContentAfterDelete', ['com_gantry5.' . static::class, $table]);

        return true;
    }

    /**
     * Returns SQL on how to create the object.
     *
     * @param array $ignore
     * @return DatabaseQuery
     */
    public function getCreateSql(array $ignore = ['asset_id'])
    {
        // Initialize table object.
        $table = self::getTable();
        $table->bind($this->getProperties());
        $dbo = $table->getDbo();

        $values = $this->getFieldValues($ignore);

        // Create the base insert statement.
        $query = $dbo->getQuery(true)
            ->insert($dbo->quoteName($table->getTableName()))
            ->columns(array_keys($values))
            ->values(implode(',', array_values($values)));

        return $query;
    }


    /**
     * Returns SQL on how to create the object.
     *
     * @param array $ignore
     * @return array
     */
    public function getFieldValues(array $ignore = ['asset_id'])
    {
        // Initialize table object.
        $table = self::getTable();
        $table->bind($this->getProperties());
        $dbo = $table->getDbo();

        $values       = [];
        $tableColumns = $table->getFields();

        // Iterate over the object variables to build the query fields and values.
        foreach (get_object_vars($this) as $k => $v) {
            // Ignore any internal or ignored fields.
            if ($k[0] === '_' || in_array($k, $ignore, true) || $v === null) {
                continue;
            }

            // Skip columns that don't exist in the table.
            if (!\array_key_exists($k, $tableColumns)) {
                continue;
            }

            $field = $tableColumns[$k];
            if (strpos($field->Type, 'int(') === 0) {
                $v = (int)$v;
            }

            // Convert arrays and objects into JSON.
            if (\is_array($v) || \is_object($v)) {
                $v = json_encode($v);
            }

            $k = $dbo->quoteName($k);
            $values[$k] = $this->fixValue($table, $k, $v);
        }

        return $values;
    }

    protected function fixValue($table, $k, $v)
    {
        if (is_string($v)) {
            $dbo = $table->getDbo();
            $v = $dbo->quote($v);
        }

        return $v;
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

    /**
     * @return Collection
     */
    public static function getAvailableInstances()
    {
        return static::collection(static::$instances);
    }

    /**
     * @param array $ids
     * @param bool $readonly
     * @return Collection
     */
    public static function getInstances(array $ids, $readonly = true)
    {
        if (!$ids) {
            return static::collection([]);
        }

        $results = [];
        $list = [];

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

    /**
     * @param $items
     * @return Collection
     */
    protected static function collection($items)
    {
        return new Collection($items);
    }

    /**
     * Method to get the table object.
     *
     * @return  Table  The table object.
     */
    protected static function getTable()
    {
        return Table::getInstance(static::$table, static::$tablePrefix);
    }

    /**
     * @return \JDatabaseQuery
     */
    static protected function getQuery()
    {
        $table = static::getTable();
        $db = Factory::getDbo();
        $query = $db->getQuery(true);
        $query->select('a.*')->from($table->getTableName().' AS a')->order(static::$order);

        return $query;
    }

    /**
     * @param \JDatabaseQuery|string $query
     */
    protected static function loadInstances($query = null)
    {
        if (!$query) {
            $query = static::getQuery();
        }

        $db = Factory::getDbo();
        $db->setQuery($query);

        /** @var Object[] $items */
        $items = [];
        foreach ($db->loadAssocList('id') as $id => $data) {
            $items[$id] = new static($data);
        }

        foreach ($items as $item) {
            if (!isset(static::$instances[$item->id])) {
                $item->exists(true);
                $item->initialize();
            }
        }

        static::$instances += $items;
    }
}
