<?php

namespace Grace\Tests\DBAL;

use Grace\DBAL\Mysqli\Connection;
use Grace\DBAL\Exception\ConnectionException;
use Grace\DBAL\Exception\QueryException;

class MysqliConnectionTest extends ConnectionTestAbstract
{
    /** @var Connection */
    protected $connection;

    protected function setUp()
    {
        $this->connection = new Connection(TEST_MYSQLI_HOST, TEST_MYSQLI_PORT, TEST_MYSQLI_NAME, TEST_MYSQLI_PASSWORD, TEST_MYSQLI_DATABASE);
        if (!$this->connection->isPhpEnvironmentSupported()) {
            $this->markTestSkipped('No mysqli support in php');
        }

        try {
            $this->connection->execute('SELECT 1');
        } catch (ConnectionException $e) {
            $this->fail('You need to set up MySQL login/password in config.php which is located in grace root');
        }
    }
    protected function tearDown()
    {
        unset($this->connection);
    }

    public function testBadConnectionConfig()
    {
        unset($this->connection);
        $this->connection = new Connection(TEST_MYSQLI_HOST, TEST_MYSQLI_PORT, 'SOME BAD NAME', TEST_MYSQLI_PASSWORD, TEST_MYSQLI_DATABASE);
        //Lazy connection, only if we really use database
        try {
            $this->connection->execute('SELECT 1');
        } catch (ConnectionException $ignored) {}
    }
    public function testGettingLastInsertIdBeforeConnectionEsbablished()
    {
        $this->assertEquals(false, $this->connection->getLastInsertId());
    }
    public function testGettingLastInsertId()
    {
        $this->connection->execute('DROP TABLE IF EXISTS test');
        $this->connection->execute('CREATE TABLE test (id INT(10) PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255))');
        $this->connection->execute("INSERT INTO test VALUES (10, 'Mike')");
        $this->assertEquals('10', $this->connection->getLastInsertId());
        $this->connection->execute('DROP TABLE IF EXISTS test');
    }
    public function testGettingAffectedRowsBeforeConnectionEsbablished()
    {
        $this->connection = new Connection(TEST_MYSQLI_HOST, TEST_MYSQLI_PORT, TEST_MYSQLI_NAME, TEST_MYSQLI_PASSWORD, TEST_MYSQLI_DATABASE);
        $this->assertEquals(false, $this->connection->getAffectedRows());
    }
    public function testGettingAffectedRows()
    {
        $this->connection->execute('DROP TABLE IF EXISTS test');
        $this->connection->execute('CREATE TABLE test (id INT(10) PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255))');
        $this->connection->execute("INSERT INTO test VALUES (1, 'Mike')");
        $this->connection->execute("INSERT INTO test VALUES (2, 'John')");
        $this->connection->execute("INSERT INTO test VALUES (3, 'Bill')");
        $this->connection->execute("UPDATE test SET name='Human'");
        $this->assertEquals(3, $this->connection->getAffectedRows());
        $this->connection->execute('DROP TABLE IF EXISTS test');
    }
    public function testFieldEscaping()
    {
        $r = $this->connection->escapeField(array('field'));
        $this->assertEquals('"field"', $r);
    }
    public function testEscaping()
    {
        $r = $this->connection->escape("quote ' quote");
        $this->assertEquals("quote \\' quote", $r);
    }
    public function testReplacingPlaceholders()
    {
        $r = $this->connection->replacePlaceholders("SELECT ?q, '?e', \"?p\", ?f, ?F, ?l, ?i, ?q:named_pl: FROM DUAL",
            array(
                '\'quoted\'',
                '\'escaped\'',
                '\'plain\'',
                'test',
                'test1.test2',
                array('t1', 't2'),
                array('f1', 'f2'),
                'named_pl' => '\'named quoted\'',
            ));
        $this->assertEquals("SELECT '\\'quoted\\'', '\\'escaped\\'', \"'plain'\", \"test\", \"test1\".\"test2\", 't1', 't2', \"f1\", \"f2\", '\\'named quoted\\'' FROM DUAL", $r);
    }
    public function testTransactionCommit()
    {
        $this->connection->execute('DROP TABLE IF EXISTS test');
        $this->connection->execute('CREATE TABLE test (id INT(10) PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255)) ENGINE=InnoDB');
        $this->connection->start();
        $this->connection->execute("INSERT INTO test VALUES (1, 'Mike')");
        $this->connection->execute("INSERT INTO test VALUES (2, 'John')");
        $this->connection->execute("INSERT INTO test VALUES (3, 'Bill')");
        $this->connection->commit();
        $r = $this->connection
            ->execute('SELECT COUNT(id) FROM test')
            ->fetchResult();
        $this->assertEquals('3', $r);
        $this->connection->execute('DROP TABLE IF EXISTS test');
    }
    public function testTransactionManualRollback()
    {
        $this->connection->execute('DROP TABLE IF EXISTS test');
        $this->connection->execute('CREATE TABLE test (id INT(10) PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255)) ENGINE=InnoDB');
        $this->connection->start();
        $this->connection->execute("INSERT INTO test VALUES (1, 'Mike')");
        $this->connection->execute("INSERT INTO test VALUES (2, 'John')");
        $this->connection->execute("INSERT INTO test VALUES (3, 'Bill')");
        $this->connection->rollback();
        $r = $this->connection
            ->execute('SELECT COUNT(id) FROM test')
            ->fetchResult();
        $this->assertEquals('0', $r);
        $this->connection->execute('DROP TABLE IF EXISTS test');
    }
    public function testTransactionRollbackOnError()
    {
        $this->connection->execute('DROP TABLE IF EXISTS test');
        $this->connection->execute('CREATE TABLE test (id INT(10) PRIMARY KEY AUTO_INCREMENT, name VARCHAR(255)) ENGINE=InnoDB');
        $this->connection->start();
        $this->connection->execute("INSERT INTO test VALUES (1, 'Mike')");
        $this->connection->execute("INSERT INTO test VALUES (2, 'John')");
        $this->connection->execute("INSERT INTO test VALUES (3, 'Bill')");
        try {
            $this->connection->execute('NO SQL SYNTAX');
        } catch (QueryException $e) {
            ;
        }
        $r = $this->connection
            ->execute('SELECT COUNT(id) FROM test')
            ->fetchResult();
        $this->assertEquals('0', $r);
        $this->connection->execute('DROP TABLE IF EXISTS test');
    }
}
