<?php

use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Module\EtherpadIntegration\AMAEtherpadDataHandler;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadActions;
use Lynxlab\ADA\Module\EtherpadIntegration\EtherpadException;
use Lynxlab\ADA\Module\EtherpadIntegration\Pads;
use Lynxlab\ADA\Module\EtherpadIntegration\Utils;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

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
[$allowedUsersAr, $neededObjAr] = array_values(EtherpadActions::getAllowedAndNeededAr());

/**
 * Performs basic controls before entering this module
 */
require_once(ROOT_DIR . '/include/module_init.inc.php');
BrowsingHelper::init($neededObjAr);
$self = Utilities::whoami();

try {
    $optionsAr = [];
    $padName = 'Errore documento condiviso';
    /**
     * @var AMAEtherpadDataHandler $etDH
     */
    $etDH = AMAEtherpadDataHandler::instance(MultiPort::getDSN($_SESSION['sess_selected_tester']));

    if (!array_key_exists('id_node', $_REQUEST)) {
        throw new EtherpadException('Specificare un nodo');
    } else {
        $nodeId = trim($_REQUEST['id_node']);
        $data = '';
        if ($nodeId !== Pads::INSTANCEPADID && false === DataValidator::validateNodeId($nodeId)) {
            throw new EtherpadException('ID nodo non valido');
        } else {
            if ($nodeId !== Pads::INSTANCEPADID) {
                // check that passed node exists
                $nodeData = $etDH->getNodeInfo($nodeId);
                if (AMADB::isError($nodeData)) {
                    throw new EtherpadException('ID nodo non valido');
                }
                $padName = sprintf(translateFN(Pads::NODEPADNAME), $nodeData['name']);
            } else {
                $padName = translateFN(Pads::INSTANCEPADNAME);
            }
            // passed nodeId looks good, pass data to the js
            $jsArgs = [
                'instanceId' => $courseInstanceObj->getId(),
                'userId' => $userObj->getId(),
                'nodeId' => $nodeId,
                'baseUrl' => MODULES_ETHERPAD_HTTP,
                'etherpadUrl' => Utils::getEtherpadURL(false),
            ];
            $optionsAr['onload_func'] = 'initEtherpad(' . htmlspecialchars(json_encode($jsArgs), ENT_QUOTES, ADA_CHARSET) . ', \'#contentcontent > .first\');';
        }
    }
} catch (EtherpadException $e) {
    $text = [
        'header' => translateFN('Errore'),
        'message' => translateFN($e->getMessage()),
    ];
    if (!isset($data)) {
        $data = '';
    }
    $data .= '<div class="ui icon error message"><i class="ban circle icon"></i><div class="content">';
    if (array_key_exists('header', $text) && strlen($text['header']) > 0) {
        $data .= '<div class="header">' . $text['header'] . '</div>';
    }
    if (array_key_exists('message', $text) && strlen($text['message']) > 0) {
        $data .= '<p>' . $text['message'] . '</p>';
    }
    $data .= '</div></div>';
}

$online_users_listing_mode = 2;
$id_course_instance = $courseInstanceObj->getId();
$online_users = ADALoggableUser::getOnlineUsersFN($id_course_instance, $online_users_listing_mode);

$content_dataAr = [
    'course_title' => $courseObj->getTitle() . ' &gt; ' . $courseInstanceObj->getTitle() . (strlen($padName) > 0 ? ' &gt; ' . ucwords(translateFN($padName)) : ''),
    'user_name' => $user_name,
    'user_type' => $user_type,
    'edit_profile' => $userObj->getEditProfilePage(),
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'help'  => $help ?? null,
    'status' => $status,
    'chat_users' => $online_users,
    'chat_link' => $chat_link ?? '',
    'data' => $data,
];

$backNode = false;
if (array_key_exists('sess_id_node', $_SESSION)) {
    $backNode = DataValidator::validateNodeId($_SESSION['sess_id_node']);
}
$content_dataAr['go_back'] = $backNode === false ? 'javascript:history.go(-1);' : HTTP_ROOT_DIR . '/browsing/view.php?id_node=' . $backNode;

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);
