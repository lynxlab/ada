<?php

namespace Lynxlab\ADA\ADAPHPMailer;

use Lynxlab\ADA\Main\Logger\ADAFileLogger;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;

class ADAPHPMailer extends PHPMailer
{
    public function configSend()
    {
        if (defined('ADA_SMTP') && ADA_SMTP) {
            $this->isSMTP();
            $this->SMTPKeepAlive = true;
            $this->Host = ADA_SMTP_HOST;
            $this->Port = ADA_SMTP_PORT;
            if (!is_null(ADA_SMTP_SECURE)) {
                $this->SMTPSecure = ADA_SMTP_SECURE;
            }
            $this->SMTPAuth = ADA_SMTP_AUTH;
            if ($this->SMTPAuth) {
                $this->Username = ADA_SMTP_USERNAME;
                $this->Password = ADA_SMTP_PASSWORD;
                if (defined('ADA_SMTP_AUTHTYPE') && ADA_SMTP_AUTHTYPE && strlen(ADA_SMTP_AUTHTYPE) > 0) {
                    $this->AuthType = ADA_SMTP_AUTHTYPE;
                }
            }
            if (defined('ADA_SMTP_DEBUG') && ADA_SMTP_DEBUG) {
                $this->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->Debugoutput = function ($str, $level) {
                    $logFile = ROOT_DIR . '/log/smtp-debug.log';
                    if (!is_file($logFile)) {
                        touch($logFile);
                    }
                    ADAFileLogger::log("$level: message: $str", $logFile);
                };
            }
        } else {
            $this->isSendmail();
        }
    }

    public function send()
    {
        if (
            !defined('DEV_ALLOW_SENDING_EMAILS') ||
            (defined('DEV_ALLOW_SENDING_EMAILS') && DEV_ALLOW_SENDING_EMAILS)
        ) {
            return parent::send();
        }
        return true;
    }
}
