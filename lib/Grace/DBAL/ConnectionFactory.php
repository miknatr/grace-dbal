<?php

namespace Grace\DBAL;

use Grace\DBAL\ConnectionAbstract\ConnectionInterface;
use Grace\DBAL\Mysqli\Connection as MysqliConnection;
use Grace\DBAL\Pgsql\Connection as PgsqlConnection;
use Grace\DBAL\PdoPgsql\Connection as PdoPgsqlConnection;

class ConnectionFactory
{
    /**
     * @param array $config
     * @return ConnectionInterface
     * @throws \LogicException
     */
    static public function getConnection(array $config)
    {
        switch ($config['adapter']) {
            case 'mysqli':
                return new MysqliConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['database']);
            case 'pgsql':
                return new PgsqlConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['database']);
            case 'pdo_pgsql':
                return new PdoPgsqlConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['database']);
        }

        throw new \LogicException('Unsupported adapter type ' . $config['adapter']);
    }
}
