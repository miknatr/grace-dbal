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

/**
 * Pg connection concrete class
 */
class Connection extends ConnectionAbstract
{
    private $resource;
    /** @var Result */
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
        $query = $this->replacePlaceholders($query, $arguments);

        if (!is_resource($this->resource)) {
            $this->connect();
        }

        $this->getLogger()->startQuery($query);
        $result = @pg_query($this->resource, $query);
        $this->getLogger()->stopQuery();

        if ($result === false) {
            $error = pg_last_error($this->resource);
            if ($this->transactionProcess) {
                $this->rollback();
            }
            throw new QueryException("Query error: " . $error . "\nSQL:\n" . $query);
        } else {
            return $this->lastResult = new Result($result);
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
        return pg_escape_string($this->resource, $value);
    }

    /**
     * @inheritdoc
     */
    public function escapeField($names)
    {
        if (!is_resource($this->resource)) {
            $this->connect();
        }

        $separator = '.';

        $r = '';
        foreach (explode('.', $names) as $value) {
            $r .= $separator . pg_escape_identifier($this->resource, $value);
        }
        return substr($r, strlen($separator));
    }

    /**
     * @inheritdoc
     */
    public function getAffectedRows()
    {
        return $this->lastResult->getAffectedRows();
    }

    /**
     * @inheritdoc
     */
    public function start()
    {
        $this->execute('START TRANSACTION');
        $this->transactionProcess = true;
    }

    /**
     * @inheritdoc
     */
    public function commit()
    {
        $this->execute('COMMIT');
        $this->transactionProcess = false;
    }

    /**
     * @inheritdoc
     */
    public function rollback()
    {
        $this->execute('ROLLBACK');
        $this->transactionProcess = false;
    }

    public function getLastInsertId()
    {
        // postgres problem
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
     * @throws ConnectionException
     */
    private function connect()
    {
        if (!$this->isPhpEnvironmentSupported()) {
            throw new ConnectionException("Function pg_connect doesn't exist");
        }

        $this->getLogger()->startConnection('Pgsql connection');

        $connectString  = "host={$this->host} port={$this->port} user={$this->user} password={$this->password} dbname={$this->database} options='--client_encoding=UTF8'";
        //Can throw warning, if have incorrect connection params. So we need '@'
        $this->resource = @\pg_connect($connectString);

        $this->getLogger()->stopConnection();

        if (!$this->resource) {
            $error = \error_get_last();
            throw new ConnectionException('Error ' . $error['message']);
        }
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
