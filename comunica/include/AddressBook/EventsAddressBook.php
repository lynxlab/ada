<?php

use Lynxlab\ADA\Comunica\AddressBook\EventsAddressBook;

use Lynxlab\ADA\Comunica\AddressBook\ADAAddressBook;

// Trigger: ClassWithNameSpace. The class EventsAddressBook was declared with namespace Lynxlab\ADA\Comunica\AddressBook. //

namespace Lynxlab\ADA\Comunica\AddressBook;

use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\User\ADALoggableUser;

class EventsAddressBook extends ADAAddressBook
{
    public static function create(ADALoggableUser $userObj)
    {

        $user_types_Ar = [
            AMA_TYPE_TUTOR => [AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_SWITCHER],
            AMA_TYPE_SWITCHER    => [AMA_TYPE_TUTOR, AMA_TYPE_STUDENT],
            AMA_TYPE_STUDENT => [AMA_TYPE_TUTOR, AMA_TYPE_STUDENT],
        ];

        $users_Ar = parent::fillAddressBook($userObj, $user_types_Ar);
        if ($users_Ar == false) {
            return new CText('');
        }
        return parent::getAddressBook($userObj, $user_types_Ar, $users_Ar);
    }
}
