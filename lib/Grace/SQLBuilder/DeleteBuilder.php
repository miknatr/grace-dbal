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

/**
 * Delete sql builder
 */
class DeleteBuilder extends WhereBuilderAbstract
{
    /**
     * @inheritdoc
     */
    protected function getQueryString()
    {
        return 'DELETE FROM ?f' . $this->getWhereSql();
    }
    /**
     * @inheritdoc
     */
    protected function getQueryArguments()
    {
        $arguments = parent::getQueryArguments();
        array_unshift($arguments, $this->from);
        return $arguments;
    }
}