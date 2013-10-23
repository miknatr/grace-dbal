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
use Grace\DBAL\Exception\QueryException;

/**
 * Provides executable interface
 */
interface ExecutableInterface
{
    /**
     * Executes sql query and return InterfaceResult object
     * @abstract
     * @param string $query
     * @param array  $arguments
     * @throws QueryException, ExceptionConnection
     * @return ResultInterface|bool
     */
    public function execute($query, array $arguments = array());
    /**
     * @return SqlDialectAbstract
     */
    public function provideSqlDialect();
}
