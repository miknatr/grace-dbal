<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\Pgsql;

use Grace\DBAL\ConnectionAbstract\SqlDialectAbstract;

/**
 * Function and operators of specific sql-dialect
 */
class SqlDialect extends SqlDialectAbstract
{
    public function likeOperator()
    {
        return "ILIKE";
    }
}
