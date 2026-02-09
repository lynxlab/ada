<?php

namespace Lynxlab\ADA\Main\AMA;

use Ozdemir\Datatables\DB\MySQL;

class AMADatatablesPDO extends MySQL
{
    public static function create(AMAPDO $pdo)
    {
        $retObj = new self([]);
        $retObj->pdo = $pdo;
        return $retObj;
    }
}
