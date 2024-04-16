<?php

namespace Lynxlab\ADA\Main\Token;

class UserRegistrationToken extends ActionToken
{
    public function __construct()
    {

        parent::initializeToken();

        $this->action       = ADA_TOKEN_FOR_REGISTRATION;
        $this->expiresAfter = ADA_TOKEN_FOR_REGISTRATION_EXPIRES_AFTER;
    }
}
