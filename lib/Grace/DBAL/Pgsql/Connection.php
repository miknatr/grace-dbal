<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Alex Polev <alex.v.polev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\Pgsql;

use Doctrine\DBAL\Types\ConversionException;
use Grace\DBAL\ConnectionAbstract\ConnectionAbstract;
use Grace\DBAL\Exception\ConnectionException;
use Grace\DBAL\Exception\QueryException;
use Grace\DBAL\Pgsql\Result;

/**
 * Pg connection concrete class
 */
class Connection extends ConnectionAbstract
{
    private $resource;
    private $lastResult;
    private $transactionProcess = false;
    private $host;
    private $port;
    private $user;
    private $password;
    private $database;

    /**
     * Creates connection instance
     * All parameters are necessary
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $database
     */
    public function __construct($host, $port, $user, $password, $database)
    {
        $this->host     = $host;
        $this->port     = $port;
        $this->user     = $user;
        $this->password = $password;
        $this->database = $database;
    }

    /**
     * @inheritdoc
     */
    public function execute($query, array $arguments = array())
    {
        //define if it command or fetch query
        $needResult = preg_match('/^(SELECT)/i', ltrim($query));

        $query = $this->replacePlaceholders($query, $arguments);

        if (!is_resource($this->resource)) {
            $this->connect();
        }

        $this->getLogger()->startQuery($query);
        $this->lastResult = @pg_query($this->resource, $query);
        $this->getLogger()->stopQuery();

        if ($this->lastResult === false) {
            $error = pg_last_error($this->resource);
            if ($this->transactionProcess) {
                $this->rollback();
            }
            throw new QueryException("Query error: " . $error . "\nSQL:\n" . $query);
        } elseif ($needResult) {
            return new Result($this->lastResult);
        } else {
            return true;
        }
    }

    /**
     * @inheritdoc
     */
    public function escape($value)
    {
        if (!is_resource($this->resource)) {
            $this->connect();
        }
        return pg_escape_string($value);
    }

    /**
     * @inheritdoc
     */
    public function escapeField(array $names)
    {
        if (!is_resource($this->resource)) {
            $this->connect();
        }
        $escapeSymbol = '"'; // '`'
        $separator    = '.';
        $r = '';
        foreach ($names as $value) {
            if (!is_scalar($value) || strpos($escapeSymbol, $value)) {
                throw new QueryException('Possible SQL injection in field name');
            }
            $r .= $separator . $escapeSymbol . $value . $escapeSymbol;
        }
        return substr($r, strlen($separator));
    }

    /**
     * @inheritdoc
     */
    public function getAffectedRows()
    {
        if (!is_resource($this->lastResult)) {
            return false;
        }

        return pg_affected_rows($this->lastResult);
    }
    /**
     * @inheritdoc
     */
    public function start()
    {
        if (!is_resource($this->resource)) {
            $this->connect();
        }
        pg_query($this->resource, 'START TRANSACTION');
        $this->transactionProcess = true;
    }
    /**
     * @inheritdoc
     */
    public function commit()
    {
        pg_query($this->resource, 'COMMIT;');
        $this->transactionProcess = false;
    }
    /**
     * @inheritdoc
     */
    public function rollback()
    {
        pg_query($this->resource, 'ROLLBACK;');
        $this->transactionProcess = false;
    }

    //TODO поговорить на тему getLastInsertId который крайне криво реализуется для PostgresSQL
    //В принципе если передать в getLastInsertId имя таблицы и договориться SEQUENCE для всех таблиц именовать
    //Так как они именуются при автогенерации при использовании field_name SERIAL, то это вполне решаемо.
    // Но в таком случае у нас все равно рассыпается интерфейс, т.к. в обычном случае контекст не нужно передавать,
    // А сюда надо.
    public function getLastInsertId()
    {
        throw new ConnectionException('Undefined behavior');
    }

    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        $this->close();
    }
    /**
     * Establishes connection
     * @throws ConnectionException
     */
    private function close()
    {
        if (is_resource($this->resource)) {
            pg_close($this->resource);
            $this->resource = null;
        }
    }
    /**
     * Establishes connection
     * @throws \Grace\DBAL\Exception\ConnectionException
     */
    private function connect($selectDb = true)
    {
        if (!$this->isPhpEnvironmentSupported()) {
            throw new ConnectionException("Function pg_connect doesn't exist");
        }

        //Can throw warning, if have incorrect connection params
        //So we need '@'
        $this
            ->getLogger()
            ->startConnection('Pgsql connection');
        $connectString = $this->generateConnectionString($selectDb);
        $this->resource = @\pg_connect($connectString);
        $this
            ->getLogger()
            ->stopConnection();

        if (!$this->resource) {
            $error = \error_get_last();
            throw new ConnectionException('Error ' . $error['message']);
        }
    }

    private function generateConnectionString($selectDb = true)
    {
        return "host={$this->host} port={$this->port} user={$this->user} password={$this->password}"
            . ($selectDb ? " dbname={$this->database}" : '')
            . " options='--client_encoding=UTF8'";
    }
    /**
     * @inheritdoc
     */
    public function createDatabaseIfNotExist()
    {
        $this->connect(false);
        $isExist = $this->execute("SELECT ?f FROM ?f WHERE ?f=?q", array('datname', 'pg_database', 'datname', $this->database))->fetchResult();
        if (!$isExist) {
            $this->execute('CREATE DATABASE ?f', array($this->database));
        }
        $this->close();
        $this->connect();
    }

    public function isPhpEnvironmentSupported()
    {
        return function_exists('pg_connect');
    }

    public function generateNewId($table)
    {
        return $this->execute('SELECT nextval(\'?f\')', array($table . '_id_seq'))->fetchResult();
    }
}
