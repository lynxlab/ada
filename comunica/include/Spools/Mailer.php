<?php

namespace Lynxlab\ADA\Comunica\Spools;

use Lynxlab\ADA\ADAPHPMailer\ADAPHPMailer;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class Mailer
{
    /**
     * send an email
     *
     *
     * @access  public
     *
     * @param   $message_ha - contains all data of message to send
     *                        this also means the recipients list
     *                        the parameter is an hash whose keys are:
     *                        data_ora,
     *                        tipo,
     *                        titolo,
     *                        mittente*,
     *                        destinatari**,
     *                        priorita,
     *                        testo
     *            $sender_email -
     *            $recipients_emails_ar - an array of all recipients
     *
     * @return    bool|AMAError an AMAError object if something goes wrong
     *
     */
    public function sendMail($message_ha, $sender_email, $recipients_emails_ar)
    {
        // logger("entered Mailer::send_mail", 3);

        if (DEV_ALLOW_SENDING_EMAILS) {
            $recipient_list = implode(",", $recipients_emails_ar);

            $headers = "From: $sender_email\n"
                . "BCC: $sender_email\n"
                . "Reply-To:$sender_email\n"
                . "X-Mailer: ADA\n"
                . "MIME-Version: 1.0\n"
                . "Content-Type: text/plain; charset=UTF-8\n"
                . "Content-Trasfer-Encoding: 8bit\n\n";

            $subject = $message_ha['titolo'];

            $message = $message_ha['testo']
                . "<br/><br/><hr style='border:0; border-top: 1px solid #eee;'/><br/>"
                . sprintf(translateFN("Messaggio generato da %s. Per maggiori informazioni consulta %s"), PORTAL_NAME, BaseHtmlLib::link(HTTP_ROOT_DIR, HTTP_ROOT_DIR)->getHtml());

            if (defined('ADA_SMTP') && ADA_SMTP) {
                $phpmailer = new ADAPHPMailer();
                $phpmailer->CharSet = ADA_CHARSET;
                $phpmailer->configSend();
                $phpmailer->SetFrom($sender_email);
                $phpmailer->AddReplyTo($sender_email);
                $phpmailer->IsHTML(true);
                $phpmailer->Subject = $subject;
                $phpmailer->Body = nl2br($message);
                $phpmailer->AltBody = strip_tags(html_entity_decode($message, ENT_QUOTES, ADA_CHARSET));
                $phpmailer->clearAllRecipients();
                foreach ($recipients_emails_ar as $recipient) {
                    $phpmailer->addAddress($recipient);
                    $res = $phpmailer->Send();
                    $phpmailer->clearAllRecipients();
                }
            } else {
                $res =  @mail($recipient_list, '=?UTF-8?B?' . base64_encode($subject) . '?=', $message, $headers);
            }
            if (!$res) {
                $errObj = new ADAError(null, "Errore nell'invio dell'email", 'Mailer', AMA_ERR_SEND_MSG);
            }
        }
        return $errObj ?? true;
    }

    public function clean()
    {
        // logger("entered MailerSpool::clean", 3);
    }
}
