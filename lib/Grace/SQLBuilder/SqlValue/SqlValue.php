<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\SQLBuilder\SqlValue;

use Grace\SQLBuilder\SqlValue\SqlValueInterface;

/**
 * Custom sql value for usage in insert or update queries
 */
class SqlValue implements SqlValueInterface
{
    private $sql = '';
    private $values = array();

    /**
     * Usage:
     * new SqlValue('POINT(?q, ?q)', array($x, $y));
     *
     * @param $sql
     * @param $values
     */
    public function __construct($sql, $values)
    {
        $this->sql = $sql;
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getSql()
    {
        return $this->sql;
    }

    /**
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

}