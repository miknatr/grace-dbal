<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Alex Polev <alex.v.polev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\Pgsql;

use Grace\DBAL\ConnectionAbstract\ResultAbstract;
use Grace\DBAL\Exception\ConnectionException;

/**
 * Pgsql result concrete class
 */
class Result extends ResultAbstract
{
    /** @var resource */
    private $result;

    /**
     * @inheritdoc
     */
    public function fetchOneOrFalse()
    {
        return pg_fetch_assoc($this->result);
    }

    /**
     * @param $result
     * @throws ConnectionException
     */
    public function __construct($result)
    {
        $this->result = $result;
    }
    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        if (is_resource($this->result)) {
            pg_free_result($this->result);
        }
    }
    /**
     * @inheritdoc
     */
    public function getNumRows()
    {
        if (!is_resource($this->result)) {
            return false;
        }

        return pg_num_rows($this->result);
    }

    public function getAffectedRows()
    {
        if (!is_resource($this->result)) {
            return false;
        }

        return pg_affected_rows($this->result);
    }
}
