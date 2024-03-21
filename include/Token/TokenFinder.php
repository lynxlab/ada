<?php

namespace Lynxlab\ADA\Main\Token;

class TokenFinder
{
    public static function findTokenForUserRegistration($user_id, $token)
    {
        $common_dh = $GLOBALS['common_dh'];

        $token_dataAr = $common_dh->get_token($token, $user_id, ADA_TOKEN_FOR_REGISTRATION);
        if (AMA_Common_DataHandler::isError($token_dataAr)) {
            return false;
        }
        $tokenObj = new UserRegistrationToken();
        $tokenObj->fromArray($token_dataAr);
        return $tokenObj;
    }

    public static function findTokenForPasswordChange($user_id, $token)
    {
        $common_dh = $GLOBALS['common_dh'];

        $token_dataAr = $common_dh->get_token($token, $user_id, ADA_TOKEN_FOR_PASSWORD_CHANGE);
        if (AMA_Common_DataHandler::isError($token_dataAr)) {
            return false;
        }
        $tokenObj = new ChangePasswordToken();
        $tokenObj->fromArray($token_dataAr);
        return $tokenObj;
    }
}