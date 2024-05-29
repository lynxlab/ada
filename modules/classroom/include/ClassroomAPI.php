<?php

/**
 * Classroom Management Class
 *
 * @package         classroom module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classroom;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\AMA\MultiPort;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class for managing Classroom
 *
 * @author giorgio
 */

class ClassroomAPI
{
    /**
     * classroom data handler
     *
     * @var Lynxlab\ADA\Module\Classroom\AMAClassroomDataHandler
     */
    private $dh;

    public function __construct($tester = null)
    {
        if (is_null($tester)) {
            $tester = $_SESSION['sess_selected_tester'];
        }
        if (isset($GLOBALS['dh'])) {
            $GLOBALS['dh']->disconnect();
        }
        $this->dh = AMAClassroomDataHandler::instance(MultiPort::getDSN($tester));
    }

    /**
     * gets all available venues
     */
    public function getAllVenues()
    {
        return $this->dh->classroomGetAllVenues();
    }

    /**
     * gets data about a venue
     *
     * @param number $id
     */
    public function getVenue($id)
    {
        return $this->dh->classroomGetVenue($id);
    }

    /**
     * gets all available venues having at least one classroom
     */
    public function getAllVenuesWithClassrooms()
    {
        return $this->dh->getJoined(
            [
                AMAClassroomDataHandler::$PREFIX . 'venues' => [
                    'fields' => ['id_venue', 'name'],
                    'join_field' => 'id_venue',
                ],
                AMAClassroomDataHandler::$PREFIX . 'classrooms' => [
                    'fields' => [],
                    'join_field' => 'id_venue',
                ],
            ],
            null,
            'name ASC'
        );
    }

    /**
     * gets list of classrooms associated to the passed venue
     *
     * @param number $id_venue
     */
    public function getClassroomsForVenue($id_venue)
    {
        return $this->dh->classroomGetClassroom(null, $id_venue);
    }

    /**
     * gets data about a classroom
     *
     * @param number $id_classroom
     */
    public function getClassroom($id_classroom)
    {
        return $this->dh->classroomGetClassroom($id_classroom);
    }

    /**
     * return the html with the classroom's facilities images
     *
     * @param array $classroomAr
     *
     * @return \Lynxlab\ADA\CORE\html4\CElement|NULL
     *
     * @access public
     */
    public function getFacilitesHTML($classroomAr)
    {
        $commonIconClass = '';

        if (intval($classroomAr['internet']) == 1) {
            $facilities[] = CDOMElement::create('img', 'src:' . MODULES_CLASSROOM_HTTP . '/layout/images/' .
                'globe.png,class:' . $commonIconClass . ',title:' . translateFN('Internet'));
        }
        if (intval($classroomAr['wifi']) == 1) {
            $facilities[] = CDOMElement::create('img', 'src:' . MODULES_CLASSROOM_HTTP . '/layout/images/' .
                'wifi.png,class:' . $commonIconClass . ',title:' . translateFN('Wi-Fi'));
        }
        if (intval($classroomAr['projector']) == 1) {
            $facilities[] = CDOMElement::create('img', 'src:' . MODULES_CLASSROOM_HTTP . '/layout/images/' .
                'projector.png,class:' . $commonIconClass . ',title:' . translateFN('Proiettore'));
        }
        if (intval($classroomAr['mobility_impaired']) == 1) {
            $facilities[] = CDOMElement::create('img', 'src:' . MODULES_CLASSROOM_HTTP . '/layout/images/' .
                'wheelchair.png,class:' . $commonIconClass . ',title:' . translateFN('Accesso disabili'));
        }

        if (isset($facilities) && count($facilities) > 0) {
            $divFacilities = CDOMElement::create('div', 'class:facilities');
            foreach ($facilities as $facility) {
                $divFacilities->addChild($facility);
            }
            return $divFacilities;
        } else {
            return null;
        }
    }

    public function __destruct()
    {
        $this->dh->disconnect();
    }
}
