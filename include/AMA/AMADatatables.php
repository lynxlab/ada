<?php

namespace Lynxlab\ADA\Main\AMA;

use Ozdemir\Datatables\Datatables;
use Ozdemir\Datatables\DB\DatabaseInterface;
use Ozdemir\Datatables\Http\Request;
use Ozdemir\Datatables\Option;

class AMADatatables extends Datatables
{
    /**
     * Datatables constructor.
     *
     * @param DatabaseInterface $db
     * @param Request $request
     */
    public function __construct(DatabaseInterface $db, ?Request $request = null)
    {
        $this->db = $db;
        $this->options = new Option($request ?: Request::createFromGlobals());
    }
}
