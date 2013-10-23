<?php

namespace Grace\Tests\SQLBuilder;

use Grace\DBAL\Mysqli\SqlDialect;
use Grace\SQLBuilder\SqlValue\SqlValue;
use Grace\SQLBuilder\UpdateBuilder;
use Grace\Tests\SQLBuilder\Plug\ExecutablePlug;

class UpdateBuilderTest extends \PHPUnit_Framework_TestCase
{
    /** @var UpdateBuilder */
    protected $builder;
    /** @var ExecutablePlug */
    protected $plug;

    protected function setUp()
    {
        $this->plug    = new ExecutablePlug(new SqlDialect());
        $this->builder = new UpdateBuilder('TestTable', $this->plug);
    }

    protected function tearDown()
    {
    }

    public function testUpdateWithoutParams()
    {
        try {
            $this->builder->execute();
        } catch (\LogicException $ignored) {
        }
    }

    public function testUpdateWithoutWhereStatement()
    {
        $this->builder
            ->values(array(
                'id'    => 123,
                'name'  => 'Mike',
                'phone' => '123-123',
                'point' => new SqlValue('POINT(?q, ?q)', array(1, 2)),
            ))
            ->execute();

        $this->assertEquals('UPDATE ?f SET ?f=?q, ?f=?q, ?f=?q, ?f=POINT(?q, ?q)', $this->plug->query);
        $this->assertEquals(array('TestTable', 'id', 123, 'name', 'Mike', 'phone', '123-123', 'point', 1, 2, 'alias' => 'TestTable'), $this->plug->arguments);
    }

    public function testUpdateWithWhereStatement()
    {
        $this->builder
            ->values(array(
                'id'    => 123,
                'name'  => 'Mike',
                'phone' => '123-123',
            ))
            ->true('isPublished') //test with AbstractWhereBuilder
            ->between('category', 10, 20) //test with AbstractWhereBuilder
            ->execute();

        $this->assertEquals(
            'UPDATE ?f SET ?f=?q, ?f=?q, ?f=?q' .
            ' WHERE ?f:alias:.?f AND ?f:alias:.?f BETWEEN ?q AND ?q',
            $this->plug->query
        );
        $this->assertEquals(array('TestTable', 'id', 123, 'name', 'Mike', 'phone', '123-123', 'isPublished', 'category', 10, 20, 'alias' => 'TestTable'), $this->plug->arguments);
    }
}
