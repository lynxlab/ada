<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Login\AbstractLogin;
use Lynxlab\ADA\Module\Login\AMALoginDataHandler;

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

$loginProviders = AbstractLogin::getLoginProviders(null, true);

if (!is_null($loginProviders) && is_array($loginProviders)) {

    /**
     * generate HTML for 'New Provider' button and the table
     */
    $configIndexDIV = CDOMElement::create('div', 'id:configindex');
    $newButton = CDOMElement::create('button');
    $newButton->setAttribute('class', 'newButton top tooltip');
    $newButton->setAttribute('title', translateFN('Clicca per creare un nuovo login provider'));
    $newButton->setAttribute('onclick', 'javascript:editProvider(null);');
    $newButton->addChild(new CText(translateFN('Nuovo Provider')));
    $configIndexDIV->addChild($newButton);
    $configIndexDIV->addChild(CDOMElement::create('div', 'class:clearfix'));
    $tableOutData = [];

    if (!AMADB::isError($loginProviders)) {
        $labels =  [translateFN('id'), translateFN('ordine'), translateFN('className'),  translateFN('Nome'),
                translateFN('Abilitato'), translateFN('Bottone'),
                translateFN('azioni')];
        $hasDefault = false;
        foreach ($loginProviders as $i => $elementArr) {
            $links = [];
            $linksHtml = "";
            $skip = $elementArr['className'] == AMALoginDataHandler::$MODULES_LOGIN_DEFAULT_LOGINPROVIDER && !$hasDefault;
            for ($j = 0; $j < 6; $j++) {
                switch ($j) {
                    case 0:
                        if (!$skip) {
                            $type = 'edit';
                            $title = translateFN('Modifica');
                            $link = 'editProvider(' . $i . ');';
                        }
                        break;
                    case 1:
                        $type = 'config';
                        $title = translateFN('Configura');
                        $link = 'document.location.href=\'' . MODULES_LOGIN_HTTP . '/config.php?id=' . $i . '\'';
                        break;
                    case 2:
                        if (!$skip) {
                            $type = 'delete';
                            $title = translateFN('Cancella');
                            $link = 'deleteProvider($j(this), ' . $i . ' , \'' . urlencode(translateFN("Questo cancellerà l'elemento selezionato")) . '\');';
                        }
                        break;
                    case 3:
                        if (!$skip || count($loginProviders) > 1) {
                            $isEnabled = intval($elementArr['enabled']) === 1;
                            $type = ($isEnabled) ? 'disable' : 'enable';
                            $title = ($isEnabled) ? translateFN('Disabilita') : translateFN('Abilita');
                            $link = 'setEnabledProvider($j(this), ' . $i . ', ' . ($isEnabled ? 'false' : 'true') . ');';
                        }
                        break;
                    case 4:
                        $type = 'up';
                        $title = translateFN('Sposta su');
                        $link = 'moveProvider($j(this),' . $i . ',-1);';
                        break;
                    case 5:
                        $type = 'down';
                        $title = translateFN('Sposta giu');
                        $link = 'moveProvider($j(this),' . $i . ',1);';
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
            if ($skip) {
                $hasDefault = true;
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

            $tableOutData[$i] =  [
                    $labels[0] => $i,
                    $labels[1] => $elementArr['displayOrder'],
                    $labels[2] => $elementArr['className'],
                    $labels[3] => $elementArr['name'],
                    $labels[4] => ((intval($elementArr['enabled']) === 1) ? translateFN('Abilitato') : translateFN('Disabilitato')),
                    $labels[5] => $elementArr['buttonLabel'],
                    $labels[6] => $linksHtml];
        }

        $OutTable = BaseHtmlLib::tableElement(
            'id:loginProvidersList',
            $labels,
            $tableOutData,
            '',
            translateFN('Elenco dei login provider')
        );
        $OutTable->setAttribute('class', ADA_SEMANTICUI_TABLECLASS);
        $configIndexDIV->addChild($OutTable);

        // if there are more than 10 rows, repeat the add new button below the table
        if (count($loginProviders) > 10) {
            $bottomButton = clone $newButton;
            $bottomButton->setAttribute('class', 'newButton bottom tooltip');
            $configIndexDIV->addChild($bottomButton);
        }
    } // if (!AMADB::isError($optionSetList))
    $data = $configIndexDIV->getHtml();
    $title = translateFN('Configurazione Login Provider');
    $optionsAr['onload_func'] = 'initDoc();';
} else {
    $data = translateFN('Impossibile caricare i dati') . '. ' . translateFN('nessun login provider trovato') . '.';
    $title = translateFN('Erorre login provider');
    $optionsAr = null;
}

$content_dataAr = [
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'status' => $status,
        'title' => $title,
        'data' => $data,
];
$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_DATATABLE,
        SEMANTICUI_DATATABLE,
        JQUERY_DATATABLE_REDRAW,
        JQUERY_DATATABLE_DATE,
        JQUERY_UI,
        JQUERY_NO_CONFLICT,
];
$layout_dataAr['CSS_filename'] = [
        JQUERY_UI_CSS,
        SEMANTICUI_DATATABLE_CSS,
        MODULES_LOGIN_PATH . '/layout/tooltips.css',
];
ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);
