<?php

/**
 * WithTransactions trait
 *
 * use this trait when you need a datahandler with
 * PDO transactions support
 *
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\AMA\Traits;

trait WithTransactions
{
    /**
     * PDO::beginTransaction wrapper
     *
     * @return bool
     */
    protected function beginTransaction()
    {
        return $this->getConnection()->connectionObject()->beginTransaction();
    }

    /**
     * PDO::rollBack wrapper
     *
     * @return bool
     */
    protected function rollBack()
    {
        return $this->getConnection()->connectionObject()->rollBack();
    }

    /**
     * PDO::commit wrapper
     *
     * @return bool
     */
    protected function commit()
    {
        return $this->getConnection()->connectionObject()->commit();
    }
}
