<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

/**
 * @package     gdpr module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

use Lynxlab\ADA\CORE\html4\CBase;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Module\GDPR\AMAGdprDataHandler;
use Lynxlab\ADA\Module\GDPR\GdprAPI;
use Lynxlab\ADA\Module\GDPR\GdprActions;
use Lynxlab\ADA\Module\GDPR\GdprException;
use Lynxlab\ADA\Module\GDPR\GdprPolicy;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;
use function Lynxlab\ADA\Main\Utilities\ts2tmFN;

/**
 * Base config file
 */
require_once(realpath(dirname(__FILE__)) . '/../../../config_path.inc.php');

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

$data = [];

try {
    if (intval($_SESSION['sess_userObj']->getType()) !== AMA_TYPE_SWITCHER) {
        throw new GdprException(translateFN("Solo il coordinatore può vedere tutte le le politiche di privacy"));
    }

    $orderby = ['lastEditTS' => 'DESC'];
    $policies = (new GdprAPI())->findBy('GdprPolicy', [], $orderby, AMAGdprDataHandler::getPoliciesDB());

    if (count($policies) > 0) {
        $data['data'] = array_map(
            /** @var GdprPolicy $el */
            function (GdprPolicy $el) {
                $retArr = [];
                $retArr['id'] = $el->getPolicyContentId();
                $retArr['title'] = $el->getTitle();
                $retArr['lastEditTS'] = is_null($el->getLastEditTS()) ? null : ts2dFN($el->getLastEditTS()) . ' ' . ts2tmFN($el->getLastEditTS());
                $retArr['mandatory'] = $el->getMandatory() ? true : false;
                $retArr['isPublished'] = $el->getIsPublished() ? true : false;
                $retArr['version'] = $el->getVersion();
                $actions = [];

                if (GdprActions::canDo(GdprActions::EDIT_POLICY, $el)) {
                    $actions[] = $el->getActionButton();
                }

                if (count($actions) > 0) {
                    $retArr['actions'] = array_reduce($actions, function ($carry, $item) {
                        if (strlen($carry) <= 0) {
                            $carry = '';
                        }
                        $carry .= ($item instanceof CBase ? $item->getHtml() : '');
                        return $carry;
                    });
                }

                return $retArr;
            },
            $policies
        );
    } else {
        $data['data'] = [];
    }
} catch (\Exception $e) {
    //  header(' ', true, 400);
    $data['data'] = [];
    $data['data']['error'] = $e->getMessage();
}

header('Content-Type: application/json');
die(json_encode($data, JSON_NUMERIC_CHECK));
