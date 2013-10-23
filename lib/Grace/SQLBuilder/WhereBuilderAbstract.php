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
 * Provides some base functions for builders with where statements
 */
abstract class WhereBuilderAbstract extends BuilderAbstract
{
    private $arguments = array();
    private $whereSqlConditions = array();

    /**
     * Adds sql statement into where statement
     * @param       $sql
     * @param array $values
     * @return $this
     */
    public function sql($sql, array $values = array())
    {
        $this->whereSqlConditions[] = $sql;
        $this->arguments = array_merge($this->arguments, $values);
        return $this;
    }

    /**
     * @param $field
     * @param $operator
     * @return $this
     */
    protected function setOneArgOperator($field, $operator)
    {
        $this->whereSqlConditions[] = '?f:alias:.?f ' . $operator;
        $this->arguments[] = $field;
        return $this;
    }

    /**
     * @param $field
     * @param $value
     * @param $operator
     * @return $this
     */
    protected function setTwoArgsOperator($field, $value, $operator)
    {
        $this->whereSqlConditions[] = '?f:alias:.?f' . $operator . '?q';
        $this->arguments[] = $field;
        $this->arguments[] = $value;
        return $this;
    }

    /**
     * Adds IS NULL statement into where statement
     * @param $field
     * @return $this
     */
    public function isNull($field)
    {
        $this->setOneArgOperator($field, 'IS NULL');
        return $this;
    }
    /**
     * Adds IS NOT NULL statement into where statement
     * @param $field
     * @return $this
     */
    public function notNull($field)
    {
        $this->setOneArgOperator($field, 'IS NOT NULL');
        return $this;
    }
    /**
     * Adds '=' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function eq($field, $value)
    {
        return $this->setTwoArgsOperator($field, $value, '=');
    }
    /**
     * Adds '!=' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function notEq($field, $value)
    {
        return $this->setTwoArgsOperator($field, $value, '!=');
    }
    /**
     * Adds '>' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function gt($field, $value)
    {
        return $this->setTwoArgsOperator($field, $value, '>');
    }
    /**
     * Adds '>=' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function gtEq($field, $value)
    {
        return $this->setTwoArgsOperator($field, $value, '>=');
    }
    /**
     * Adds '<' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function lt($field, $value)
    {
        return $this->setTwoArgsOperator($field, $value, '<');
    }
    /**
     * Adds '<=' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function ltEq($field, $value)
    {
        return $this->setTwoArgsOperator($field, $value, '<=');
    }
    /**
     * Checks if the field is TRUE
     * @param $field
     * @return $this
     */
    public function true($field)
    {
        $this->whereSqlConditions[] = '?f:alias:.?f';
        $this->arguments[] = $field;
        return $this;
    }
    /**
     * Checks if the field is FALSE
     * @param $field
     * @return $this
     */
    public function false($field)
    {
        $this->whereSqlConditions[] = 'NOT ?f:alias:.?f';
        $this->arguments[] = $field;
        return $this;
    }
    /**
     * Adds LIKE statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function like($field, $value)
    {
        $operator = ' ' . $this->executable->provideSqlDialect()->likeOperator() . ' ';
        return $this->setTwoArgsOperator($field, $value, $operator);
    }
    /**
     * Adds NOT LIKE statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function notLike($field, $value)
    {
        $operator = ' NOT ' . $this->executable->provideSqlDialect()->likeOperator() . ' ';
        return $this->setTwoArgsOperator($field, $value, $operator);
    }
    /**
     * Adds LIKE '%value%' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function likeInPart($field, $value)
    {
        $operator = ' ' . $this->executable->provideSqlDialect()->likeOperator() . ' ';
        return $this->setTwoArgsOperator($field, '%' . $value . '%', $operator);
    }
    /**
     * Adds NOT LIKE '%value%' statement into where statement
     * @param $field
     * @param $value
     * @return $this
     */
    public function notLikeInPart($field, $value)
    {
        $operator = ' NOT ' . $this->executable->provideSqlDialect()->likeOperator() . ' ';
        return $this->setTwoArgsOperator($field, '%' . $value . '%', $operator);
    }
    /**
     * @param       $field
     * @param array $values
     * @param       $operator
     * @param       $emptyValuesCondition
     * @return $this
     */
    protected function setInOperator($field, array $values, $operator, $emptyValuesCondition)
    {
        if (empty($values)) {
            $this->whereSqlConditions[] = $emptyValuesCondition;
        } else {
            $whereCondition =  '?f:alias:.?f ' . $operator . ' (' . substr(str_repeat('?q,', count($values)), 0, -1) . ')';

            $this->whereSqlConditions[] = $whereCondition;
            $this->arguments = array_merge($this->arguments, array($field), $values);
        }

        return $this;
    }
    /**
     * Adds IN statement into where statement
     * @param       $field
     * @param array $values
     * @return $this
     */
    public function in($field, array $values)
    {
        return $this->setInOperator($field, $values, 'IN', 'FALSE');
    }
    /**
     * Adds NOT IN statement into where statement
     * @param       $field
     * @param array $values
     * @return $this
     */
    public function notIn($field, array $values)
    {
        return $this->setInOperator($field, $values, 'NOT IN', 'TRUE');
    }
    /**
     * @param $field
     * @param $value1
     * @param $value2
     * @param $operator
     * @return $this
     */
    protected function setBetweenOperator($field, $value1, $value2, $operator)
    {
        $this->whereSqlConditions[] = '?f:alias:.?f ' . $operator . ' ?q AND ?q';
        $this->arguments[] = $field;
        $this->arguments[] = $value1;
        $this->arguments[] = $value2;
        return $this;
    }
    /**
     * Adds BETWEEN statement into where statement
     * @param $field
     * @param $value1
     * @param $value2
     * @return $this
     */
    public function between($field, $value1, $value2)
    {
        return $this->setBetweenOperator($field, $value1, $value2, 'BETWEEN');
    }
    /**
     * Adds NOT BETWEEN statement into where statement
     * @param $field
     * @param $value1
     * @param $value2
     * @return $this
     */
    public function notBetween($field, $value1, $value2)
    {
        return $this->setBetweenOperator($field, $value1, $value2, 'NOT BETWEEN');
    }

