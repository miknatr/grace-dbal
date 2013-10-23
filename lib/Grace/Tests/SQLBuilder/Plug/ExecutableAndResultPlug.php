<?php

namespace Grace\Tests\SQLBuilder\Plug;

use Grace\DBAL\ConnectionAbstract\ResultInterface;
use Grace\Tests\SQLBuilder\Plug\ExecutablePlug;

class ExecutableAndResultPlug extends ExecutablePlug implements ResultInterface
{
    public function fetchAll()
    {
        return 'all';
    }
    public function fetchResult()
    {
        return 'result';
    }
    public function fetchColumn()
    {
        return 'column';
    }
    public function fetchHash()
    {
        return 'hash';
    }
    public function fetchOneOrFalse()
    {
        return 'one or false';
    }
}
