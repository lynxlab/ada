<?php

/**
 * LOGIN MODULE
 *
 * @package     login module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2015-2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\Login\AbstractLogin;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
*/
require_once(realpath(dirname(__FILE__)) . '/../../config_path.inc.php');
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
$self = whoami();

foreach (AbstractLogin::getLoginProviders(null) as $id => $className) {
    if (intval($id) === intval($_GET['id'])) {
        $providerClassName = $className;
        $providerFQCN = AbstractLogin::getNamespaceName() . "\\" . $className;
        $loginObj = new $providerFQCN($id);
        break;
    }
}

if (isset($loginObj) && is_object($loginObj) && is_a($loginObj, AbstractLogin::getNamespaceName() . '\\AbstractLogin')) {
    $data = $loginObj->generateConfigPage()->getHtml();
    $title = translateFN('Configurazioni ' . ucfirst(strtolower($loginObj->loadProviderName())));
    $optionsAr['onload_func'] = 'initDoc(\'' . $providerClassName . '\');';
} else {
    $data = translateFN('Impossibile caricare i dati') . '. ' . translateFN('Login provider ID non riconosciuto') . '.';
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
        MODULES_LOGIN_PATH . '/js/jquery.jeditable.mini.js',
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
