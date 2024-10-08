<?php

use Lynxlab\ADA\CORE\html4\CBase;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprException;
use Lynxlab\ADA\Module\GDPR\GdprRequest;
use Lynxlab\ADA\Module\GDPR\GdprRequestType;
use Ramsey\Uuid\Uuid;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once(realpath(__DIR__) . '/../../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Get Users (types) allowed to access this module and needed objects
 */
[$allowedUsersAr, $neededObjAr] = array_values(GdprActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$showAll = null;
$uuid = null;
$data = [];
if (array_key_exists('showall', $_REQUEST)) {
    $showAll = filter_var($_REQUEST['showall'], FILTER_SANITIZE_NUMBER_INT, FILTER_REQUIRE_SCALAR);
}
if (array_key_exists('uuid', $_REQUEST)) {
    $uuid = filter_var($_REQUEST['uuid'], FILTER_SANITIZE_STRING, FILTER_REQUIRE_SCALAR);
}

try {
    if (intval($_SESSION['sess_userObj']->getType()) === AMA_TYPE_VISITOR && strlen($uuid) <= 0) {
        throw new GdprException(translateFN("L'utente non registrato può solo vedere il suo numero di pratica"));
    } elseif ($showAll === true && intval($_SESSION['sess_userObj']->getType()) !== AMA_TYPE_SWITCHER) {
        throw new GdprException(translateFN("Solo il coordinatore può vedere tutte le richieste"));
    }

    if (strlen($uuid ?? '') > 0 && !Uuid::isValid($uuid)) {
        throw new GdprException(translateFN("Numero di pratica non valido"));
    }

    $orderby = ['generatedTs' => 'DESC'];
    // list user's requests by default
    $where   = ['generatedBy' => $_SESSION['sess_userObj']->getId()];

    if ($showAll) {
        // only the swithcer can access all requests
        $where = [];
    }

    if (strlen($uuid ?? '') > 0) {
        if (intval($_SESSION['sess_userObj']->getType()) === AMA_TYPE_SWITCHER) {
            // the swithcer can view an uuid regardless of the generatedBy value
            $where = [];
        }
        // show only request matching the passed uuid
        $where +=  ['uuid' => $uuid];
    }

    $gdprAPI = new GdprAPI();
    $requests = $gdprAPI->findBy($gdprAPI->getObjectClasses()[AMAGdprDataHandler::REQUESTCLASSKEY], $where, $orderby);
    if (count($requests) > 0) {
        if ($showAll) {
            $requests = array_filter($requests, fn (GdprRequest $el) => !is_null($el->getConfirmedTs()));
        }

        $data['data'] = array_map(
            /** @var GdprRequest $el */
            function (GdprRequest $el) use ($showAll) {
                $retArr = [];
                $retArr['uuid'] = $el->getUuid();
                if ($showAll) {
                    $retArr['generatedBy'] = $el->getGeneratedBy();
                }
                $retArr['generatedDate'] = Utilities::ts2dFN($el->getGeneratedTs()) . ' ' . Utilities::ts2tmFN($el->getGeneratedTs());
                $retArr['closedDate'] = is_null($el->getClosedTs()) ? null : Utilities::ts2dFN($el->getClosedTs()) . ' ' . Utilities::ts2tmFN($el->getClosedTs());
                $retArr['type'] = $el->getType()->toArray();
                $actions = [];

                if ($showAll && is_null($el->getClosedTs()) && $el->getType() instanceof GdprRequestType) {
                    // actions are only available when showAll is true and request is not closed
                    if (GdprActions::canDo($el->getType()->getLinkedAction(), $el)) {
                        $actions[] = $el->getActionButton();
                    }
                    if (GdprActions::canDo(GdprActions::FORCE_CLOSE_REQUEST, $el)) {
                        $actions[] = $el->getActionButton(true);
                    }
                }

                if ($showAll || count($actions) > 0) {
                    if ($showAll) {
                        $retArr['content'] = $el->getContent();
                        if (method_exists($el, 'getMoreCols')) {
                            foreach ($el::getMoreCols() as $dataArr) {
                                $field = $dataArr['field']['data'];
                                if (array_key_exists('value', $dataArr)) {
                                    if (is_callable($dataArr['value'])) {
                                        $retArr[$field] = $dataArr['value']($el);
                                    } else {
                                        $retArr[$field] = $dataArr['value'];
                                    }
                                } else {
                                    $retArr[$field] = null;
                                }
                            }
                        }
                    }
                    $retArr['actions'] = array_reduce($actions, function ($carry, $item) {
                        if (empty($carry)) {
                            $carry = '';
                        }
                        $carry .= ($item instanceof CBase ? $item->getHtml() : '');
                        return $carry;
                    });
                }

                return $retArr;
            },
            array_values($requests)
        );
    } else {
        $data['data'] = [];
    }
} catch (Exception $e) {
    //  header(' ', true, 400);
    $data['data'] = [];
    $data['data']['error'] = $e->getMessage();
}

header('Content-Type: application/json');
die(json_encode($data, JSON_NUMERIC_CHECK));
