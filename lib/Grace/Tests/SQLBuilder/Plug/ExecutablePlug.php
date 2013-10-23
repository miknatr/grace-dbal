<?php

namespace Grace\Tests\SQLBuilder\Plug;

use Grace\DBAL\ConnectionAbstract\ExecutableInterface;
use Grace\DBAL\ConnectionAbstract\SqlDialectAbstract;

class ExecutablePlug implements ExecutableInterface
{
    public $query;
    public $arguments;
    public $sqlDialect;

    public function __construct(SqlDialectAbstract $sqlDialect)
    {
        $this->sqlDialect = $sqlDialect;
    }

    public function execute($query, array $arguments = array())
    {
        $this->query     = $query;
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * @return SqlDialectAbstract
     */
    public function provideSqlDialect()
    {
        return $this->sqlDialect;
    }

}
