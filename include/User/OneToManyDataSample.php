<?php

/**
 * class OneToManyDataSample for storing corresponding table data
 *
 * PLS NOTE: public properties MUST BE ONLY table column names
 *
 * If some other properties are needed, MUST add them as protected and/or private
 * and implement setters and getters
 *
 * @author giorgio
 *
 */

namespace Lynxlab\ADA\Main\User;

use Lynxlab\ADA\Main\User\UserExtraTables;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class OneToManyDataSample extends UserExtraTables
{
    public $sampleKeyProp;
    public $sampleForeignKeyProp;
    public $fieldOne;
    public $fieldTwo;
    public $fieldThree;

    /**
     * the name of the unique key in the table
     *
     * @var string
     */
    protected static $keyProperty = "sampleKeyProp";

    /**
     * the name of the foreign key (i.e. the key that points to the user id)
     *
     * @var string
     */
    protected static $foreignKeyProperty = "sampleForeignKeyProp";

    /**
     * array of labels to be used for each filed when rendering
     * to HTML in file /include/HtmlLibrary/UserExtraModuleHtmlLib.inc.php
     *
     * It's populated in the constructor because of the call to translateFN.
     *
     * NOTE: in this case the first two values are not displayed,
     * so labels are set to null value.
     *
     * @var array
     */
    protected $labels;

    public function __construct($dataAr = [])
    {
        $this->labels = [
            null,
            null,
            translateFN('Sample Label One'),
            translateFN('Sample Label Two'),
            translateFN('Sample Label Three'),
        ];
        parent::__construct($dataAr);
    }
}
