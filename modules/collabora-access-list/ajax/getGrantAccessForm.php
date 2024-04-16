<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\CollaboraACL\AMACollaboraACLDataHandler;
use Lynxlab\ADA\Module\CollaboraACL\CollaboraACLActions;
use Lynxlab\ADA\Module\CollaboraACL\CollaboraACLException;
use Lynxlab\ADA\Module\CollaboraACL\FileACL;
use Lynxlab\ADA\Module\CollaboraACL\GrantAccessForm;
use Lynxlab\ADA\Switcher\Subscription;

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
[$allowedUsersAr, $neededObjAr] = array_values(CollaboraACLActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);

/**
 * @var AMACollaboraACLDataHandler $GLOBALS['dh']
 */
$GLOBALS['dh'] = AMACollaboraACLDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

$retArray = ['status' => 'ERROR'];
session_write_close();

// sanitizie data
$passedData = [];
$needed = [
    [
        'key' => 'courseId',
        'sanitize' => function ($v) {
            return intval($v);
        },
    ],
    [
        'key' => 'instanceId',
        'sanitize' => function ($v) {
            return intval($v);
        },
    ],
    [
        'key' => 'fileAclId',
        'sanitize' => function ($v) {
            return intval($v);
        },
    ],
    [
        'key' => 'ownerId',
        'sanitize' => function ($v) {
            return intval($v);
        },
    ],
    [
        'key' => 'nodeId',
        'sanitize' => function ($v) {
            return (is_string($v) ? strval(trim($v)) : null);
        },
    ],
    [
        'key' => 'filename',
        'sanitize' => function ($v) {
            return (is_string($v) ? strval(trim($v)) : null);
        },
    ],
    [
        'key' => 'grantedUsers',
        'sanitize' => function ($v) {
            if (is_array($v) && count($v) > 0) {
                return array_map('intval', $v);
            } else {
                return [];
            }
        },
    ],
];

foreach ($needed as $n) {
    if (array_key_exists($n['key'], $_REQUEST)) {
        $passedData[$n['key']] = $n['sanitize']($_REQUEST[$n['key']]);
    } else {
        $passedData[$n['key']] = null;
    }
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    /**
     * it's a POST, save the passed data
     */
    $res = $GLOBALS['dh']->saveGrantedUsers($passedData);

    if (AMADB::isError($res) || $res instanceof CollaboraACLException) {
        // if it's an error display the error message
        $retArray['status'] = "ERROR";
        $retArray['msg'] = $res->getMessage();
    } else {
        $retArray['status'] = "OK";
        $retArray['msg'] = translateFN('Preferenze salvate');
        $retArray['fileAclId'] = $res['fileAclId'];
    }
} elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET') {
    // load data needed by the form
    $allUsers = Subscription::findSubscriptionsToClassRoom($passedData['instanceId']);
    $acl = $GLOBALS['dh']->findBy('FileACL', ['id' => $passedData['fileAclId']]);
    if (is_array($acl) && count($acl) == 1) {
        $acl = reset($acl);
    } else {
        // make a new, empty access list
        $acl = new FileACL();
    }
    // add granted key to the subscription array
    $allUsers = array_map(
        function ($subscription) use ($acl) {
            $retval = [
                'id' => $subscription->getSubscriberId(),
                'nome' => $subscription->getSubscriberFirstname(),
                'cognome' => $subscription->getSubscriberLastname(),
                'granted' => false,
            ];
            foreach ($acl->getAllowedUsers() as $allowed) {
                if ($allowed['utente_id'] == $subscription->getSubscriberId()) {
                    $retval['granted'] = true;
                    break;
                }
            }
            return $retval;
        },
        $allUsers
    );
    // sort by lastname asc
    usort($allUsers, function ($a, $b) {
        return strcasecmp($a['cognome'], $b['cognome']);
    });
    // display the form with loaded data
    $formData = [
        'fileAclId' => $passedData['fileAclId'] > 0 ? $passedData['fileAclId'] : 0,
        'allUsers' => $allUsers,
        'isTutor' => $userObj->getType() == AMA_TYPE_TUTOR,
    ];
    $form = new GrantAccessForm('grantaccess', null, $formData);
    $retArray['status'] = "OK";
    $retArray['html'] = $form->withSubmit()->toSemanticUI()->getHtml();
    $retArray['dialogTitle'] = translateFN('Preferenze condivisione file');
}

header('Content-Type: application/json');
echo json_encode($retArray);
