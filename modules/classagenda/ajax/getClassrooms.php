<?php

/**
 * CLASSAGENDA MODULE.
 *
 * @package         classagenda module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Module\Classroom\ClassroomAPI;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = [];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR, AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
    AMA_TYPE_TUTOR => ['layout'],
    AMA_TYPE_STUDENT => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');

$retVal = translateFN('Nessuna classe trovata');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    if (ModuleLoaderHelper::isLoaded('MODULES_CLASSROOM') && isset($venueID) && intval($venueID) > 0) {
        $selTester = null;
        if (isset($_SESSION['sess_selected_tester'])) {
            $selTester = $_SESSION['sess_selected_tester'];
        } else {
            switch ($_SESSION['sess_userObj']->getType()) {
                case AMA_TYPE_STUDENT:
                    if (isset($courseID) && intval($courseID) > 0) {
                        $selTesterArr = AMACommonDataHandler::getInstance()->getTesterInfoFromIdCourse($courseID);
                        if (!AMADB::isError($selTesterArr) && is_array($selTesterArr) && isset($selTesterArr['puntatore'])) {
                            $selTester = $selTesterArr['puntatore'];
                        }
                    }
                    break;
                default:
                    $selTester = $_SESSION['sess_userObj']->getDefaultTester();
                    break;
            }
        }

        $classroomAPI = new ClassroomAPI($selTester);
        $result = $classroomAPI->getClassroomsForVenue(intval($venueID));

        if (!AMADB::isError($result)) {
            $firstEl = reset($result);
            if (!is_array($firstEl)) {
                $result = [$result];
            }
            $radios[0] = [
                'name' => translateFN('Nessuna'),
                'seats' => '',
            ];
            foreach ($result as $classroom) {
                $radios[$classroom['id_classroom']] = [
                    'name' => $classroom['name'],
                    'seats' => $classroom['seats'],
                ];
            }
            reset($radios);
            $htmlElement = CDOMElement::create('div');
            foreach ($radios as $id => $radio) {
                $radioEL = CDOMElement::create('radio', 'name:classroomradio,class:classroomradio,value:' . $id . ',id:classroom' . $id);
                $labelEL = CDOMElement::create('label', 'for:classroom' . $id);
                $labelEL->addChild(new CText($radio['name']));

                if (strlen($radio['seats']) > 0) {
                    $labelSPAN = CDOMElement::create('span');
                    $labelSPAN->addChild(new CText(' (' . $radio['seats'] . ' ' . translateFN('posti') . ')'));
                    $labelEL->addChild($labelSPAN);
                }

                $htmlElement->addChild($radioEL);
                $htmlElement->addChild($labelEL);
                $htmlElement->addChild(CDOMElement::create('div', 'class:clearfix'));
            }

            /**
             * add hidden div with id='facilities<classroomid>'
             * to display classroom facilities as a tooltip
             */
            reset($result);
            foreach ($result as $classroom) {
                // this will return a div CDOMElement or null
                $facilitiesHTML = $classroomAPI->getFacilitesHTML($classroom);
                if (!is_null($facilitiesHTML)) {
                    $facilitiesHTML->setAttribute('id', 'facilities' . $classroom['id_classroom']);
                    $facilitiesHTML->setAttribute('style', 'display:none;');
                    $htmlElement->addChild($facilitiesHTML);
                }
            }
            $retVal = $htmlElement->getHtml();
        }
    }
}
die($retVal);