    protected $gluingLoginOperators = array('OR' => 1, 'AND' => 1);
    protected $rightOperators = array('NOT' => 1, '(' => 1);
    protected $leftOperators = array(')' => 1);

    /** @return $this */
    public function _not()
    {
        $this->whereSqlConditions[] = 'NOT';
        return $this;
    }
    /** @return $this */
    public function _and()
    {
        $this->whereSqlConditions[] = 'AND';
        return $this;
    }
    /** @return $this */
    public function _or()
    {
        $this->whereSqlConditions[] = 'OR';
        return $this;
    }
    /**
     * @return $this
     */
    public function _open()
    {
        $this->whereSqlConditions[] = '(';
        return $this;
    }
    /** @return $this */
    public function _close()
    {
        $this->whereSqlConditions[] = ')';
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function getQueryArguments()
    {
        return $this->arguments;
    }
    /**
     * @return string
     */
    protected function getWhereSql()
    {
        if (count($this->whereSqlConditions) == 0) {
            return '';
        }

        //есть операторы, которые должны склеивать любые два элемента
        //и есть операторы, которые не нуждаются в склейке: "NOT" и ")" не нужна склейка справа, ")" не нужна слева
        $conditions = array();
        $conditions[] = $this->whereSqlConditions[0];

        for ($i = 1; $i < count($this->whereSqlConditions); $i++) {

            $prev = $this->whereSqlConditions[$i - 1];
            $curr = $this->whereSqlConditions[$i];

            //проверяем, нужно ли склеить с помощью AND предыдущий и текущий элемент
            if (!isset($this->gluingLoginOperators[$prev])
                and !isset($this->gluingLoginOperators[$curr])
                and !isset($this->rightOperators[$prev])
                and !isset($this->leftOperators[$curr])) {

                $conditions[] = 'AND';
            }

            $conditions[] = $this->whereSqlConditions[$i];
        }

        return ' WHERE ' . implode(' ', $conditions);
    }
}
