<?php

use Lynxlab\ADA\Module\EtherpadIntegration\Groups;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Bookmark\Tag;

use Lynxlab\ADA\Main\AMA\AMADB;

use function \translateFN;

/**
 * @package     studentsgroups module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2020, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\StudentsGroups\AMAStudentsGroupsDataHandler;
use Lynxlab\ADA\Module\StudentsGroups\Groups;
use Lynxlab\ADA\Module\StudentsGroups\StudentsGroupsActions;
use Svg\Tag\Group;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(StudentsGroupsActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMAStudentsGroupsDataHandler $GLOBALS['dh']
 */
if (array_key_exists('sess_selected_tester', $_SESSION)) {
    $GLOBALS['dh'] = AMAStudentsGroupsDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
}

$data = ['error' => translateFN('errore sconosciuto')];

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    $params = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);

    if (class_exists(AMAStudentsGroupsDataHandler::MODELNAMESPACE . $params['object'])) {
        if ($params['object'] == 'Groups') {
            $groupsData = [];
            if (isset($_REQUEST['id']) && intval(trim($_REQUEST['id'])) > 0) {
                $withMembers = true;
                $withActions = false;
                $withGroupDetails = false;
                $groupsList = $GLOBALS['dh']->findBy($params['object'], [ 'id' => intval(trim($_REQUEST['id'])) ]);
            } else {
                $withMembers = false;
                $withActions = true;
                $withGroupDetails = true;
                $groupsList = $GLOBALS['dh']->findAll($params['object']);
            }
            if (!AMADB::isError($groupsList)) {
                /**
                 * @var \Lynxlab\ADA\Module\StudentsGroups\Groups $group
                 */
                foreach ($groupsList as $group) {
                    if ($withActions) {
                        $links = [];
                        $linksHtml = "";

                        for ($j = 0; $j < 2; $j++) {
                            switch ($j) {
                                case 0:
                                    if (StudentsGroupsActions::canDo(StudentsGroupsActions::EDIT_GROUP)) {
                                        $type = 'edit';
                                        $title = translateFN('Modifica gruppo');
                                        $link = 'editGroup(\'' . $group->getId() . '\');';
                                    }
                                    break;
                                case 1:
                                    if (StudentsGroupsActions::canDo(StudentsGroupsActions::TRASH_GROUP)) {
                                        $type = 'delete';
                                        $title = translateFN('Cancella gruppo');
                                        $link = 'deleteGroup($j(this), \'' . $group->getId() . '\');';
                                    }
                                    break;
                            }

                            if (isset($type)) {
                                $links[$j] = CDOMElement::create('li', 'class:liactions');

                                $linkshref = CDOMElement::create('button');
                                $linkshref->setAttribute('onclick', 'javascript:' . $link);
                                $linkshref->setAttribute('class', $type . 'Button tooltip');
                                $linkshref->setAttribute('title', $title);
                                $links[$j]->addChild($linkshref);
                                // unset for next iteration
                                unset($type);
                            }
                        }

                        if (!empty($links)) {
                            $linksul = CDOMElement::create('ul', 'class:ulactions');
                            foreach ($links as $link) {
                                $linksul->addChild($link);
                            }
                            $linksHtml = $linksul->getHtml();
                        } else {
                            $linksHtml = '';
                        }
                    }

                    $tmpData['label'] = $group->getLabel();
                    foreach ($group->getCustomFields() as $key => $val) {
                        if (array_key_exists($key, Groups::getCustomFieldsVal()) && array_key_exists($val, Groups::getCustomFieldsVal()[$key])) {
                            $tmpData[Groups::CUSTOMFIELDPRFIX . $key] = Groups::getCustomFieldsVal()[$key][$val];
                        } else {
                            $tmpData[Groups::CUSTOMFIELDPRFIX . $key] = null;
                        }
                    }
                    if ($withGroupDetails) {
                        $imgDetails = CDOMElement::create('img', 'src:' . HTTP_ROOT_DIR . '/layout/' . $_SESSION['sess_template_family'] . '/img/details_open.png');
                        $imgDetails->setAttribute('title', translateFN('visualizza/nasconde i dettagli del gruppo'));
                        $imgDetails->setAttribute('onclick', 'toggleGroupDetails(' . $group->getId() . ',this);');
                        $imgDetails->setAttribute('style', 'cursor:pointer;');
                        $tmpData['detailsBtn'] = $imgDetails->getHtml();
                    }
                    if ($withActions) {
                        $tmpData['actions'] = $linksHtml;
                    }
                    if ($withMembers) {
                        $tmpData['members'] = $group->getMembers();
                    }
                    array_push($groupsData, $tmpData);
                }
            } // if (!AMADB::isError($groupsList))
            $data = [ 'data' => $groupsData ];
        }
    } else {
        $data = [ 'error' => translateFN('Oggetto non valido')];
    }
}

if (array_key_exists('error', $data)) {
    header(' ', true, 404);
    $data['data'] = [];
}
header('Content-Type: application/json');
die(json_encode($data));
