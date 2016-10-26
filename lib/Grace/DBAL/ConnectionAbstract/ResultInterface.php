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

/**
 * Provides query result interface
 */
interface ResultInterface
{
    /**
     * Fetches one row or ORM model or false
     * @abstract
     * @return array|bool one row from db as associative array
     */
    public function fetchOneOrFalse();
    /**
     * Fetches all rows as array or ORM model
     * @abstract
     * @return array rows
     */
    public function fetchAll();
    /**
     * Fetches one row or false
     * @abstract
     * @return array|bool one row from db as associative array
     */
    public function fetchAssocOneOrFalse();
    /**
     * Fetches all rows as array
     * @abstract
     * @return array rows
     */
    public function fetchAssocAll();
    /**
     * Fetches first value from first row of result
     * @abstract
     * @return string
     */
    public function fetchResult();
    /**
     * Fetches first value from all rows in result
     * @abstract
     * @return array first values
     */
    public function fetchColumn();
    /**
     * Fetches first value from all rows as a key and second as a value
     * @abstract
     * @return array key-value
     */
    public function fetchHash();
    /**
     * Returns number of rows in query result
     * Returns false if result is not presented
     * @abstract
     * @return int|bool
     */
    public function getNumRows();
}
