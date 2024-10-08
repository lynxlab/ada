<?php

use Lynxlab\ADA\Comunica\Spools\Mailer;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout', 'course', 'course_instance'];
/**
 * Performs basic controls before entering this module
 */
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR,];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_VISITOR => ['layout',],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_messages
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_agenda
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);
$common_dh = AMACommonDataHandler::getInstance();

/*
 * INCLUSIONE SPECIFICA PER PAYPAL
 */
if (file_exists(ROOT_DIR . '/browsing/paypal/paypal_conf.inc.php')) {
    require_once ROOT_DIR . '/browsing/paypal/paypal_conf.inc.php';
    $paypal_allowed = true;
}

/*
* GESTIONE LOG
*/
$logStr = "";
if (!is_dir(ROOT_DIR . '/log/paypal/')) {
    $oldmask = umask(0);
    mkdir(ROOT_DIR . '/log/paypal/', 0775, true);
    umask($oldmask);
}
$log_file = ROOT_DIR . '/log/paypal/' . PAYPAL_IPN_LOG;
$fpx = fopen($log_file, 'a');

error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", ROOT_DIR . '/log/paypal/paypal-ipn-error.log');

$debug = 1;
if ($debug == 1) {
    fwrite($fpx, "INIZIO processo IPN\n");
    fwrite($fpx, "Prima di init \n");
}

$lockfile = ADA_UPLOAD_PATH . $_POST['ipn_track_id'] . '.lock';

