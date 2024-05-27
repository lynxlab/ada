<?php

/**
 * Venues Management Class
 *
 * @package         classroom module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

namespace Lynxlab\ADA\Module\Classroom;

use Lynxlab\ADA\Module\Classroom\FormVenues;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * class for managing Venues
 *
 * @author giorgio
 */

class VenuesManagement extends AbstractClassRoomManagement
{
    public $id_venue;
    public $name;
    public $addressline1;
    public $addressline2;
    public $contact_name;
    public $contact_phone;
    public $contact_email;
    public $map_url;

    /**
     * build, manage and display the module's pages
     *
     * @return array
     *
     * @access public
     */
    public function run($action = null)
    {
        /* @var $html   string holds html code to be retuned */
        $htmlObj = null;
        /* @var $path   string  path var to render in the help message */
        $help = translateFN('Da qui puoi inserire o modifcare un logo dove ci sono le aule in cui si terranno i corsi');
        /* @var $status string status var to render in the breadcrumbs */
        $title = translateFN('Luoghi');

        switch ($action) {
            case MODULES_CLASSROOM_EDIT_VENUE:
                /**
                 * edit action, display the form with passed data
                 */
                $htmlObj = new FormVenues($this->toArray());
                // no break
            default:
                /**
                 * return an empty page as default action
                 */
                break;
        }

        return [
            'htmlObj'   => $htmlObj,
            'help'      => $help,
            'title'     => $title,
        ];
    }
}
