<?php

namespace Lynxlab\ADA\Main\Token;

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;

class TokenManager
{
    public static function createTokenForUserRegistration($userObj)
    {
        return self::createToken(new UserRegistrationToken(), $userObj);
    }

    public static function createTokenForPasswordChange($userObj)
    {
        return self::createToken(new ChangePasswordToken(), $userObj);
    }

    public static function updateToken(ActionToken $token)
    {
        $common_dh = $GLOBALS['common_dh'];

        $token_dataAr = $token->toArray();
        $result = $common_dh->updateToken($token_dataAr);
        if (AMACommonDataHandler::isError($result)) {
            return false;
        }
        return true;
    }

    private static function createToken($tokenObj, $userObj)
    {
        $tokenObj->generateTokenStringFrom($userObj->getUserName());
        $tokenObj->setUserId($userObj->getId());

        if (self::save($tokenObj)) {
            return $tokenObj;
        }
        return false;
    }

    private static function save(ActionToken $token)
    {
        $common_dh = $GLOBALS['common_dh'];

        $token_dataAr = $token->toArray();
        $result = $common_dh->addToken($token_dataAr);
        if (AMACommonDataHandler::isError($result)) {
            return false;
        }

        return true;
    }
}