if (!is_file($lockfile)) {
    if (touch($lockfile)) {
        if ($debug == 1) {
            fwrite($fpx, "Lockfile $lockfile creato\n");
        }
    } else {
        if ($debug == 1) {
            fwrite($fpx, "Lockfile $lockfile NON creato!!!!\n");
        }
    }

    // buffer the output, close the connection with the browser and run a "background" task
    ob_end_clean();
    ignore_user_abort(true);
    // capture output
    ob_start();
    // these headers tell the browser to close the connection
    header("HTTP/1.1 200 OK");
    // flush all output
    ob_end_flush();
    flush();
    @ob_end_clean();
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }

    error_reporting(E_ALL);
    ini_set("log_errors", 1);
    ini_set("error_log", ROOT_DIR . '/log/paypal/paypal-ipn-error.log');

    $today_date = Utilities::todayDateFN();
    $providerId = DataValidator::isUinteger($_REQUEST['provider']);
    $courseId = DataValidator::isUinteger($_REQUEST['course']);
    $instanceId = DataValidator::isUinteger($_REQUEST['instance']);
    $studentId = DataValidator::isUinteger($_REQUEST['student']);

    $testerInfoAr = $common_dh->getTesterInfoFromId($providerId, AMA_FETCH_BOTH);
    $buyerObj = DBRead::readUser($studentId);
    if ((is_object($buyerObj)) && (!AMADataHandler::isError($buyerObj))) {
        if (!AMACommonDataHandler::isError($testerInfoAr)) {
            $provider_name = $testerInfoAr[1];
            $tester = $testerInfoAr[10];
            $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
            // $currentTesterId = $newTesterId;
            $GLOBALS['dh'] = $tester_dh;
            $dh = $tester_dh;

            // id dello studente
            if (!isset($instanceId)) {
                $instanceId = $sess_id_user; // ??????
            }

            /**
             * Instance Object
             */
            $instanceObj = new CourseInstance($instanceId);
            $price = $instanceObj->getPrice();
            $user_level = $instanceObj->getStartLevelStudent();
            $course = $dh->getCourse($courseId);
            $course_name = $course['titolo'];

            /**
             * GESTIONE IPN DA PAYPAL
             */
            // assigned session variables to local variables
            $paypal_email_address = PAYPAL_ACCOUNT;
            $product_price = $price;
            $price_currency = CURRENCY_CODE;

            $req = array_merge([
                'cmd' => '_notify-validate',
            ], $_POST);

            $request = curl_init();
            if ($debug == 1) {
                fwrite($fpx, sprintf("sending to Paypal...\n%s\n", print_r($req, true)));
            }
            curl_setopt_array($request, [
                CURLOPT_URL => 'https://' . PAYPAL_IPN_URL . '/cgi-bin/webscr',
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($req),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_ENCODING => 'gzip',
                CURLOPT_FORBID_REUSE => true,
                CURLOPT_FRESH_CONNECT => true,
                CURLOPT_CONNECTTIMEOUT => 30,
                CURLOPT_TIMEOUT => 60,
                CURLINFO_HEADER_OUT => true,
                CURLOPT_HTTPHEADER => [
                    'Connection: close',
                    'Expect: ',
                ],
            ]);

            // Execute request and get response and status code
            $response = curl_exec($request);
            $status   = curl_getinfo($request, CURLINFO_HTTP_CODE);
            curl_close($request);

            // assign posted variables to local variables
            $payment_status = $_POST['payment_status'];
            $payment_amount = $_POST['mc_gross'];
            $payment_currency = $_POST['mc_currency'];
            $txn_id = $_POST['txn_id'];
            $receiver_email = $_POST['receiver_email'];
            $payer_email = $_POST['payer_email'];
            // $invoice = $_POST['invoice'];
            $customeripaddress = $_POST['custom'];
            $productname = $_POST['item_name1'];

            if ($status != 200) {
                $message = translateFN("Errore di comunicazione con Paypal. Impossibile proseguire");
                if ($debug == 1) {
                    fwrite($fpx, "Error connecting to Paypal\nSTATUS: %s\nRESPONSE: %s\n", $status, print_r($response, true));
                }
            } else {
                if (strcmp('VERIFIED', $response) === 0) {
                    if ($debug == 1) {
                        fwrite($fpx, "Paypal IPN VERIFIED\n");
                    }
                    $firstname = $buyerObj->getFirstName();
                    $lastname = $buyerObj->getLastName();
                    $username = $buyerObj->getUserName();

                    if (trim($receiver_email) == '') {
                        $receiver_email = $_POST['receiver_email'];
                    }

                    if ($debug == 1) {
                        fwrite($fpx, "\nStudent: $studentId , Class: $instanceId \n");
                        fwrite($fpx, "\nPRODUCT DETAILS CHECK\n");
                        fwrite($fpx, "|$receiver_email| : |$paypal_email_address|\n");
                        fwrite($fpx, "|$payment_amount| : |$product_price|\n");
                        fwrite($fpx, "|$payment_currency| : |$price_currency|\n");
                        fwrite($fpx, "|$payment_status| : |Completed|\n\n");
                    }
                    if (
                        // ($receiver_email == $paypal_email_address) &&
                        ($payment_amount == $product_price) &&
                        ($payment_currency == $price_currency) &&
                        ($payment_status == 'Completed')
                    ) {
                        if ($debug == 1) {
                            fwrite($fpx, "Paypal IPN DATA OK\n");
                        }
                        $body_mail = translateFN("Hai effettuato il pagamento di") . " " . $payment_amount . " EUR " . translateFN('tramite Paypal' . "\n\r");
                        $body_mail .= translateFN('Questo addebito verrà visualizzato sull\'estratto conto della carta di credito o prepagata come pagamento a PAYPAL') . ' ' . PAYPAL_NAME_ACCOUNT;
                        $message_ha["titolo"] = PORTAL_NAME . " - " . translateFN('Conferma di pagamento') . ' - ' . translateFN("Iscrizione al corso:") . " " . $course_name;
                        $sender_email = ADA_ADMIN_MAIL_ADDRESS;
                        $recipients_emails_ar = [$payer_email];
                        if (!in_array($buyerObj->getEmail(), $recipients_emails_ar)) {
                            $recipients_emails_ar[] = $buyerObj->getEmail();
                        }

                        // iscrizione al corso
                        $status = 2;
                        $res = $dh->courseInstanceStudentSubscribe($instanceId, $studentId, $status, $user_level);
                        if (AMADataHandler::isError($res)) {
                            $msg = $res->getMessage();
                            //                    $dh->courseInstanceStudentPresubscribeRemove($id_course_instance,$id_studente);
                            //                    header("Location: $error?err_msg=$msg");
                            $message_ha["testo"] = translateFN('Gentile') . " " . $firstname . ",\r\n" . translateFN("Si è verificato un errore nell'iscrizione al corso") . " " . $course_name . "\n\r\n\r";
                            $message_ha["testo"] .=  $body_mail;
                            $message_ha["testo"] .= "\n\r\n\r" . translateFN('Per maggiori informazioni scrivi una mail a:') . " " . ADA_ADMIN_MAIL_ADDRESS;
                            $message_ha["testo"] .= "\n\r" . translateFN("Buono studio.");
                            $sender_email = ADA_ADMIN_MAIL_ADDRESS;
                            $recipients_emails_ar = [$payer_email, $buyerObj->getEmail()];
                        } else {
                            //                  header("Location: $back_url?id_studente=$id_studente");
                            // Send mail to the user with his/her data.
                            $switcherTypeAr = [AMA_TYPE_SWITCHER];
                            $extended_data = true;
                            $switcherList = $dh->getUsersByType($switcherTypeAr, $extended_data);
                            if (!AMADataHandler::isError($switcherList)) {
                                $switcher_email = $switcherList[0]['e_mail'];
                            } else {
                                $switcher_email = ADA_ADMIN_MAIL_ADDRESS;
                                if ($debug == 1) {
                                    fwrite($fpx, "switcher email from ADMIN" . PHP_EOL);
                                }
                            }
                            $notice_mail = sprintf(translateFN('Questa è una risposta automatica. Si prega di non rispondere a questa mail. Per informazioni scrivere a %s'), $switcher_email);
                            $message_ha["testo"] = $notice_mail . "\n\r\n\r";

                            $message_ha["testo"] .= translateFN('Gentile') . " " . $firstname . ",\r\n" . translateFN("grazie per esserti iscritto al corso") . " " . $course_name . "\n\r\n\r";
                            $message_ha["testo"] .=  $body_mail;
                            //$message_ha["testo"] .= "\n\r\n\r". translateFN("Ti ricordiamo i tuoi dati di accesso.\n\r username: ") . $user_name . "\n\r" . translateFN("password:" . " " . $user_password);
                            $message_ha["testo"] .= "\n\r\n\r" . translateFN("Questo è l'indirizzo per accedere al corso: ") . "\n\r" . $http_root_dir . "\n\r";
                            $message_ha["testo"] .= "\n\r" . translateFN("Una volta fatto il login, potrai accedere al corso");
                            $message_ha["testo"] .= "\n\r" . translateFN("Buono studio!");
                            $message_ha["testo"] .= "\n\r" . PORTAL_NAME;
                            $message_ha["testo"] .= "\n\r\n\r --------\r\n" . translateFN('Dettagli di pagamento.');
                            $message_ha["testo"] .= "\r\n" . translateFN('Nome e cognome:') . " " . $firstname . " " . $lastname;
                            $message_ha["testo"] .= "\r\n" . translateFN('Username:') . " " . $username;
                            $message_ha["testo"] .= "\r\n" . translateFN('Importo:') . " " . $payment_currency . " " . $payment_amount;
                            $message_ha["testo"] .= "\r\n" . translateFN('Iscrizione al corso:') . " " . $course_name;
                            $message_ha["testo"] .= "\r\n" . translateFN('ID della transazione:') . " " . $txn_id;
                            $message_ha["testo"] .= "\r\n --------\r\n";
                            //                    $message_ha["testo"] .= "\n\r\n\r". "------------------";

                            if ($debug == 1) {
                                fwrite($fpx, "Inviata mail a " . implode(",", $recipients_emails_ar) . "\n");
                            }
                        }
                        $mailer = new Mailer();
                        $res = $mailer->sendMail($message_ha, $sender_email, $recipients_emails_ar);
                    } else {
                        $message = translateFN('Gentile') . " " . $firstname . ", <BR />";
                        $message .= translateFN('il corso pagato non corrisponde ai dettagli in nostro possesso') . "<BR />";
                        $message .= translateFN('se hai bisogno di maggiori informazioni scrivi una mail a:') . " " . ADA_ADMIN_MAIL_ADDRESS;

                        $ipn_log .= "Purchase does not match product details\n";
                        if ($debug == 1) {
                            fwrite($fpx, "Purchase does not match product details\n");
                        }
                    }
                } elseif (strcmp('INVALID', $response) === 0) {
                    /*
                        $message = translateFN('Gentile') . " " . $firstname .", <BR />";
                        $message .= translateFN('Non è possibile verificare il tuo acquisto')."<BR />";
                        $message .= translateFN('Forse provando più tardi riuscirai ad acquistare il corso.');
                        *
                        */
                    if ($debug == 1) {
                        fwrite($fpx, "INVALID: We cannot verify your purchase\nSTATUS: %s\nRESPONSE: %s\n", $status, print_r($response, true));
                    }
                }
            }

            $unlinkStatus = unlink($lockfile);

            if ($debug == 1) {
                if ($unlinkStatus) {
                    fwrite($fpx, "Lockfile $lockfile rimosso\n");
                } else {
                    fwrite($fpx, "Lockfile $lockfile NON rimosso\n");
                }
                fwrite($fpx, "FINE processo IPN\n======================\n\n");
            }
            /**
             * FINE GESTIONE IPN DA PAYPAL
             *
             */
        } else {
            if ($debug == 1) {
                fwrite($fpx, "IPN Process started \n");
                fwrite($fpx, "IPN internal error of ADA \n");
            }
        }
    }
} else {
    if ($debug == 1 && $fpx) {
        fwrite($fpx, "Lockfile trovato, rispondo ok\r\n");
    }
    // Reply with an empty 200 response to indicate to paypal the IPN was received correctly.
    header("HTTP/1.1 200 OK");
}

if ($fpx) {
    fclose($fpx);
}

die();
