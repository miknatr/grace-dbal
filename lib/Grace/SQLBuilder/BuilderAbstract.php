<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\SQLBuilder;

use Grace\DBAL\ConnectionAbstract\ExecutableInterface;
use Grace\DBAL\ConnectionAbstract\ResultInterface;

/**
 * Provides some base functions for builders
 */
abstract class BuilderAbstract implements ResultInterface
{
    /** @var \Grace\DBAL\ConnectionAbstract\ExecutableInterface */
    protected $executable;
    private $result;
    protected $from;
    protected $alias;

    /**
     * @param                                 $fromTable
     * @param \Grace\DBAL\ConnectionAbstract\ExecutableInterface $executable
     */
    public function __construct($fromTable, ExecutableInterface $executable)
    {
        $this->setFrom($fromTable);
        $this->executable = $executable;
    }
    /**
     * @return $this
     */
    public function getClone()
    {
        return clone $this;
    }
    /**
     * @param $fromTable
     * @return $this
     */
    public function setFrom($fromTable)
    {
        //TODO сделать возможность менять алиас пост-фактум, чтобы при этом билдер не разваливался (в джоинах и тд)
        $this->from = $fromTable;
        if (empty($this->alias)) {
            $this->alias = $fromTable;
        }
        return $this;
    }
    /**
     * @param $alias
     * @return $this
     */
    public function setFromAlias($alias)
    {
        $this->alias = $alias;
        return $this;
    }
    /**
     * @return ResultInterface
     */
    public function execute()
    {
        if (!$this->result) {
            $this->result = $this->executable->execute(
                $this->getQueryString(),
                array_merge(array('alias' => $this->alias), $this->getQueryArguments())
            );
        }

        return $this->result;
    }
    /**
     * @inheritdoc
     */
    public function fetchAll()
    {
        return $this
            ->execute()
            ->fetchAll();
    }
    /**
     * @inheritdoc
     */
    public function fetchOneOrFalse()
    {
        return $this
            ->execute()
            ->fetchOneOrFalse();
    }
    /**
     * @inheritdoc
     */
    public function fetchResult()
    {
        return $this
            ->execute()
            ->fetchResult();
    }
    /**
     * @inheritdoc
     */
    public function fetchColumn()
    {
        return $this
            ->execute()
            ->fetchColumn();
    }
    /**
     * @inheritdoc
     */
    public function fetchHash()
    {
        return $this
            ->execute()
            ->fetchHash();
    }
    /**
     * @abstract
     * @return string sql query string
     */
    abstract protected function getQueryString();
    /**
     * @abstract
     * @return array arguments for sql query
     */
    abstract protected function getQueryArguments();
}

