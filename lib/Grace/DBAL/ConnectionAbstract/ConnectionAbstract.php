<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natarov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\DBAL\ConnectionAbstract;

use Grace\DBAL\Exception\QueryException;
use Grace\DBAL\ConnectionAbstract\ConnectionInterface;
use Grace\DBAL\QueryLogger;
use Grace\SQLBuilder\Factory;
use Grace\Cache\CacheInterface;

/**
 * Provides some base functions for concrete connection classes
 */
abstract class ConnectionAbstract implements ConnectionInterface
{
    /** @var array ConnectionAbstract */
    private static $connections = array();
    public function __construct()
    {
        self::$connections[] = $this;
    }

    public static function debug($onlySlow = false)
    {
        foreach (self::$connections as $connection) {
            /** @var ConnectionAbstract $connection */
            $connectionQueries = $connection->getLogger()->getConnections();
            $queries = $connection->getLogger()->getQueries();

            foreach ($connectionQueries as $row) {
                if ($onlySlow && $row['time'] < 0.05) {
                    continue;
                }

                echo "{$row['time']}\n";
                $query = self::formatQuery($row['query']);
                echo "$query\n";
                echo "\n";
            }

            foreach ($queries as $row) {
                if ($onlySlow && $row['time'] < 0.05) {
                    continue;
                }

                echo "{$row['time']}\n";
                $query = self::formatQuery($row['query']);
                echo "$query\n";
                echo "\n";
            }
        }
    }

