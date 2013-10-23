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

class UpdateBuilder extends WhereBuilderAbstract
{
    private $fieldsSql = '';
    private $fieldValues = array();

    /**
     * @param array $values
     * @return $this
     */
    public function values(array $values)
    {
        $this->fieldsSql = array();
        $this->fieldValues = array();

        foreach ($values as $k => $v) {
            if (is_object($v) and $v instanceof SqlValueInterface) {
                $this->fieldsSql[]   = '?f=' . $v->getSql();
                $this->fieldValues[] = $k;
                $this->fieldValues   = array_merge($this->fieldValues, $v->getValues());
            } else {
                $this->fieldsSql[]   = '?f=?q';
                $this->fieldValues[] = $k;
                $this->fieldValues[] = $v;
            }
        }

        $this->fieldsSql = implode(', ', $this->fieldsSql);

        return $this;
    }
    protected function getQueryString()
    {
        if (count($this->fieldValues) == 0) {
            throw new \LogicException('Set values for update before execute');
        }
        return 'UPDATE ?f SET ' . $this->fieldsSql . $this->getWhereSql();
    }
    protected function getQueryArguments()
    {
        return array_merge(array($this->from), $this->fieldValues, parent::getQueryArguments());
    }
}
