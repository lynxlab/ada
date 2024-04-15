<?php

use Lynxlab\ADA\Main\Token\ChangePasswordToken;

use Lynxlab\ADA\Main\Token\ActionToken;

// Trigger: ClassWithNameSpace. The class ChangePasswordToken was declared with namespace Lynxlab\ADA\Main\Token. //

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
