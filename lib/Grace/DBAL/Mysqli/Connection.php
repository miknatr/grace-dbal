<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\Mysqli;

use Grace\DBAL\ConnectionAbstract\ConnectionAbstract;
use Grace\DBAL\Exception\ConnectionException;
use Grace\DBAL\Exception\QueryException;
use Grace\DBAL\Mysqli\Result;

/**
 * Mysqli connection concrete class
 */
class Connection extends ConnectionAbstract
{
    /** @var \mysqli */
    private $dbh;
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
     * @return bool|Result
     * @throws \Grace\DBAL\Exception\QueryException
     */
    public function execute($query, array $arguments = array())
    {
        $query = $this->replacePlaceholders($query, $arguments);

        if (!is_object($this->dbh)) {
            $this->connect();
        }

        $this
            ->getLogger()
            ->startQuery($query);
        $result = $this->dbh->query($query);
        $this
            ->getLogger()
            ->stopQuery();

        if ($result === false) {
            if ($this->transactionProcess) {
                $this->rollback();
            }
            throw new QueryException('Query error ' . $this->dbh->errno . ' - ' . $this->dbh->error . ". \nSQL:\n" . $query);
        }

        if (is_object($result)) {
            /** @var \mysqli_result $result */
            return new Result($result);
        }

        return true;
    }
    /**
     * @inheritdoc
     */
    public function escape($value)
    {
        if (!is_object($this->dbh)) {
            $this->connect();
        }
        return $this->dbh->real_escape_string($value);
    }
    /**
     * @inheritdoc
     */
    public function escapeField(array $names)
    {
        if (!is_object($this->dbh)) {
            $this->connect();
        }
        $escapeSymbol = '"'; // MySQL is in ANSI mode
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
    public function getLastInsertId()
    {
        if (!is_object($this->dbh)) {
            return false;
        }
        return $this->dbh->insert_id;
    }
    /**
     * @inheritdoc
     */
    public function getAffectedRows()
    {
        if (!is_object($this->dbh)) {
            return false;
        }
        return $this->dbh->affected_rows;
    }
    /**
     * @inheritdoc
     */
    public function start()
    {
        if (!is_object($this->dbh)) {
            $this->connect();
        }
        $this->dbh->autocommit(false);
        $this->transactionProcess = true;
    }
    /**
     * @inheritdoc
     */
    public function commit()
    {
        $this->dbh->commit();
        $this->dbh->autocommit(true);
        $this->transactionProcess = false;
    }
    /**
     * @inheritdoc
     */
    public function rollback()
    {
        $this->dbh->rollback();
        $this->dbh->autocommit(true);
        $this->transactionProcess = false;
    }
    /**
     * @inheritdoc
     */
    public function __destruct()
    {
        if (is_object($this->dbh)) {
            $this->dbh->close();
        }
    }
    /**
     * Establishes connection
     * @throws \Grace\DBAL\Exception\ConnectionException
     */
    private function connect($selectDb = true)
    {
        if (!$this->isPhpEnvironmentSupported()) {
            throw new ConnectionException("Function mysqli_connect doesn't exist");
        }

        //Can throw warning, if have incorrect connection params
        //So we need '@'
        $this->getLogger()->startConnection('Mysqli connection');
        $this->dbh = @mysqli_connect($this->host, $this->user, $this->password, $selectDb ? $this->database : null, (int)$this->port);
        $this->getLogger()->stopConnection();

        if (mysqli_connect_error()) {
            throw new ConnectionException('Error ' . mysqli_connect_errno() . ' - ' . mysqli_connect_error());
        }

        $this->getLogger()->startConnection('Setting utf8 charset');
        $this->dbh->query("SET character SET 'utf8'");
        $this->dbh->query('SET character_set_client = utf8');
        $this->dbh->query('SET character_set_results = utf8');
        $this->dbh->query('SET character_set_connection = utf8');
        $this->dbh->query("SET SESSION collation_connection = 'utf8_general_ci'");
        $this->dbh->query("SET sql_mode = 'ANSI'");
        $this->getLogger()->stopConnection();
    }
    /**
     * @inheritdoc
     */
    public function createDatabaseIfNotExist()
    {
        $this->connect(false);
        $this->execute('CREATE DATABASE IF NOT EXISTS ?f', array($this->database));
        $this->dbh->select_db($this->database);
    }

    public function isPhpEnvironmentSupported()
    {
        return function_exists('pg_connect');
    }
}
