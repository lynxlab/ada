<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\Newsletter\AMANewsletterDataHandler;
use Lynxlab\ADA\Module\Newsletter\FormEditNewsletter;

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

$self = Utilities::whoami();

$GLOBALS['dh'] = AMANewsletterDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$containerDIV = CDOMElement::create('div', 'id:moduleContent');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'  && !empty($_POST)) {
    // saves the newsletter
    $newsletterHa['date'] = (isset($_POST['subject']) && trim($_POST['subject']) !== '') ? $dh->dateToTs(trim($_POST['date'])) : $dh->dateToTs(date("d/m/Y"));
    $newsletterHa['subject'] = (isset($_POST['subject']) && trim($_POST['subject']) !== '') ? trim($_POST['subject']) : null;
    $newsletterHa['sender'] = (isset($_POST['sender']) && trim($_POST['sender']) !== '') ? trim($_POST['sender']) : null;
    $newsletterHa['htmltext'] = (isset($_POST['htmltext']) && trim($_POST['htmltext']) !== '') ? trim($_POST['htmltext']) : null;
    $newsletterHa['plaintext'] = (isset($_POST['plaintext']) && trim($_POST['plaintext']) !== '') ? trim($_POST['plaintext']) : null;
    $newsletterHa['draft'] = intval($_POST['draft']);
    $newsletterHa['id'] = (isset($_POST['id']) && intval($_POST['id']) > 0) ? intval($_POST['id']) : 0;

    $retval = $dh->saveNewsletter($newsletterHa);

    if (AMADB::isError($retval)) {
        $msg = new CText(translateFN('Errore nel salvataggio della newsletter'));
    } else {
        $msg = new CText(translateFN('Newsletter salvata'));
    }

    $containedElement = CDOMElement::create('div', 'class:newsletterSaveResults');

    $spanmsg = CDOMElement::create('span', 'class:newsletterSaveResultstext');
    $spanmsg->addChild($msg);

    $button = CDOMElement::create('button', 'id:newsletterSaveResultsbutton');
    $button->addChild(new CText(translateFN('OK')));
    $button->setAttribute('onclick', 'javascript:self.document.location.href=\'' . MODULES_NEWSLETTER_HTTP . '\'');

    $containedElement->addChild($spanmsg);
    $containedElement->addChild($button);

    $data = $containedElement->getHtml();

    /// if it's an ajax request, output html and die
    if (isset($_POST['requestType']) && trim($_POST['requestType']) === 'ajax') {
        echo $data;
        die();
    }
} else {
    $containedElement = new FormEditNewsletter('editnewsletter');

    $newsletterId = (isset($_GET['id']) && intval($_GET['id']) > 0) ? intval($_GET['id']) : 0;

    if ($newsletterId > 0) {
        $loadedNewsletter = $dh->getNewsletter($newsletterId);
        if (!AMADB::isError($loadedNewsletter)) {
            $containedElement->fillWithArrayData($loadedNewsletter);
        }
    }
    $data = $containedElement->render();
}

$containerDIV->addChild(new CText($data));
$data = $containerDIV->getHtml();

/**
 * include proper jquery ui css file depending on wheter there's one
 * in the template_family css path or the default one
 */
if (!is_dir(MODULES_NEWSLETTER_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui')) {
    $layout_dataAr['CSS_filename'] = [
            JQUERY_UI_CSS,
    ];
} else {
    $layout_dataAr['CSS_filename'] = [
            MODULES_NEWSLETTER_PATH . '/layout/' . $userObj->template_family . '/css/jquery-ui/jquery-ui-1.10.3.custom.min.css',
    ];
}


$content_dataAr = [
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'status' => $status,
        'title' => translateFN('Newsletter'),
        'data' => $data,
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JQUERY_MASKEDINPUT,
        JQUERY_NO_CONFLICT,
        MODULES_NEWSLETTER_PATH . '/js/html2text.js',
];

$optionsAr = [];
$optionsAr['onload_func'] = 'initDoc();';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);
