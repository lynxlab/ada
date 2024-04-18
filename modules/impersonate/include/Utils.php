<?php

/**
 * @package     impersonate module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2021, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Impersonate;

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\User\ADALoggableUser;
use Lynxlab\ADA\Main\User\ADAUser;
use Lynxlab\ADA\Module\Impersonate\ImpersonateActions;
use Lynxlab\ADA\Module\Impersonate\LinkedUsers;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class Utils
{
    /**
     * session key to store the source user id
     */
    public const MODULES_IMPERSONATE_SESSBACKDATA = 'impersonate-back-data';

    /**
     * session key to store the LinkedUser object
     */
    public const MODULES_IMPERSONATE_SESSLINKEDOBJ = 'sess_LinkedUserObj';

    /**
     * generate the buttons element to be used to activate the impersonate function in the UI
     *
     * @return \Lynxlab\ADA\CORE\html4\CDOMElement
     */
    public static function generateMenu()
    {
        $div = CDOMElement::create('div', 'class:impersonate-link-container');
        if (isset($_SESSION[self::MODULES_IMPERSONATE_SESSBACKDATA])) {
            $link = CDOMElement::create('a', 'class:ui tiny button impersonatelink, href:' . MODULES_IMPERSONATE_HTTP . '/impersonate.php');
            $link->addChild(new CText(sprintf(translateFN('Torna %s'), $_SESSION[self::MODULES_IMPERSONATE_SESSBACKDATA]->getTypeAsString())));
            $div->addChild($link);
        } else {
            try {
                $impersonateObj = LinkedUsers::getSessionLinkedUser();
            } catch (ImpersonateException) {
                $impersonateObj = [];
            }
            if (count($impersonateObj) > 0) {
                foreach ($impersonateObj as $iObj) {
                    $link = CDOMElement::create('a', 'class:ui tiny button impersonatelink, href:' . MODULES_IMPERSONATE_HTTP . '/impersonate.php?t=' . $iObj->getLinkedType());
                    // create a user object to have getTypeAsString
                    $tmpUser = new ADAUser();
                    // $tmpUser->isSuper = false;
                    $tmpUser->setType($iObj->getLinkedType());
                    $link->addChild(new CText(sprintf(translateFN('Diventa %s'), $tmpUser->getTypeAsString())));
                    $div->addChild($link);
                }
            }
        }
        return $div;
    }

    /**
     * checks if the session user is impersonating one of the linked users
     *
     * @return boolean
     */
    public static function isImpersonating()
    {
        return (array_key_exists(self::MODULES_IMPERSONATE_SESSBACKDATA, $_SESSION) && $_SESSION[self::MODULES_IMPERSONATE_SESSBACKDATA] instanceof ADALoggableUser);
    }

    /**
     * builds the actions href for the switcher UI to link and unlink users
     *
     * @param int $userId user id of the link source
     * @param int $userType user type if the link source
     * @param array $linkedUsers array of LinkedUsers object for the source user id
     * @return array array of \CDOMElement obecjts, each one being an href
     */
    public static function buildActionsLinks($userId, $userType, $linkedUsers)
    {
        $retarr = [];
        $supportedLinks = LinkedUsers::getSupportedLinks()[$userType];
        foreach ($supportedLinks as $linkedType) {
            // create a user object to have getTypeAsString
            $tmpUser = new ADAUser();
            $tmpUser->setType($linkedType);
            if ($tmpUser->getType() == AMA_TYPE_TUTOR) {
                // $tmpUser->isSuper = false;
            }

            // filter the passed array to get only needed values
            $filteredUsers = array_filter($linkedUsers, fn ($el) => $el->getSourceId() == $userId && $el->getSourceType() == $userType && $el->getLinkedType() == $linkedType);

            $addLink = false;
            $img = CDOMElement::create('img');
            $link = CDOMElement::create('a', 'class:tooltip,href:javascript:void(0);');
            if (count($filteredUsers) > 0) {
                if (ImpersonateActions::canDo(ImpersonateActions::DELETE_LINKEDUSER)) {
                    // unlink
                    $img->setAttribute('src', MODULES_IMPERSONATE_HTTP . '/layout/img/unlink-' . $linkedType . '.png');
                    $link->setAttribute('onclick', 'javascript:deleteLinkedUser($j(this));');
                    $title = translateFN('Scollega %s');
                    $addLink = true;
                }
            } else {
                if (ImpersonateActions::canDo(ImpersonateActions::NEW_LINKEDUSER)) {
                    // link
                    $img->setAttribute('src', MODULES_IMPERSONATE_HTTP . '/layout/img/link-' . $linkedType . '.png');
                    $link->setAttribute('onclick', 'javascript:newLinkedUser($j(this));');
                    $title = translateFN('Collega %s');
                    $addLink = true;
                }
            }
            if ($addLink) {
                $link->addChild($img);
                $link->setAttribute('title', sprintf($title, $tmpUser->getTypeAsString()));
                $link->setAttribute('data-base-url', MODULES_IMPERSONATE_HTTP);
                $link->setAttribute('data-linked-type', $linkedType);
                $link->setAttribute('data-source-id', $userId);
                $retarr[] = $link;
            }
        }
        return $retarr;
    }
}
