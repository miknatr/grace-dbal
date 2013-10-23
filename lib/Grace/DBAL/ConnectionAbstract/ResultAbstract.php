<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\ConnectionAbstract;

use Grace\DBAL\ConnectionAbstract\ResultInterface;

/**
 * Provides some base functions for concrete result classes
 */
abstract class ResultAbstract implements ResultInterface
{
    /**
     * @inheritdoc
     */
    public function fetchAll()
    {
        $r = array();
        while ($row = $this->fetchOneOrFalse()) {
            $r[] = $row;
        }
        return $r;
    }

    /**
     * @inheritdoc
     */
    public function fetchResult()
    {
        $row = $this->fetchOneOrFalse();
        if (is_array($row)) {
            return array_shift($row);
        } else {
            return null;
        }
    }

    /**
     * @inheritdoc
     */
    public function fetchColumn()
    {
        $r = array();
        while ($row = $this->fetchOneOrFalse()) {
            if (count($row) == 1) {
                $r[] = array_shift($row);
            } else {
                throw new \LogicException('Sql-query result must contain one column');
            }
        }
        return $r;
    }

    /**
     * @inheritdoc
     */
    public function fetchHash()
    {
        $r = array();
        while ($row = $this->fetchOneOrFalse()) {
            if (count($row) == 2) {
                $key     = array_shift($row);
                $value   = array_shift($row);
                $r[$key] = $value;
            } else {
                throw new \LogicException('Sql-query result must contain two columns');
            }
        }
        return $r;
    }
}
