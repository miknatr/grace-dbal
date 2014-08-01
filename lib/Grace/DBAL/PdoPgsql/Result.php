<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Alex Polev <alex.v.polev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\PdoPgsql;

use Grace\DBAL\ConnectionAbstract\ResultAbstract;
use Grace\DBAL\Exception\ConnectionException;

/**
 * Pgsql result concrete class
 */
class Result extends ResultAbstract
{
    /** @var \PDOStatement */
    private $result;

    /**
     * @inheritdoc
     */
    public function fetchOneOrFalse()
    {
        return $this->result->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * @param \PDOStatement $result
     * @throws ConnectionException
     */
    public function __construct(\PDOStatement $result)
    {
        $this->result = $result;
    }
    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        if ($this->result) {
            $this->result->closeCursor();
        }
    }
    /**
     * @inheritdoc
     */
    public function getNumRows()
    {
        if (!$this->result) {
            return false;
        }

        return $this->result->rowCount();
    }

    public function getAffectedRows()
    {
        if (!$this->result) {
            return false;
        }

        return $this->result->rowCount();
    }
}
