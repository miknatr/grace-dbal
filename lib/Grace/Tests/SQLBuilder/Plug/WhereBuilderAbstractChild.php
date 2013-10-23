<?php

namespace Grace\Tests\SQLBuilder\Plug;

use Grace\SQLBuilder\WhereBuilderAbstract;

class WhereBuilderAbstractChild extends WhereBuilderAbstract
{
    public function getQueryString()
    {
        //It's plug
        ;
    }
    public function getWhereSql()
    {
        return parent::getWhereSql();
    }
    public function getQueryArguments()
    {
        return parent::getQueryArguments();
    }
}
