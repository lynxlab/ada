<?php

/**
 * SERVICE-COMPLETE MODULE.
 *
 * @package        service-complete module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2013, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           service-complete
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Servicecomplete;

use Jawira\CaseConverter\Convert;

/**
 * management class for completeRules form
 *
 * @author giorgio
 */
class CompleteRulesManagement
{
    /**
     * the array of all the possible defined and
     * implemented conditions used to build the
     * operation as described in module's own
     * config.ini.php
     *
     * @var array
     */
    private $formConditionsList;

    /**
     * CompleteRulesManagement constructor.
     */
    public function __construct()
    {
        $this->formConditionsList = [];
    }

    /**
     * generates the form instance and returns the html
     *
     * @return the usual array with: html, path and status keys
     */
    public function form($data = null)
    {
        $dh = $GLOBALS['dh'];

        // populate the conditionList array
        foreach ($GLOBALS['completeClasses'] as $className) {
            if (
                class_exists(__NAMESPACE__ . "\\" . (new Convert($className))->toPascal()) ||
                class_exists(__NAMESPACE__ . "\\" . $className)
            ) {
                $this->formConditionsList[$className] = $className;
            }
        }
        $form = new FormCompleteRules($data, $this->formConditionsList);

        /**
         * path and status are not used for time being (03/dic/2013)
         */
        return [
                'html'   => $form->getHtml(),
                'path'   => '',
                'status' => '',
        ];
    }
}
