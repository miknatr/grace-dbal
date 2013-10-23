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

use Grace\DBAL\ConnectionAbstract\ExecutableInterface;

/**
 * Factory for sql-builders
 */
class Factory
{
    /** @var ExecutableInterface */
    private $executable;

    /**
     * @param ExecutableInterface $executable
     */
    public function __construct(ExecutableInterface $executable)
    {
        $this->executable = $executable;
    }

    /**
     * @param string $table
     * @return SelectBuilder
     */
    public function select($table)
    {
        return new SelectBuilder($table, $this->executable);
    }

    /**
     * @param string $table
     * @return InsertBuilder
     */
    public function insert($table)
    {
        return new InsertBuilder($table, $this->executable);
    }

    /**
     * @param string $table
     * @return UpdateBuilder
     */
    public function update($table)
    {
        return new UpdateBuilder($table, $this->executable);
    }

    /**
     * @param string $table
     * @return DeleteBuilder
     */
    public function delete($table)
    {
        return new DeleteBuilder($table, $this->executable);
    }
}

