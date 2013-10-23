<?php
/*
 * This file is part of the Grace package.
 *
 * (c) Mikhail Natrov <miknatr@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grace\SQLBuilder\SqlValue;

/**
 * Custom sql value for usage in insert or update queries
 */
interface SqlValueInterface
{
    /**
     * @return string
     */
    public function getSql();
    /**
     * @return array
     */
    public function getValues();
}