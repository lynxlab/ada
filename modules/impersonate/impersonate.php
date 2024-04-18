<?php

/**
 * @package     impersonate module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Module\Impersonate\AMAImpersonateDataHandler;
use Lynxlab\ADA\Module\Impersonate\ImpersonateActions;
use Lynxlab\ADA\Module\Impersonate\ImpersonateException;
use Lynxlab\ADA\Module\Impersonate\LinkedUsers;
use Lynxlab\ADA\Module\Impersonate\Utils;

use function Lynxlab\ADA\Main\AMA\DBRead\readUser;

/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

// MODULE's OWN IMPORTS

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(ImpersonateActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

$impersonateId = -1;

if (isset($_SESSION[Utils::MODULES_IMPERSONATE_SESSBACKDATA])) {
    $impersonateObj = $_SESSION[Utils::MODULES_IMPERSONATE_SESSBACKDATA];
    unset($_SESSION[Utils::MODULES_IMPERSONATE_SESSBACKDATA]);
} else {
    /**
     * @var AMAImpersonateDataHandler $impDH
     */
    $impDH = AMAImpersonateDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));
    try {
        $impObj = LinkedUsers::getSessionLinkedUser();
        if (count($impObj) > 0) {
            if (isset($_GET['t']) && intval($_GET['t']) > 0) {
                $t = intval($_GET['t']);
                $impObj = array_filter($impObj, fn ($el) => $el->getLinkedType() == $t);
            }
            $impObj = reset($impObj);
            $impersonateId = $impObj->getLinkedId();
            $_SESSION[Utils::MODULES_IMPERSONATE_SESSBACKDATA] = $_SESSION['sess_userObj'];
        } else {
            throw new ImpersonateException('Error loading LinkedUsers object');
        }
    } catch (ImpersonateException) {
        $impersonateId = -1;
        if (isset($_SESSION[Utils::MODULES_IMPERSONATE_SESSBACKDATA])) {
            unset($_SESSION[Utils::MODULES_IMPERSONATE_SESSBACKDATA]);
        }
    }
}

if (!isset($impersonateObj)) {
    $impersonateObj = $impersonateId > 0 ? readUser($impersonateId) : $userObj;
}

if ($impersonateObj instanceof ADALoggableUser) {
    if (isset($_SESSION[Utils::MODULES_IMPERSONATE_SESSBACKDATA])) {
        $impersonateObj->setStatus(ADA_STATUS_REGISTERED);
    }
    ADAUser::setSessionAndRedirect(
        $impersonateObj,
        false,
        $impersonateObj->getLanguage(),
        null,
        isset($_SERVER['HTTP_REFERER']) && strlen($_SERVER['HTTP_REFERER']) > 0 ? $_SERVER['HTTP_REFERER'] : $impersonateObj->getHomePage()
    );
}
