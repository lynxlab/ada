<?php

/**
 * CLASSROOM MODULE.
 *
 * @package         classroom module
 * @author          Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2014, Lynx s.r.l.
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            classroom
 * @version         0.1
 */

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Classroom\AMAClassroomDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout'],
];

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

// MODULE's OWN IMPORTS

$self = Utilities::whoami();

$GLOBALS['dh'] = AMAClassroomDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

/**
 * generate HTML for 'New Venue' button and the table
 */

$venuesIndexDIV = CDOMElement::create('div', 'id:venuesindex');

$newButton = CDOMElement::create('button');
$newButton->setAttribute('class', 'newButton top');
$newButton->setAttribute('title', translateFN('Clicca per creare un nuovo luogo'));
$newButton->setAttribute('onclick', 'javascript:editVenue(null);');
$newButton->addChild(new CText(translateFN('Nuovo Luogo')));
$venuesIndexDIV->addChild($newButton);
$venuesIndexDIV->addChild(CDOMElement::create('div', 'class:clearfix'));

$venuesData = [];
$venuesList = $GLOBALS['dh']->classroomGetAllVenues();

if (!AMADB::isError($venuesList)) {
    $labels = [
        translateFN('nome'), translateFN('Nominativo di contatto'),
        translateFN('Telefono del contatto'), translateFN('E-Mail del contatto'),
        translateFN('azioni'),
    ];

    foreach ($venuesList as $i => $venueAr) {
        $links = [];
        $linksHtml = "";

        for ($j = 0; $j < 2; $j++) {
            switch ($j) {
                case 0:
                    $type = 'edit';
                    $title = translateFN('Modifica luogo');
                    $link = 'editVenue(' . $venueAr['id_venue'] . ');';
                    break;
                case 1:
                    $type = 'delete';
                    $title = translateFN('Cancella luogo');
                    $link = 'deleteVenue($j(this), ' . $venueAr['id_venue'] . ' , \'' . urlencode(translateFN("Questo cancellerà l'elemento selezionato")) . '\');';
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

        if (DataValidator::validateEmail($venueAr['contact_email'])) {
            $emailHref = CDOMElement::create('a');
            $emailHref->setAttribute('href', 'mailto:' . $venueAr['contact_email']);
            $emailHref->addChild(new CText($venueAr['contact_email']));
            $emailOut = $emailHref->getHtml();
        } else {
            $emailOut = $venueAr['contact_email'];
        }

        $venuesData[$i] = [
            $labels[0] => $venueAr['name'],
            $labels[1] => $venueAr['contact_name'],
            $labels[2] => $venueAr['contact_phone'],
            $labels[3] => $emailOut,
            $labels[4] => $linksHtml,
        ];
    }

    $venuesTable = BaseHtmlLib::tableElement('id:completeVenuesList', $labels, $venuesData, '', translateFN('Elenco dei luoghi'));
    $venuesTable->setAttribute('class', $venuesTable->getAttribute('class') . ' ' . ADA_SEMANTICUI_TABLECLASS);
    $venuesIndexDIV->addChild($venuesTable);

    // if there are more than 10 rows, repeat the add new button below the table
    if ($i > 10) {
        $bottomButton = clone $newButton;
        $bottomButton->setAttribute('class', 'newButton bottom');
        $venuesIndexDIV->addChild($bottomButton);
    }
} // if (!AMA_DB::isError($venuesList))


$data = $venuesIndexDIV->getHtml();

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'status' => $status,
    'title' => translateFN('classroom'),
    'data' => $data,
];

$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    JQUERY_UI,
];

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
    MODULES_CLASSROOM_PATH . '/layout/tooltips.css',
];

$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);
