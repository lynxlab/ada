<?php

use Lynxlab\ADA\Main\Token\UserRegistrationToken;

use Lynxlab\ADA\Main\Token\TokenFinder;

use Lynxlab\ADA\Main\Token\ChangePasswordToken;

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;

// Trigger: ClassWithNameSpace. The class TokenFinder was declared with namespace Lynxlab\ADA\Main\Token. //

namespace Lynxlab\ADA\Main\Token;

class TokenFinder
{
    public static function findTokenForUserRegistration($user_id, $token)
    {
        $common_dh = $GLOBALS['common_dh'];

        $token_dataAr = $common_dh->getToken($token, $user_id, ADA_TOKEN_FOR_REGISTRATION);
        if (AMACommonDataHandler::isError($token_dataAr)) {
            return false;
        }
        $tokenObj = new UserRegistrationToken();
        $tokenObj->fromArray($token_dataAr);
        return $tokenObj;
    }

    public static function findTokenForPasswordChange($user_id, $token)
    {
        $common_dh = $GLOBALS['common_dh'];

        $token_dataAr = $common_dh->getToken($token, $user_id, ADA_TOKEN_FOR_PASSWORD_CHANGE);
        if (AMACommonDataHandler::isError($token_dataAr)) {
            return false;
        }
        $tokenObj = new ChangePasswordToken();
        $tokenObj->fromArray($token_dataAr);
        return $tokenObj;
    }
}
