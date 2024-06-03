<?php

/**
 * APIException.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2024, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Controllers\Common;

use Exception;

/**
 * Empty class to define API's own Exception
 *
 * @author giorgio
 *
 */
class APIException extends Exception
{
    /**
     * Exception parameters.
     *
     * @var array
     */
    private $params = [];

    /**
     * Get exception parameters.
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Set exception parameters.
     */
    public function setParams(array $params): self
    {
        $this->params = $params;
        return $this;
    }
}
