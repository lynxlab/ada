<?php

/**
 * Classbudget Management Class
 *
 * @package         classbudget module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2015, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classbudget
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classbudget;

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;

/**
 * class for managing Classbudget
 *
 * @author giorgio
 */

class ClassbudgetAPI
{
    /**
     * class datahandler
     *
     * @var \Lynxlab\ADA\Module\Classbudget\AMAClassbudgetDataHandler
     */
    private $dh;

    /**
     * constructor
     */
    public function __construct()
    {
        if (isset($GLOBALS['dh'])) {
            $GLOBALS['dh']->disconnect();
        }
        $this->dh = AMAClassbudgetDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        $this->dh->disconnect();
    }

    /**
     * Saves a budget object for the course instance
     *
     * @param BudgetCourseInstanceManagement $object data to be saved
     *
     * @return int inserted or updated row id
     *
     * @access public
     */
    public function saveBudgetCourseInstance(BudgetCourseInstanceManagement $object)
    {
        return (int) $this->dh->saveBudgetCourseInstance($object->toArray());
    }

    /**
     * Gets a budget object for a course instance
     *
     * @param int $course_instance_id the instance id to load object for
     *
     * @return BudgetCourseInstanceManagement|AMA_Error
     *
     * @access public
     */
    public function getBudgetCourseInstance($course_instance_id)
    {
        $dataAr = $this->dh->getBudgetCourseInstanceByInstanceID($course_instance_id);
        if (!AMADB::isError($dataAr)) {
            return new BudgetCourseInstanceManagement($dataAr);
        } else {
            return $dataAr;
        }
    }

    /**
     * Deletes a budget row for a course instance
     *
     * @param int $course_instance_id the instance id to delete row for
     *
     * @return AMAError|int of affected rows
     *
     * @access public
     */
    public function deleteBudgetCourseInstance($course_instance_id)
    {
        return $this->dh->deleteBudgetCourseInstanceByInstanceID($course_instance_id);
    }
}
