<?php

namespace Grace\DBAL;

use Grace\DBAL\ConnectionAbstract\ConnectionInterface;
use Grace\DBAL\Mysqli\Connection as MysqliConnection;
use Grace\DBAL\Pgsql\Connection as PgsqlConnection;

class ConnectionFactory
{
    /**
     * @param array $config
     * @return ConnectionInterface
     * @throws \LogicException
     */
    static public function getConnection(array $config)
    {
        if (empty($config['adapter'])) {
            throw new \LogicException('Adapter type must be defined');
        }

        switch ($config['adapter']) {
            case 'mysqli':
                return new MysqliConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['database']);
            case 'pgsql':
                return new PgsqlConnection($config['host'], $config['port'], $config['user'], $config['password'], $config['database']);
        }

        throw new \LogicException('Unsupported adapter type ' . $config['adapter']);
    }
}
