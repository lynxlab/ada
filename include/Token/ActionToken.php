<?php

use Lynxlab\ADA\Main\Token\ActionToken;

// Trigger: ClassWithNameSpace. The class ActionToken was declared with namespace Lynxlab\ADA\Main\Token. //

namespace Lynxlab\ADA\Main\Token;

abstract class ActionToken
{
    protected $isValid;
    protected $expiresAfter;
    protected $creationTimestamp;
    protected $userId;
    protected $action;
    protected $tokenString;

    public function isValid()
    {
        return !$this->alreadyUsed() && !$this->isExpired();
    }

    public function alreadyUsed()
    {
        return $this->isValid == ADA_TOKEN_IS_NOT_VALID;
    }

    public function markAsUsed()
    {
        $this->isValid = ADA_TOKEN_IS_NOT_VALID;
    }

    public function isExpired()
    {
        return ($this->creationTimestamp + $this->expiresAfter) < time();
    }

    public function fromArray($token_dataAr = [])
    {
        $this->isValid           = $token_dataAr['valido'];
        $this->creationTimestamp = $token_dataAr['timestamp_richiesta'];
        $this->userId            = $token_dataAr['id_utente'];
        $this->tokenString       = $token_dataAr['token'];
    }

    public function toArray()
    {
        $token_dataAr = [
            'token'               => $this->tokenString,
            'id_utente'           => $this->userId,
            'timestamp_richiesta' => $this->creationTimestamp,
            'azione'              => $this->action,
            'valido'              => $this->isValid,
        ];

        return $token_dataAr;
    }

    public function setUserId($user_id)
    {
        $this->userId = $user_id;
    }

    public function generateTokenStringFrom($text)
    {
        $this->tokenString = sha1($text . $this->creationTimestamp);
    }

    public function getTokenString()
    {
        return $this->tokenString;
    }

    protected function initializeToken()
    {
        $this->isValid           = ADA_TOKEN_IS_VALID;
        $this->creationTimestamp = time();
    }
}
