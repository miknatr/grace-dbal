<?php

namespace Grace\Tests\SQLBuilder;

use Grace\DBAL\Mysqli\SqlDialect;
use Grace\Tests\SQLBuilder\Plug\ExecutablePlug;
use Grace\Tests\SQLBuilder\Plug\WhereBuilderAbstractChild;

class WhereBuilderAbstractTest extends \PHPUnit_Framework_TestCase
{
    /** @var WhereBuilderAbstractChild */
    protected $builder;
    /** @var ExecutablePlug */
    protected $plug;

    protected function setUp()
    {
        $this->plug    = new ExecutablePlug(new SqlDialect);
        $this->builder = new WhereBuilderAbstractChild('TestTable', $this->plug);
    }
    protected function tearDown()
    {
    }
    public function testWithoutConditions()
    {
        $this->assertEquals('', $this->builder->getWhereSql());
        $this->assertEquals(array(), $this->builder->getQueryArguments());
    }
    public function testOneCondition()
    {
        $this->builder->eq('id', 123);
        $this->assertEquals(' WHERE ?f:alias:.?f=?q', $this->builder->getWhereSql());
        $this->assertEquals(array('id', 123), $this->builder->getQueryArguments());
    }
    public function testAllConditions()
    {
        $this->builder
            ->eq('id', 1)
            ->notEq('id', 2)
            ->gt('id', 3)
            ->gtEq('id', 4)
            ->lt('id', 5)
            ->ltEq('id', 6)
            ->like('name', 'Mike')
            ->notLike('name', 'John')
            ->likeInPart('lastname', 'Li')
            ->notLikeInPart('lastname', 'Fu')
            ->in('category', array(1, 2, 3, 4, 5))
            ->notIn('category', array(6, 7, 8, 9, 0))
            ->between('id', 7, 8)
            ->notBetween('id', 9, 10)
            ->sql('(?f > ?q OR ?f < ?q)', array('id', 100, 'id', 200));
        $this->assertEquals(
            ' WHERE ?f:alias:.?f=?q AND ?f:alias:.?f!=?q'
            . ' AND ?f:alias:.?f>?q AND ?f:alias:.?f>=?q AND ?f:alias:.?f<?q AND ?f:alias:.?f<=?q'
            . ' AND ?f:alias:.?f LIKE ?q AND ?f:alias:.?f NOT LIKE ?q'
            . ' AND ?f:alias:.?f LIKE ?q AND ?f:alias:.?f NOT LIKE ?q'
            . ' AND ?f:alias:.?f IN (?q,?q,?q,?q,?q)'
            . ' AND ?f:alias:.?f NOT IN (?q,?q,?q,?q,?q)'
            . ' AND ?f:alias:.?f BETWEEN ?q AND ?q AND ?f:alias:.?f NOT BETWEEN ?q AND ?q'
            . ' AND (?f > ?q OR ?f < ?q)',
            $this->builder->getWhereSql()
        );
        $this->assertEquals(
            array(
                'id',
                1,
                'id',
                2,
                'id',
                3,
                'id',
                4,
                'id',
                5,
                'id',
                6,
                'name',
                'Mike',
                'name',
                'John',
                'lastname',
                '%Li%',
                'lastname',
                '%Fu%',
                'category',
                1,
                2,
                3,
                4,
                5,
                'category',
                6,
                7,
                8,
                9,
                0,
                'id',
                7,
                8,
                'id',
                9,
                10,
                'id',
                100,
                'id',
                200
            ),
            $this->builder->getQueryArguments()
        );
    }
}
