<?php

namespace Lynxlab\ADA\Comunica\AddressBook;

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\User\ADALoggableUser;

class MessagesAddressBook extends ADAAddressBook
{
    public static function create(ADALoggableUser $userObj)
    {

        $user_types_Ar = [
            AMA_TYPE_ADMIN       => [AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_SWITCHER],
            AMA_TYPE_SWITCHER    => [AMA_TYPE_TUTOR, AMA_TYPE_STUDENT],
            AMA_TYPE_AUTHOR      => [],
            AMA_TYPE_TUTOR        => [AMA_TYPE_SWITCHER, AMA_TYPE_STUDENT],
            AMA_TYPE_STUDENT     => [AMA_TYPE_SWITCHER, AMA_TYPE_TUTOR],
        ];
        /**
         * @author giorgio 13/apr/2015
         *
         * if userObj is a superTutor, add AMA_TYPE_TUTOR to the addressbook
         */
        if ($userObj->getType() == AMA_TYPE_TUTOR && $userObj->isSuper()) {
            $user_types_Ar[AMA_TYPE_TUTOR][] = AMA_TYPE_TUTOR;
        }

        $users_Ar = parent::fillAddressBook($userObj, $user_types_Ar);
        if ($users_Ar == false) {
            return new CText('');
        }
        return parent::getAddressBook($userObj, $user_types_Ar, $users_Ar);
    }
}
