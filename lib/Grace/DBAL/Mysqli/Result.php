<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\Mysqli;

use Grace\DBAL\ConnectionAbstract\ResultAbstract;

/**
 * Mysqli result concrete class
 */
class Result extends ResultAbstract
{
    /** @var \mysqli_result */
    private $result;

    /**
     * @inheritdoc
     */
    public function fetchOneOrFalse()
    {
        return $this->result->fetch_assoc();
    }
    /**
     * @param \mysqli_result $result
     */
    public function __construct(\mysqli_result $result)
    {
        $this->result = $result;
    }
    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        $this->result->free();
    }
}