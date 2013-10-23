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

use Grace\SQLBuilder\SqlValue\SqlValueInterface;

/**
 * Insert sql builder
 */
class InsertBuilder extends BuilderAbstract
{
    private $fieldsSql = '';
    private $valuesSql = '';
    private $fieldValues = array();

    /**
     * Prepares values for inserting into db
     * @param array $values
     * @return $this
     */
    public function values(array $values)
    {
        $this->fieldValues = array();

        $this->fieldsSql   = '?i';
        $this->fieldValues[] = array_keys($values);

        $this->valuesSql   = array();

        foreach ($values as $v) {
            if (is_object($v) and $v instanceof SqlValueInterface) {
                $this->valuesSql[] = $v->getSql();
                $this->fieldValues = array_merge($this->fieldValues, $v->getValues());
            } else {
                $this->valuesSql[]   = '?q';
                $this->fieldValues[] = $v;
            }
        }

        $this->valuesSql = implode(', ', $this->valuesSql);

        return $this;
    }
    /**
     * @inheritdoc
     */
    protected function getQueryString()
    {
        if (count($this->fieldValues) == 0) {
            throw new \LogicException('Set values for insert before execute');
        }
        return 'INSERT INTO ?f (' . $this->fieldsSql . ')' . ' VALUES (' . $this->valuesSql . ')';
    }
    /**
     * @inheritdoc
     */
    protected function getQueryArguments()
    {
        $arguments = $this->fieldValues;
        array_unshift($arguments, $this->from);
        return $arguments;
    }
}
