<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\Course\CourseInstance;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\User\ADAGuest;
use Lynxlab\ADA\Main\Utilities;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Performs basic controls before entering this module
 */
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];

/**
 * Get needed objects
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'course', 'course_instance'],
];

$trackPageToNavigationHistory = false;
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

$self = Utilities::whoami(); // to select the right template
/*
 * INCLUSIONE SPECIFICA PER PAYPAL
 */
if (file_exists(ROOT_DIR . '/browsing/paypal/paypal_conf.inc.php')) {
    require_once ROOT_DIR . '/browsing/paypal/paypal_conf.inc.php';
    $paypal_allowed = true;
}

$today_date = Utilities::todayDateFN();
$providerId = DataValidator::isUinteger($_GET['provider']);
$courseId = DataValidator::isUinteger($_GET['course']);
$instanceId = DataValidator::isUinteger($_GET['instance']);
$studentId = DataValidator::isUinteger($_GET['student']);

$testerInfoAr = $common_dh->getTesterInfoFromId($providerId, AMA_FETCH_BOTH);
if (!AMACommonDataHandler::isError($testerInfoAr)) {
    $provider_name = $testerInfoAr[1];
    $tester = $testerInfoAr[10];
    $tester_dh = AMADataHandler::instance(MultiPort::getDSN($tester));
    // $currentTesterId = $newTesterId;
    $GLOBALS['dh'] = $tester_dh;
    $dh = $tester_dh;

    /*
    * GESTIONE LOG
    */
    $logStr = "";
    if (!is_dir(ROOT_DIR . '/log/paypal/')) {
        $oldmask = umask(0);
        mkdir(ROOT_DIR . '/log/paypal/', 0775, true);
        umask($oldmask);
    }
    $log_file = ROOT_DIR . '/log/paypal/' . PAYPAL_PDT_LOG;
    $fpx = fopen($log_file, 'a');
    $debug = 1;

    // id dello studente
    if (!isset($studentId)) {
        $studentId = $sess_id_user;
    }

    if ($debug == 1) {
        fwrite($fpx, "INIZIO processo Confirm \n");
        fwrite($fpx, "Student: $studentId \n");
    }
    /*
     * Instance Object
     */
    $instanceObj = new CourseInstance($instanceId);
    //    print_r($instanceObj);
    $price = $instanceObj->getPrice();
    $course = $dh->getCourse($courseId);
    $course_name = $course['titolo'];

    if (!isset($back_url)) {
        $back_url = "student.php";
    }

    // preparazione output HTML e print dell' output
    $title = translateFN("Conferma pagamento iscrizione al corso");
    //    $link_annulla_iscrizione = "<a href=\"".$http_root_dir . "/iscrizione/student_course_instance_unsubscribe.php?id_instance=".
    $instanceId . "&id_student=" . $studentId . "&back_url=student_course_instance_menu.php\">" . translateFN('Annulla iscrizione') . "</a>";
    $link_torna_home = "<a href=\"" . $http_root_dir . "/browsing/student.php\">" . translateFN('Torna alla Home') . "</a>";

    $info_div = CDOMElement::create('DIV', 'id:info_div');
    $info_div->setAttribute('class', 'info_div');
    $label_text = CDOMElement::create('span', 'class:info');
    $label_text->addChild(new CText(translateFN('La tua iscrizione è stata effettuata con successo.')));
    $info_div->addChild($label_text);
    $homeUser = $userObj->getHomePage();
    $link_span = CDOMElement::create('span', 'class:info_link');
    $link_to_home = BaseHtmlLib::link($homeUser, translateFN('vai alla home per accedere.'));
    $link_span->addChild($link_to_home);
    $info_div->addChild($link_span);
    //$data = new CText(translateFN('La tua iscrizione è stata effettuata con successo.'));
    $data = $info_div;


    /*
     * MANAGE PDT FROM PAYPAL
     *
     */
    // assigned session variables to local variables
    $paypal_email_address = PAYPAL_ACCOUNT;
    $product_price = $price;
    $price_currency = CURRENCY_CODE;

    // Init cURL
    $request = curl_init();
    // Set request options
    $req = [
        'cmd' => '_notify-synch',
        'tx' => trim($_GET['tx']),
        'at' => IDENTITY_CHECK,
    ];
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
    ]);

    // Execute request and get response and status code
    $response = curl_exec($request);
    $status   = curl_getinfo($request, CURLINFO_HTTP_CODE);
    // Close connection
    curl_close($request);

    if ($status != 200) {
        $ipn_log .= "Error connecting to Paypal\n";
        $message = translateFN("Errore di comunicazione con Paypal. Impossibile proseguire.");
        $message .= "<br />" . translateFN("Se non riceverei una mail di comunicazione, scrivi a") . ADA_ADMIN_MAIL_ADDRESS;
        if ($debug == 1) {
            fwrite($fpx, "Error connecting to Paypal\nSTATUS: %s\nRESPONSE: %s\n", $status, print_r($response, true));
        }
    } else {
        $lines = explode("\n", $response);
        $keyarray = [];
        if (in_array('SUCCESS', $lines)) {
            //        print_r($lines);
            array_shift($lines); // remove 'SUCCESS' line
            foreach ($lines as $line) {
                [$key, $val] = explode("=", $line, 2);
                $keyarray[urldecode($key)] = urldecode($val);
            }
            // check the payment_status is Completed
            // check that txn_id has not been previously processed
            // check that receiver_email is your Primary PayPal email
            // check that payment_amount/payment_currency are correct
            // process payment
            // $first_name = $keyarray['first_name'];
            // $last_name = $keyarray['last_name'];
            $item_name = $keyarray['item_name1'];
            $payment_amount = $keyarray['mc_gross'];
            $payment_currency = $keyarray['mc_currency'];
            $item_number = $keyarray['item_number1'];
            $txn_id = $keyarray['txn_id'];
            $receiver_email = $keyarray['business'];
            $payer_email = $keyarray['payer_email'];
            $payment_status = $keyarray['payment_status'];
            if (
                ($receiver_email == $paypal_email_address) &&
                ($payment_amount == $product_price) &&
                ($payment_currency == $price_currency) &&
                ($payment_status == 'Completed')
            ) {
                $date = AMADataHandler::tsToDate(time(), "%d/%m/%Y - %H:%M:%S");
                if ($debug == 1) {
                    fwrite($fpx, "Paypal PDT DATA OK - $date\n");
                }

                $first_name = $userObj->getFirstName();
                $last_name = $userObj->getLastName();

                //                $body = translateFN("Hai effettuato il pagamento di") . " ". $payment_amount ." EUR ". translateFN('tramite Paypal' . "\n\r").
                //                $body .= translateFN('Questo addebito verrà visualizzato sull\'estratto conto della carta di credito o prepagata come pagamento a PAYPAL *Lynx s.r.l.');
                $message_ha["testo"] = translateFN('Gentile') . " " . $first_name . ",\r\n" . translateFN("grazie per aver eseguito l'iscrizione al") . " " . $course_name . "\n\r\n\r";
                //                $message_ha["testo"] .=  $body_mail;
                //$message_ha["testo"] .= "\n\r\n\r". translateFN("I tuoi dati di accesso sono. username: ") . $username . "\n\r" . translateFN("password:" . " " . $password );
                //$message_ha["testo"] .= "\n\r". translateFN("Buono studio.");
                $message = nl2br($message_ha["testo"]);
                $message .= "<br />" . translateFN('Ti abbiamo inviato una mail di conferma dell\'iscrizione. Cliccando sul link inserito nella mail potrai accedere al corso');
                $message .= "<br />--------<br />" . translateFN('Dettagli di pagamento.');
                $message .= "<br />" . translateFN('Nome e cognome:') . " " . $first_name . " " . $last_name;
                $message .= "<br />" . translateFN('Importo:') . " " . $payment_currency . " " . $payment_amount;
                $message .= "<br />" . translateFN('Iscrizione al corso:') . " " . $course_name;
                $message .= "<br />" . translateFN('Data della transazione:') . " " . $date;
                $message .= "<br />" . translateFN('ID della transazione:') . " " . $txn_id;
                $message .= "<br />--------<br />";

                // subscribe student
                $isSubscribed = count(array_filter(
                    Subscription::findSubscriptionsToClassRoom($instanceObj->getId()),
                    fn ($s) => $userObj->getId() == $s->getSubscriberId()
                )) > 0;
                if (!$isSubscribed) {
                    $ressub = $dh->courseInstanceStudentSubscribe($instanceObj->getId(), $userObj->getId(), ADA_STATUS_SUBSCRIBED, $instanceObj->getStartLevelStudent());
                    if ($debug == 1) {
                        if (!AMADB::isError($ressub)) {
                            fwrite($fpx, "Successfully subscribed!!\n");
                        } else {
                            fwrite($fpx, "DB Error while subscribing!!\n");
                        }
                    }
                } else {
                    if ($debug == 1) {
                        fwrite($fpx, "Was already subscribed!!\n");
                    }
                }
            } else {
                $message = translateFN('Gentile') . " " . $first_name . ", <BR />";
                $message .= translateFN('il corso pagato non corrisponde ai dettagli in nostro possesso') . "<BR />";
                $message .= translateFN('se hai bisogno di maggiori informazioni scrivi una mail a:') . " " . ADA_ADMIN_MAIL_ADDRESS . "<br />";

                if ($debug == 1) {
                    fwrite($fpx, "Purchase does not match product details\n");
                }
            }
        } elseif (in_array('FAIL', $lines)) {
            $ipn_log .= "Error connecting to Paypal\n";
            $message = translateFN("Errore di comunicazione con Paypal. Impossibile proseguire.");
            $message .= "<br />" . translateFN("Se non riceverei una mail di comunicazione, scrivi a ") . ADA_ADMIN_MAIL_ADDRESS . "<br />";
            if ($debug == 1) {
                fwrite($fpx, "FAIL Error connecting to Paypal\nSTATUS: %s\nRESPONSE: %s\n", $status, print_r($response, true));
            } // log for manual investigation
        }
    }

    if ($debug == 1) {
        fwrite($fpx, "FINE processo Confirm \n======================\n\n");
        fclose($fpx);
    }
    /*
     * FINE GESTIONE PDT DA PAYPAL
     *
     */

    //$dati = $message;
    //    print_r($message);
    $info_div = CDOMElement::create('DIV', 'id:info_div');
    $info_div->setAttribute('class', 'info_div');
    $label_text = CDOMElement::create('span', 'class:info');
    $label_text->addChild(new CText($message));
    $info_div->addChild($label_text);
    if ($userObj->getStatus() == ADA_STATUS_PRESUBSCRIBED) {
        /**
         * add a message to info_div
         */
        $more_text = CDOMElement::create('span', 'class:moreinfo');
        $more_text->addChild(new CText(translateFN("se ci sono problemi nel confemrare o concludere la tua iscrizione, contatta la segreteria")));
        $info_div->addChild($more_text);
        /**
         * when a user does a self registration from login_required.php,
         * then registration.php calls ADALoggableUser::setSessionAndRedirect
         * to let info.php complete te subscription to the instance
         *
         * This has the side-effect that ADA sees the user as logged in,
         * so a new ADAGuest must be set in the session.
         */
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['sess_userObj'] = new ADAGuest();
        $_SESSION['sess_id_user_type'] = $_SESSION['sess_userObj']->getType();
    } else {
        /**
         * user is registered and logged in, show the link to the homepage
         */
        $homeUser = $userObj->getHomePage();
        $link_span = CDOMElement::create('span', 'class:info_link');
        $link_to_home = BaseHtmlLib::link($homeUser, translateFN('vai alla home per accedere.'));
        $link_span->addChild($link_to_home);
        $info_div->addChild($link_span);
    }
    //$data = new CText(translateFN('La tua iscrizione è stata effettuata con successo.'));
    $data = $info_div;
    //    print_r($data->getHtml());
    $path = translateFN('modulo di iscrizione');
    $dati = $link_torna_home;

    $field_data = [
        'menu' => "", //$menu,
        'path' => $path,
        //'data'=>$dati,
        'data' => $info_div->getHtml(),
        'help' => '', // $help,
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'titolo_corso' => $course_name,
        'annulla_iscrizione' => $link_annulla_iscrizione ?? '',
        'price' => $price,
    ];
} else {
    $dati = translateFN('Impossibile proseguire, Provider non trovato');
    $field_data = [
        'menu' => "", //$menu,
        'data' => $dati,
        'help' => '', // $help,
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'titolo_corso' => $course_name,
        'annulla_iscrizione' => $link_annulla_iscrizione,
        'price' => $price,
    ];
}

/**
 * Sends data to the rendering engine
 */
ARE::render($layout_dataAr, $field_data);
