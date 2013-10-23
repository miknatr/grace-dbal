<?php

namespace Grace\Tests\SQLBuilder;

use Grace\DBAL\Mysqli\SqlDialect;
use Grace\SQLBuilder\Factory;
use Grace\SQLBuilder\SelectBuilder;
use Grace\SQLBuilder\InsertBuilder;
use Grace\SQLBuilder\UpdateBuilder;
use Grace\SQLBuilder\DeleteBuilder;
use Grace\Tests\SQLBuilder\Plug\ExecutablePlug;

class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /** @var Factory */
    protected $builder;

    protected function setUp()
    {
        $this->builder = new Factory(new ExecutablePlug(new SqlDialect()));
    }
    public function testSelectFactory()
    {
        $r = $this->builder->select('Test');
        $this->assertTrue($r instanceof SelectBuilder);
    }
    public function testInsertFactory()
    {
        $r = $this->builder->insert('Test');
        $this->assertTrue($r instanceof InsertBuilder);
    }
    public function testUpdateFactory()
    {
        $r = $this->builder->update('Test');
        $this->assertTrue($r instanceof UpdateBuilder);
    }
    public function testDeleteFactory()
    {
        $r = $this->builder->delete('Test');
        $this->assertTrue($r instanceof DeleteBuilder);
    }
}
