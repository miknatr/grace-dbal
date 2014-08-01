<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Alex Polev <alex.v.polev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\PdoPgsql;

use Doctrine\DBAL\Types\ConversionException;
use Grace\DBAL\ConnectionAbstract\ConnectionAbstract;
use Grace\DBAL\Exception\ConnectionException;
use Grace\DBAL\Exception\QueryException;

/**
 * Pg connection concrete class
 */
class Connection extends ConnectionAbstract
{
    /** @var \PDO */
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

        if (!$this->resource) {
            $this->connect();
        }

        $this->getLogger()->startQuery($query);
        $result = $this->resource->query($query);
        $this->getLogger()->stopQuery();

        if ($result === false) {
            if ($this->transactionProcess) {
                $this->rollback();
            }
            throw new QueryException("Query error: " . print_r($this->resource->errorInfo(), true) . "\nSQL:\n" . $query);
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
        // PDO::quote adds quotes around string
        // So we use unsafe code for postgres only because of double quote escaping
        return str_replace("'", "''", $value);
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
            // I don't know how to do it right
            $r .= $separator . '"' . str_replace('"', '""', $value) . '"';
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
        if ($this->resource) {
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
            throw new ConnectionException("Extension pdo_pgsql doesn't exist");
        }

        try {
            $this->getLogger()->startConnection('PdoPgsql connection');

            // STOPPER charset=UTF8
            $connectString  = "pgsql:host={$this->host};port={$this->port};dbname={$this->database};user={$this->user};password={$this->password}";

            $this->resource = new \PDO($connectString);
            $this->resource->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_SILENT);

            $this->getLogger()->stopConnection();
        } catch (\PDOException $e) {
            throw new ConnectionException('Error ' . $e->getMessage());
        }
    }

    public function isPhpEnvironmentSupported()
    {
        return extension_loaded('pdo_pgsql');
    }

    public function generateNewId($table)
    {
        return $this->execute('SELECT nextval(\'?f\')', array($table . '_id_seq'))->fetchResult();
    }
}
