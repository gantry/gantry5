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

use Joomla\CMS\Factory;
use Joomla\CMS\Service\Provider\Database;

/**
 * Class Finder
 * @package Gantry\Joomla\Object
 */
abstract class Finder
{
    /** @var string Table associated with the model. */
    protected $table;
    /** @var string */
    protected $primaryKey = 'id';
    /** @var \JDatabaseQuery */
    protected $query;
    /** @var Database */
    protected $db;
    /** @var int */
    protected $start = 0;
    /** @var int */
    protected $limit = 20;
    /** @var bool */
    protected $skip = false;

    /**
     * Finder constructor.
     *
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        if (!$this->table) {
            throw new \DomainException('Table name missing from ' . get_class($this));
        }

        $this->db = Factory::getDbo();
        $this->query = $this->db->getQuery(true);
        $this->query->from($this->table . ' AS a');

        if ($options) {
            $this->parse($options);
        }
    }

    /**
     * @param array $options
     * @return $this
     */
    public function parse(array $options)
    {
        foreach ($options as $method => $params) {
            if (method_exists($this, $method)) {
                call_user_func_array([$this, $method], (array) $params);
            }
        }

        return $this;
    }

    /**
     * Set limitstart for the query.
     *
     * @param int $limitstart
     * @return $this
     */
    public function start($limitstart = 0)
    {
        $this->start = (int)$limitstart;

        return $this;
    }

    /**
     * Set limit to the query.
     *
     * @param int $limit
     *
     * @return $this
     */
    public function limit($limit = null)
    {
        if (null !== $limit) {
            $this->limit = (int)$limit;
        }

        return $this;
    }

    /**
     * Set order by field and direction.
     *
     * This function can be used more than once to chain order by.
     *
     * @param  string $by
     * @param  string|int $direction
     * @param  string $alias
     *
     * @return $this
     */
    public function order($by, $direction = 1, $alias = 'a')
    {
        if ($direction === 'RANDOM') {
            $this->query->order('RAND()');

            return $this;
        }

        if (is_numeric($direction)) {
            $direction = $direction > 0 ? 'ASC' : 'DESC';
        } else {
            $direction = strtolower((string)$direction) === 'desc' ? 'DESC' : 'ASC';
        }
        $by = (string)$alias . '.' . $this->db->quoteName($by);
        $this->query->order("{$by} {$direction}");

        return $this;
    }

    /**
     * Filter by field.
     *
     * @param  string           $field       Field name.
     * @param  string           $operation   Operation (>|>=|<|<=|=|IN|NOT IN)
     * @param  string|int|array $value       Value.
     *
     * @return $this
     */
    public function where($field, $operation, $value)
    {
        $db = $this->db;
        $operation = strtoupper($operation);
        switch ($operation)
        {
            case '>':
            case '>=':
            case '<':
            case '<=':
            case '=':
                // Quote all non integer values.
                $value = (string)(int)$value === (string)$value ? (int)$value : $db->quote($value);
                $this->query->where("{$this->db->quoteName($field)} {$operation} {$value}");
                break;
            case 'BETWEEN':
            case 'NOT BETWEEN':
                list($a, $b) = (array) $value;
                // Quote all non integer values.
                $a = (string)(int)$a === (string)$a ? (int)$a : $db->quote($a);
                $b = (string)(int)$b === (string)$b ? (int)$b : $db->quote($b);
                $this->query->where("{$this->db->quoteName($field)} {$operation} {$a} AND {$b}");
                break;
            case 'IN':
            case 'NOT IN':
                $value = (array) $value;
                if (empty($value)) {
                    // WHERE field IN (nothing).
                    $this->query->where('0');
                } else {
                    // Quote all non integer values.
                    array_walk($value, function (&$value) use ($db) { $value = (string)(int)$value === (string)$value ? (int)$value : $db->quote($value); });
                    $list = implode(',', $value);
                    $this->query->where("{$this->db->quoteName($field)} {$operation} ({$list})");
                }
                break;
        }

        return $this;
    }

    /**
     * Get items.
     *
     * Derived classes should generally override this function to return correct objects.
     *
     * @return array
     */
    public function find()
    {
        if ($this->skip) {
            return [];
        }

        $baseQuery = clone $this->query;
        $this->prepare();
        $query = $this->query;
        $this->query = $baseQuery;

        $query->select('a.' . $this->primaryKey);
        $this->db->setQuery($query, $this->start, $this->limit);

        return $this->db->loadColumn() ?: [];
    }

    /**
     * Count items.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        $baseQuery = clone $this->query;
        $this->prepare();
        $query = $this->query;
        $this->query = $baseQuery;

        $query->select('COUNT(*)');
        $this->db->setQuery($query);

        return (int)$this->db->loadResult();
    }

    /**
     * Override to include common where rules.
     *
     * @return void
     */
    protected function prepare()
    {
    }
}
