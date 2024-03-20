<?php

namespace Lynxlab\ADA\Main\Token;

class ChangePasswordToken extends ActionToken
{
    public function __construct()
    {

        parent::initializeToken();

        $this->action       = ADA_TOKEN_FOR_PASSWORD_CHANGE;
        $this->expiresAfter = ADA_TOKEN_FOR_PASSWORD_CHANGE_EXPIRES_AFTER;
    }
}