    // TODO http://github.com/jdorn/sql-formatter
    private static function formatQuery($sql)
    {
        $insertIndent = function ($level, $text) {
            $indent = str_repeat(' ', 4 * $level);
            return preg_replace("/\n +|(\band\b|\bor\b)/i", "\n" . $indent . '\1', $text);
        };

        // сначала делаем переводы строк
        $sql = preg_replace('/SELECT|UPDATE|DELETE|SET|WHERE|FROM|USING|LEFT|INNER|OUTER|RIGHT|ORDER|GROUP|HAVING|LIMIT/', "\n$0", $sql);
        $sql = preg_replace('/", /', "$0\n    ", $sql);

        // теперь бубеним скобочки

        // tokenizer
        $parts = preg_split('/([()])/', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
        // descend
        $currentIndent = 0;
        for ($i = 1; $i < count($parts); $i += 2) {
            $isOpening = $parts[$i] == '(';

            // next in $parts is the body after the paren
            // so what do we do:
            // (keke should become (<indent><\n>keke
            // )keke same
            // also ) should be newlined as well

            // special case: () with only one condition in it
            // we skip the bastard completely, but the part after the closing paren needs to be processed
            if ($isOpening && !preg_match('/\b(and|or)\b/i', $parts[$i + 1]) && !empty($parts[$i + 2]) && $parts[$i + 2] == ')') {
                $parts[$i + 3] = $insertIndent($currentIndent, $parts[$i + 3]);
                // skipping the closing paren
                $i += 2;
                continue;
            }

            $currentIndent += $isOpening ? 1 : -1;
            $indent = str_repeat(' ', 4 * $currentIndent);
            $parts[$i + 1] = "\n" . $indent . $insertIndent($currentIndent, $parts[$i + 1]);

            if (!$isOpening) {
                $parts[$i] = "\n" . $indent . $parts[$i];
            }
        }

        // removing whitespace lines
        return preg_replace("/\n\s*\n/", "\n", join('', $parts));
    }

    /** @var SqlDialectAbstract */
    protected $sqlDialect;
    /**
     * @return SqlDialectAbstract
     */
    public function provideSqlDialect()
    {
        if ($this->sqlDialect == null) {
            //hardcoded naming convention
            $class = '\\' . substr(get_class($this), 0, -strlen('Connection')) . 'SqlDialect';
            $this->sqlDialect = new $class;
        }

        return $this->sqlDialect;
    }

    /**
     * @var CacheInterface
     */
    private $cache;
    /**
     * @inheritdoc
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
        return $this;
    }
    /**
     * @inheritdoc
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @var QueryLogger
     */
    private $logger;
    /**
     * @inheritdoc
     */
    public function setLogger(QueryLogger $logger)
    {
        $this->logger = $logger;
        return $this;
    }
    /**
     * @inheritdoc
     */
    public function getLogger()
    {
        if (!is_object($this->logger)) {
            $this->setLogger(new QueryLogger());
        }
        return $this->logger;
    }
    /**
     * @inheritdoc
     */
    public function getSQLBuilder()
    {
        return new Factory($this);
    }
    /**
     * @inheritdoc
     */
    public function replacePlaceholders($query, array $arguments)
    {
        //firstly, we replace named placeholders like ?i:name: where "i" is escaping type and "name" is parameter name
        $onMatch = function($matches) use ($arguments, $query, $arguments) {
            if (!array_key_exists($matches[2], $arguments)) {
                throw new QueryException("Placeholder named '$matches[2]' is not presented in \$arguments\n$query\n" . print_r($arguments, true));
            }
            return $this->escapeValueByType($arguments[$matches[2]], $matches[1]);
        };
        $query = preg_replace_callback("(\?([a-zA-Z]{1}):([a-zA-Z0-9_]{0,100}):)", $onMatch, $query);

        //secondly, we replace ordered placeholders like ?i where "i" is escaping type
        $counter = -1;
        $onMatch = function($matches) use ($arguments, &$counter, $query, $arguments) {
            $counter++;
            if (!array_key_exists($counter, $arguments)) {
                throw new QueryException("Placeholder number '$counter' is not presented in \$arguments\n$query\n" . print_r($arguments, true));
            }
            return $this->escapeValueByType($arguments[$counter], $matches[1]);
        };
        $query = preg_replace_callback("(\?([a-zA-Z]{1}))", $onMatch, $query);

        return $query;
    }

    /**
     * Escapes value in compliance with type
     *
     * Possible values of $type:
     * "p" - plain value, no escaping
     * "e" - escaping by "db-escape" function, but not quoting
     * "q" - escaping by "db-escape" function and quoting
     * "l" - escaping by "db-escape" function and quoting for arrays ('1', '2', '3')
     * "v" - escaping by "db-escape" function and quoting for INSERT ... VALUES ('1', '2', '3'), ('1', '2', '3')
     * "f" - escaping by fully-qualified field name ('table.field' => `table`.`field`)
     * "F" - escaping by fully-qualified field name ('table.field' => `table`.`field`)
     * "i" - escaping by field name for arrays
     *
     * @param mixed $value
     * @param string $type
     * @throws \Grace\DBAL\Exception\QueryException
     * @return string
     */
    private function escapeValueByType($value, $type)
    {
        if ($type != 'a' and $type != 'l' and $type != 'i' and $type != 'v' and (is_object($value) or is_array($value))) {
            throw new QueryException('Value of type ' . $type . ' must be string: ' . print_r($value, true));
        }

        switch ($type) {
            case 'b':
                $r = (bool) $value ? "'t'" : "'f'";
                break;
            case 'p':
                $r = $value;
                break;
            case 'e':
                if (is_null($value)) {
                    return 'null';
                }
                $r = $this->escape($value);
                break;
            case 'q':
                if (is_null($value)) {
                    return 'null';
                }
                $r = "'" . $this->escape($value) . "'";
                break;
            case 'a': // postgres array syntax
                if (!is_array($value)) {
                    throw new QueryException('Value must be array: ' . print_r($value, true));
                }
                $r = "'{" . implode(', ', $value) . "}'";
                break;
            case 'l': // comma separated values (example: IN (?l), array('1', '2') => IN ('1', '2'))
                $r = '';
                if (!is_array($value)) {
                    throw new QueryException('Value must be array: ' . print_r($value, true));
                }
                foreach ($value as $part) {
                    $r .= is_null($part) ? ", null" : ", '" . $this->escape($part) . "'";
                }
                $r = substr($r, 2);
                break;
            case 'v':
                if (!is_array($value)) {
                    throw new QueryException('Value must be array: ' . print_r($value, true));
                }
                $r = '';
                foreach ($value as $part) {
                    $r .= ', (' . $this->escapeValueByType($part, 'l') .')';
                }
                $r = substr($r, 2);
                break;
            case 'f':
            case 'F':
                $r = $this->escapeField(explode('.', $value));
                break;
            case 'i': // comma separated field names (as example - insert queries)
                $r = '';
                if (!is_array($value)) {
                    throw new QueryException('Value must be array: ' . print_r($value, true));
                }
                foreach ($value as $part) {
                    $r .= ', ' . $this->escapeField(explode('.', $part));
                }
                $r = substr($r, 2);
                break;
            default:
                throw new QueryException('Placeholder has incorrect type: ' . $type);
        }
        return $r;
    }


    protected $idCounterByTable = array();

    /**
     * Generate new id for insert
     * @param string $table
     * @throws \OutOfBoundsException
     * @return mixed
     */
    public function generateNewId($table)
    {
        if (!isset($this->idCounterByTable[$table])) {
            $this->idCounterByTable[$table] = $this->getSQLBuilder()->select($table)->field('id')->orderDesc('id')->limit(0, 1)->fetchResult();
        }

        for ($i = 0; $i < 50; $i++) {
            $this->idCounterByTable[$table]++;

            if ($this->getCache()) {
                $key    = 'grace_id_gen_' . $table . '_' . strval($this->idCounterByTable[$table]);

                $isBusy = $this->getCache()->get($key);
                if ($isBusy === null) {
                    $this->getCache()->set($key, '1', 60);
                    return $this->idCounterByTable[$table];
                }
            } else {
                return $this->idCounterByTable[$table];
            }
        }

        throw new \OutOfBoundsException('Maximum number of cycles to generate new id has reached');
    }
}
