<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\SQLBuilder;

/**
 * Select sql builder
 */
class SelectBuilder extends WhereBuilderAbstract
{
    protected $fields = '*';
    protected $fieldsArguments = array();
    protected $joins = array();
    protected $lastJoinAlias = '';
    protected $joinArguments = array();
    protected $groupSql = '';
    protected $groupArguments = array();
    protected $havingSql = '';
    protected $havingArguments = array();
    protected $orderSql = '';
    protected $orderArguments = array();
    protected $limitSql;

    /**
     * Sets count syntax
     * @return $this
     */
    public function count()
    {
        $this->fields = 'COUNT(?f:alias:.?f) AS ?f';
        //TODO id - magic field
        $this->fieldsArguments = array();
        $this->fieldsArguments[] = 'id';
        $this->fieldsArguments[] = 'counter';
        return $this;
    }

    /**
     * Sets fields statement
     *
     * @param $fields array('id', array('AsText(?f) AS ?f', array('coords', 'coords')))
     * @throws \BadMethodCallException
     * @return $this
     */
    public function fields(array $fields)
    {
        $newFields = array();
        $this->fields = '';
        $this->fieldsArguments = array();

        foreach ($fields as $field) {
            if (is_scalar($field)) {
                $newFields[] = '?f:alias:.?f';
                $this->fieldsArguments[] = $field;
            } else {
                if (!isset($field[0]) or !isset($field[1]) or !is_array($field[1])) {
                    throw new \BadMethodCallException('Must be exist 0 and 1 index in array and second one must be an array');
                }
                $newFields[] = $field[0];
                $this->fieldsArguments = array_merge($this->fieldsArguments, $field[1]);
            }
        }

        $this->fields = implode(', ', $newFields);

        return $this;
    }

    /**
     * Sets one field in fields statement
     * @param $field
     * @param array $arguments
     * @return $this
     */
    public function fieldSql($field, array $arguments = array())
    {
        $this->fields(array(array($field, $arguments)));
        return $this;
    }
    /**
     * Sets one field in fields statement
     * @param $field
     * @return $this
     */
    public function field($field)
    {
        $this->fields(array($field));
        return $this;
    }

    /**
     * Sets one field in fields statement
     *
     * @param string $tableName
     * @param string $alias
     * @return $this
     */
    public function join($tableName, $alias = null)
    {
        $this->joins[] = ' LEFT JOIN ?f as ?f';
        $this->lastJoinAlias = $alias;
        $this->joinArguments[] = $tableName;
        $this->joinArguments[] = $alias;
        return $this;
    }

    public function onEq($localField, $foreignField)
    {
        if (empty($this->joins)) {
            throw new \LogicException('Select builder error: onEq() called before join()');
        }

        $lastJoinIndex = count($this->joins) - 1;
        if ($this->joins[$lastJoinIndex] == ' LEFT JOIN ?f as ?f') {
            $this->joins[$lastJoinIndex] .= ' ON';
        } else {
            $this->joins[$lastJoinIndex] .= ' AND';
        }

        $this->joins[$lastJoinIndex] .= ' ?f:alias:.?f = ?f';
        $this->joinArguments[] = $localField;
        $this->joinArguments[] = "{$this->lastJoinAlias}.{$foreignField}";

        return $this;
    }

    /**
     * Sets group by statement
     * @param $sql
     * @param $arguments
     * @return $this
     */
    public function having($sql, array $arguments)
    {
        $this->havingSql       = ' HAVING ' . $sql;
        $this->havingArguments = $arguments;
        return $this;
    }

    /**
     * Sets group by statement
     *
     * @param string $field
     * @param bool $prefixWithTableAlias
     * @return $this
     */
    public function group($field, $prefixWithTableAlias = true)
    {
        $aliasInsert = $prefixWithTableAlias ? '?f:alias:.' : '';

        if ($this->groupSql == '') {
            $this->groupSql = " GROUP BY {$aliasInsert}?f";
        } else {
            $this->groupSql .= ", {$aliasInsert}?f";
        }
        $this->groupArguments[] = $field;
        return $this;
    }

    /**
     * Sets asc order by statement
     * @param string $field
     * @param bool $prefixWithTableAlias
     * @return $this
     */
    public function orderAsc($field, $prefixWithTableAlias = true)
    {
        $this->orderByDirection($field, 'ASC', $prefixWithTableAlias);
        return $this;
    }
    /**
     * Sets desc order by statement
     * @param string $field
     * @param bool $prefixWithTableAlias
     * @return $this
     */
    public function orderDesc($field, $prefixWithTableAlias = true)
    {
        $this->orderByDirection($field, 'DESC', $prefixWithTableAlias);
        return $this;
    }

    /**
     * Sets order by statement
     *
     * @param string $field
     * @param string $direction
     * @param bool $prefixWithTableAlias
     * @return $this
     */
    protected function orderByDirection($field, $direction, $prefixWithTableAlias = true)
    {
        $aliasInsert = $prefixWithTableAlias ? '?f:alias:.' : '';

        if ($this->orderSql == '') {
            $this->orderSql = " ORDER BY {$aliasInsert}?f {$direction}";
        } else {
            $this->orderSql .= ", {$aliasInsert}?f {$direction}";
        }
        $this->orderArguments[] = $field;
    }
    /**
     * Sets limit statements
     * @param $from
     * @param $limit
     * @return $this
     */
    public function limit($from, $limit)
    {
        $this->limitSql = ' LIMIT ' . $limit . ' OFFSET ' . $from;
        return $this;
    }
    /**
     * @inheritdoc
     */
    public function getQueryString()
    {
        return 'SELECT ' . $this->fields . ' FROM ?f AS ?f' . join('', $this->joins) . $this->getWhereSql() .
            $this->groupSql . $this->havingSql . $this->orderSql . $this->limitSql;
    }
    /**
     * @inheritdoc
     */
    protected function getQueryArguments()
    {
        $arguments = parent::getQueryArguments();
        return array_merge($this->fieldsArguments, array($this->from, $this->alias), $this->joinArguments, $arguments, $this->groupArguments, $this->havingArguments, $this->orderArguments);
    }
}
