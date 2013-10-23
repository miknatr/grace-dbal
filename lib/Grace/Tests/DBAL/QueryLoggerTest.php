<?php

namespace Grace\Tests\DBAL;

use Grace\DBAL\QueryLogger;

class QueryLoggerTest extends \PHPUnit_Framework_TestCase
{
    /** @var QueryLogger */
    protected $logger;

    protected function setUp()
    {
        $this->logger = new QueryLogger();
    }
    public function testEmptyLogger()
    {
        $r = $this->logger->getQueries();
        $this->assertEquals(array(), $r);
    }
    public function testQueriesLogger()
    {
        $this->logger->startQuery('q1');
        $this->logger->stopQuery();

        $this->logger->startQuery('q2');
        $this->logger->stopQuery();

        $r = $this->logger->getQueries();

        $this->assertEquals('q1', $r[0]['query']);
        $this->assertTrue($r[0]['time'] > 0);

        $this->assertEquals('q2', $r[1]['query']);
        $this->assertTrue($r[1]['time'] > 0);
    }
}
