<?php

namespace Lynxlab\ADA\Main\Token;

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
        $result = $common_dh->update_token($token_dataAr);
        if (AMA_Common_DataHandler::isError($result)) {
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
        $result = $common_dh->add_token($token_dataAr);
        if (AMA_Common_DataHandler::isError($result)) {
            return false;
        }

        return true;
    }
}
