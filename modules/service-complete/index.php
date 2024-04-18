<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\CORE\HtmlElements\Table;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;

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
$variableToClearAR = [];
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

$self = 'complete';

$GLOBALS['dh'] = AMACompleteDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

/**
 * generate HTML for 'New Rule' button and the table
 */

$rulesIndexDIV = CDOMElement::create('div', 'id:rulesindex');

$newButton = CDOMElement::create('button');
$newButton->setAttribute('class', 'newButton top');
$newButton->setAttribute('title', translateFN('Clicca per creare una nuova regola'));
$newButton->setAttribute('onclick', 'javascript:self.document.location.href=\'' . MODULES_SERVICECOMPLETE_HTTP . '/edit_completerule.php\'');
$newButton->addChild(new CText(translateFN('Nuova Regola')));

$rulesData = [];

$rulesList = $dh->getCompleteConditionSetList();

if (!AMADB::isError($rulesList)) {
    $labels =  [translateFN('descrizione'), translateFN('azioni')];

    foreach ($rulesList as $i => $ruleAr) {
        $links = [];
        $linksHtml = "";

        for ($j = 0; $j < 3; $j++) {
            switch ($j) {
                case 0:
                    $type = 'edit';
                    $title = translateFN('Clicca per modificare la regola');
                    $link = 'self.document.location.href=\'edit_completerule.php?conditionSetId=' . $ruleAr['id'] . '\';';
                    break;
                case 1:
                    $type = 'apply';
                    $title = translateFN('Clicca per collegare la regola ai corsi');
                    $link = 'self.document.location.href=\'completerule_link_courses.php?conditionSetId=' . $ruleAr['id'] . '\';';
                    break;
                case 2:
                    $type = 'delete';
                    $title = translateFN('Clicca per cancellare la regola');
                    $link = 'deleteRule ($j(this), ' . $ruleAr['id'] . ' , \'' . urlencode(translateFN("Questo cancellerà l'elemento selezionato")) . '\');';
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

        $rulesData[$i] =  [
                $labels[0] => $ruleAr['descrizione'],
                $labels[1] => $linksHtml];
    }

    $historyTable = new Table();
    $historyTable->initTable('0', 'center', '1', '1', '90%', '', '', '', '', '1', '0', '', 'default', 'completeRulesList');
    $historyTable->setTable($rulesData, translateFN('Elenco delle regole di completamento'), translateFN('Elenco delle regole di completamento'));
    $histData = $historyTable->getTable();
    $histData = preg_replace('/class="/', 'class="' . ADA_SEMANTICUI_TABLECLASS . ' ', $histData, 1); // replace first occurence of class

    $rulesIndexDIV->addChild($newButton);
    $rulesIndexDIV->addChild(CDOMElement::create('div', 'class:clearfix'));
    $rulesIndexDIV->addChild(new CText($histData));
    // if there are more than 10 rows, repeat the add new button below the table
    if (isset($i) && $i > 10) {
        $bottomButton = clone $newButton;
        $bottomButton->setAttribute('class', 'newButton bottom');
        $rulesIndexDIV->addChild($bottomButton);
    }
    // if (!AMADB::isError($rulesList))
} else {
    $rulesIndexDIV->addChild(new CText(translateFN('Errore nella lettura dell\'elenco delle regole')));
}

$data = $rulesIndexDIV->getHtml();

/**
 * include proper jquery ui css file depending on wheter there's one
 * in the template_family css path or the default one
*/
if (!is_dir(MODULES_SERVICECOMPLETE_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui')) {
    $layout_dataAr['CSS_filename'] = [
            JQUERY_UI_CSS,
    ];
} else {
    $layout_dataAr['CSS_filename'] = [
        MODULES_SERVICECOMPLETE_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui/jquery-ui-1.10.3.custom.min.css',
    ];
}

array_push($layout_dataAr['CSS_filename'], SEMANTICUI_DATATABLE_CSS);
array_push($layout_dataAr['CSS_filename'], MODULES_SERVICECOMPLETE_PATH . '/layout/tooltips.css');

$content_dataAr = [
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'status' => $status,
        'label' => translateFN('Regole di completamento'),
        'data' => $data,
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_DATATABLE,
        SEMANTICUI_DATATABLE,
        JQUERY_DATATABLE_DATE,
        JQUERY_UI,
        JQUERY_NO_CONFLICT,
];

$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);
