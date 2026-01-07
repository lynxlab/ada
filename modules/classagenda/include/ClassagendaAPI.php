<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package        classagenda module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2025, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           classagenda
 * @version        0.1
 */

namespace Lynxlab\ADA\Module\Classagenda;

use Lynxlab\ADA\Main\AMA\MultiPort;

/**
 * class for managing Classagenda
 *
 * @author giorgio
 */

class ClassagendaAPI
{
    /**
     * class datahandler
     *
     * @var \Lynxlab\ADA\Module\Classagenda\AMAClassagendaDataHandler
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
        $this->dh = AMAClassagendaDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        $this->dh->disconnect();
    }

    public function getClassRoomEventsForCourseInstance($courseInstanceId, $venueID = null, $start = 0, $end = 0)
    {
        return $this->dh->getClassRoomEventsForCourseInstance($courseInstanceId, $venueID, $start, $end);
    }
}
