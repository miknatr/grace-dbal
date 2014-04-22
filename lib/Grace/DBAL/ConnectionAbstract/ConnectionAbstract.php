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
            $queries           = $connection->getLogger()->getQueries();

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
            $indent        = str_repeat(' ', 4 * $currentIndent);
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
            $class            = '\\' . substr(get_class($this), 0, -strlen('Connection')) . 'SqlDialect';
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

    protected $fieldCache = array();

    public function replacePlaceholders($query, array $arguments)
    {
        $positionalIndex = 0;

        $parts  = explode('?', $query);
        $length = count($parts);
        for ($i = 1; $i < $length; $i++) {
            $part =& $parts[$i];
            $type = substr($part, 0, 1);

            if (substr($part, 1, 1) == ':') {
                // named placeholder ?q:name:
                $end  = strpos($part, ':', 2);
                $name = substr($part, 2, $end - 2);
                $part = substr($part, $end + 1);

                if (!array_key_exists($name, $arguments)) {
                    throw new QueryException("Placeholder named '{$name}' is not presented in \$arguments\n{$query}\n" . print_r($arguments, true));
                }

                $value = $arguments[$name];
            } else {
                // positional placeholder ?q
                $part = substr($part, 1);

                if (!array_key_exists($positionalIndex, $arguments)) {
                    throw new QueryException("Placeholder number '{$positionalIndex}' is not presented in \$arguments\n{$query}\n" . print_r($arguments, true));
                }

                $value = $arguments[$positionalIndex];

                $positionalIndex++;
            }

            if (is_scalar($value)) {
                switch ($type) {
                    case 'q':
                        $part = "'" . $this->escape($value) . "'" . $part;
                        break;

                    case 'f':
                    case 'F':
                        if (!isset($this->fieldCache[$value])) {
                            $this->fieldCache[$value] = $this->escapeField($value);
                        }
                        $part = $this->fieldCache[$value] . $part;
                        break;

                    case 'b':
                        $part = "'" . $this->escape((bool) $value) . "'" . $part;
                        break;

                    case 'p':
                        $part = $value . $part;
                        break;

                    case 'e':
                        $part = $this->escape($value) . $part;
                        break;

                    case 'a': // postgres array syntax
                    case 'l': // comma separated values (example: IN (?l), array('1', '2') => IN ('1', '2'))
                    case 'v':
                    case 'i': // comma separated field names (as example - insert queries)
                        throw new QueryException('Value must be array: ' . var_export($value, true));

                    default:
                        throw new QueryException('Placeholder has incorrect type: ' . $type);
                }
            } elseif ($value === null) {
                switch ($type) {
                    case 'e':
                    case 'q':
                        $part = 'null' . $part;
                        break;

                    case 'b':
                    case 'p':
                    case 'f':
                    case 'F':
                    case 'a': // postgres array syntax
                    case 'l': // comma separated values (example: IN (?l), array('1', '2') => IN ('1', '2'))
                    case 'v':
                    case 'i': // comma separated field names (as example - insert queries)
                        throw new QueryException("Value of type {$type} must not be null");

                    default:
                        throw new QueryException("Placeholder has incorrect type: {$type}");
                }
            } elseif (is_array($value)) {
                switch ($type) {
                    case 'b':
                    case 'p':
                    case 'e':
                    case 'q':
                    case 'f':
                    case 'F':
                        throw new QueryException("Value of type $type must be string: " . var_export($value, true));

                    case 'a': // postgres array syntax
                        $part = "'{" . implode(', ', $value) . "}'" . $part;
                        break;

                    case 'l': // comma separated values (example: IN (?l), array('1', '2') => IN ('1', '2'))
                        $sql = '';
                        foreach ($value as $v) {
                            $sql .= $v === null ? ", null" : ", '" . $this->escape($v) . "'";
                        }
                        $part = substr($sql, 2) . $part;
                        break;

                    case 'v':
                        $sql = '';
                        foreach ($value as $list) {
                            $sql .= ', (';

                            $listSql = '';
                            foreach ($list as $v) {
                                $listSql .= $v === null ? ", null" : ", '" . $this->escape($v) . "'";
                            }
                            $sql .= substr($listSql, 2);

                            $sql .= ')';
                        }
                        $part = substr($sql, 2) . $part;
                        break;

                    case 'i': // comma separated field names (as example - insert queries)
                        $sql = '';
                        foreach ($value as $v) {
                            $sql .= ', ' . $this->escapeField($v);
                        }
                        $part = substr($sql, 2) . $part;
                        break;

                    default:
                        throw new QueryException("Placeholder has incorrect type: {$type}");
                }
            } else {
                throw new QueryException("Value of type {$type} is incorrect: " . var_export($value, true));
            }
        }
        return join('', $parts);
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
                $key = 'grace_id_gen_' . $table . '_' . strval($this->idCounterByTable[$table]);

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
