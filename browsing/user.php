<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Badges\AMABadgesDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\AMACompleteDataHandler;
use Lynxlab\ADA\Module\Servicecomplete\CompleteConditionSet;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\ts2dFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_STUDENT => ['layout', 'default_tester'],
];
require_once ROOT_DIR . '/include/module_init.inc.php';
$self = whoami();

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
 * @var \Lynxlab\ADA\Main\User\ADAAbstractUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

$courseInstances = [];
$serviceProviders = $userObj->getTesters();
$displayWhatsNew = false;
$displayTable = false;

/**
 * change the two below call to active to let the closed
 * instances completely disappear from the HTML table
 */
if (count($serviceProviders) == 1) {
    $provider_dh = AMADataHandler::instance(MultiPort::getDSN($serviceProviders[0]));
    $courseInstances = $provider_dh->getCourseInstancesForThisStudent($userObj->getId(), true);
} else {
    foreach ($serviceProviders as $Provider) {
        $provider_dh = AMADataHandler::instance(MultiPort::getDSN($Provider));
        $courseInstances_provider = $provider_dh->getCourseInstancesForThisStudent($userObj->getId(), true);
        if (!is_array($courseInstances_provider)) {
            $courseInstances_provider = [];
        }
        $courseInstances = array_merge($courseInstances, $courseInstances_provider);
    }
}

if (!AMADataHandler::isError($courseInstances)) {
    /**
     * @author giorgio 23/apr/2015
     *
     *  filter course instance that are associated to a level of service having:
     *  - nonzero value in isPublic, so that all instances of public courses will not be shown here
     *  - zero value in IsPublic and the service level in the $GLOBALS['userHiddenServiceTypes'] array, to hide autosubscription instances
     */
    if (!is_array($courseInstances)) {
        $courseInstances = [];
    }
    $courseInstances = array_filter($courseInstances, function ($courseInstance) {
        if (is_null($courseInstance['tipo_servizio'])) {
            $courseInstance['tipo_servizio'] = DEFAULT_SERVICE_TYPE;
        }
        $actualServiceType = !is_null($courseInstance['istanza_tipo_servizio']) ? $courseInstance['istanza_tipo_servizio'] : $courseInstance['tipo_servizio'];
        if (intval($_SESSION['service_level_info'][$actualServiceType]['isPublic']) !== 0) {
            $filter = false;
        } elseif (in_array($actualServiceType, $GLOBALS['userHiddenServiceTypes'])) {
            $filter = false;
        }
        return ($filter ?? true);
    });

    /**
     * @author giorgio 22/feb/2016
     *
     * if an id_course and id_course_instance are passed in $_GET, filter the found
     * course instances so that at the end courseInstances array should have one element
     * and the proper page is shown to the logged user (as if she was subscribed to one course only)
     */
    $id_course = DataValidator::checkInputValues('id_course', 'CourseId', INPUT_GET);
    $id_course_instance = DataValidator::checkInputValues('id_course_instance', 'CourseId', INPUT_GET);

    if (($id_course !== false) && ($id_course_instance !== false)) {
        $courseInstances = array_filter($courseInstances, fn ($courseInstance) => ($courseInstance['id_corso'] == $id_course) &&
            ($courseInstance['id_istanza_corso'] == $id_course_instance));
        /**
         * @author giorgio 24/apr/2013
         *
         * if the 3 $_GET params are all set, display the (kind of) "what's new" page
         */
        $displayWhatsNew = isset($_GET['id_node']);
    }

    $found = count($courseInstances);
    $displayTable = $found > 1;

    $thead_dataAr = [
        translateFN('Titolo'),
        translateFN('Iniziato'),
        translateFN('Data inizio'),
        translateFN('Durata'),
        translateFN('Data fine'),
        translateFN('Azioni'),
    ];
    $tbody_dataAr = [];

    foreach ($courseInstances as $c) {
        $courseId = $c['id_corso'];
        $nodeId = $courseId . '_0';
        $courseInstanceId = $c['id_istanza_corso'];
        $subscription_status = $c['status'];
        $started = ($c['data_inizio'] > 0 && $c['data_inizio'] < time()) ? translateFN('Si') : translateFN('No');
        $start_date = ($c['data_inizio'] > 0) ? $c['data_inizio'] : $c['data_inizio_previsto'];
        if (isset($c['data_iscrizione']) && intval($c['data_iscrizione']) > 0) {
            $start_date = intval($c['data_iscrizione']);
        }
        $isEnded = ($c['data_fine'] > 0 && $c['data_fine'] < time()) ? true : false;
        $isStarted = ($c['data_inizio'] > 0 && $c['data_inizio'] <= time()) ? true : false;
        $self_instruction = $c['self_instruction'] ?? 0;

        if (!$isEnded && isset($c['duration_subscription']) && intval($c['duration_subscription']) > 0) {
            $duration = $c['duration_subscription'];
            $end_date = $common_dh->addNumberOfDays($duration, $start_date);
        } else {
            $duration = $c['durata'];
            $end_date = $c['data_fine'];
        }
        /**
         * giorgio 13/01/2021: force end_date to have time set to 23:59:59
         */
        $end_date = strtotime('tomorrow midnight', $end_date) - 1;

        /**
         * check service completeness and badges
         */
        $provider = $common_dh->getTesterInfoFromIdCourse($courseId);
        if (array_key_exists('puntatore', $provider)) {
            if (isset($GLOBALS['dh'])) {
                $oldDH = $GLOBALS['dh'];
            }
            $GLOBALS['dh'] = AMADataHandler::instance(MultiPort::getDSN($provider['puntatore']));
            $_SESSION['sess_selected_tester'] = $provider['puntatore'];
            if ($subscription_status != ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED) {
                if ($new_status = BrowsingHelper::checkServiceComplete($userObj, $courseId, $courseInstanceId) > 0) {
                    $subscription_status = $new_status;
                }
            }
            BrowsingHelper::checkRewardedBadges($userObj, $courseId, $courseInstanceId);
            unset($_SESSION['sess_selected_tester']);
            if (isset($oldDH)) {
                $GLOBALS['dh'] = $oldDH;
                unset($oldDH);
            } else {
                unset($GLOBALS['dh']);
            }
        }
        unset($provider);
        /**
         * done check service completeness and badges
         */

        $access_link = BaseHtmlLib::link(
            "#",
            translateFN('Attendi apertura corso')
        );

        if (!in_array($subscription_status, [ADA_STATUS_SUBSCRIBED, ADA_STATUS_VISITOR, ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED, ADA_STATUS_TERMINATED])) {
            $access_link = BaseHtmlLib::link("#", translateFN('Abilitazione in corso...'));
        } elseif ($isStarted) {
            /**
             * @author giorgio 03/apr/2015
             *
             * if user is subscribed or completed and the subscription date + subscription_duration
             * falls after 'now', must set the subscription status to terminated
             */
            if (in_array($subscription_status, [ADA_STATUS_SUBSCRIBED, ADA_STATUS_COMPLETED])) {
                if (!isset($c['data_iscrizione']) || is_null($c['data_iscrizione']) || intval($c['data_iscrizione']) === 0) {
                    $c['data_iscrizione'] = time();
                }
                if (!isset($c['duration_subscription']) || is_null($c['duration_subscription'])) {
                    $c['duration_subscription'] = PHP_INT_MAX;
                }
                $subscritionEndDate = $common_dh->addNumberOfDays($c['duration_subscription'], intval($c['data_iscrizione']));
                /**
                 * giorgio 13/01/2021: force subscritionEndDate to have time set to 23:59:59
                 */
                $subscritionEndDate = strtotime('tomorrow midnight', $subscritionEndDate) - 1;

                if ($isEnded || time() >= $subscritionEndDate) {
                    $userObj->setTerminatedStatusForInstance($courseId, $courseInstanceId);
                    $subscription_status = ADA_STATUS_TERMINATED;
                }
            }

            if (!$displayTable) {
                /**
                 * automatic entering the course
                 * redirect to the first node of the ONLY instance to which the student is subscribed
                 * only the first time (coming from login page)
                 * TODO: we should use some kind of constant to change this behavoiur for specific installation, provider, service, courses, instances, ....
                 */
                $navigationHistoryObj = $_SESSION['sess_navigation_history'];
                if (
                    ADA_USER_AUTOMATIC_ENTER && $navigationHistoryObj->userComesFromLoginPage() && $isStarted && !$isEnded
                    && !in_array($subscription_status, [ADA_STATUS_SUBSCRIBED, ADA_STATUS_VISITOR, ADA_STATUS_TERMINATED])
                ) {
                    header("Location: view.php?id_node=$nodeId&id_course=$courseId&id_course_instance=$courseInstanceId");
                    exit();
                }

                /*
                * @author giorgio 24/apr/2013
                *
                * one course found, with something new to display
                * set displayWhatsNew to true, the renderer will render the appropriate page
                *
                * NOTE: user.php with appropriate parameters is a kind of "whats new" page
                *
                */
                if (!$isEnded && $subscription_status != ADA_STATUS_TERMINATED && MultiPort::checkWhatsNew($userObj, $courseInstanceId, $courseId)) {
                    // uncomment to displayWhatsNew always, if the course has some news
                    // $displayWhatsNew = true;
                } else {
                    // resume 'normal' behaviour
                    $access_link = CDOMElement::create('div');
                    $link = CDOMElement::create('a', 'href:view.php?id_node=' . $nodeId . '&id_course=' . $courseId . '&id_course_instance=' . $courseInstanceId);
                    if ($isEnded || $subscription_status == ADA_STATUS_TERMINATED || $subscription_status == ADA_STATUS_COMPLETED) {
                        $link->addChild(new CText(translateFN('Rivedi il corso')));
                    } elseif ($isStarted && !$isEnded) {
                        $link->addChild(new CText(translateFN('Accedi')));
                    }
                    $access_link->addChild($link);
                }
            } else {
                /**
                 * set access link
                 */
                $access_link = CDOMElement::create('div');
                $link = CDOMElement::create('a');
                $linkParams = [
                    'id_course' => $courseId,
                    'id_course_instance' => $courseInstanceId,
                ];
                if ($isEnded || in_array($subscription_status, [ADA_STATUS_TERMINATED, ADA_STATUS_COMPLETED])) {
                    $link->addChild(new CText(translateFN('Rivedi il corso')));
                    $linkScript = 'view.php';
                    $linkParams['id_node'] = $nodeId;
                } elseif ($isStarted && !$isEnded) {
                    $linkScript = 'user.php';
                    $link->addChild(new CText(translateFN('Accedi')));
                }
                $link->setAttribute('href', $linkScript . '?' . http_build_query($linkParams));
                $access_link->addChild($link);
                /**
                 * done access link
                 */

                // @author giorgio 24/apr/2013
                // adds whats new link if needed
                if (!$isEnded && ($subscription_status != ADA_STATUS_TERMINATED || $subscription_status != ADA_STATUS_COMPLETED) && MultiPort::checkWhatsNew($userObj, $courseInstanceId, $courseId)) {
                    $link = CDOMElement::create('a', 'href:user.php?id_node=' . $nodeId .
                        '&id_course=' . $courseId .
                        '&id_course_instance=' . $courseInstanceId);
                    $link->setAttribute("class", "whatsnewlink");
                    $link->addChild(new CText(translateFN('Novit&agrave;')));
                    $access_link->addChild($link);
                }

                $tbody_dataAr[] = [
                    $c['titolo'],
                    $started,
                    ts2dFN($start_date),
                    sprintf(translateFN('%d giorni'), $duration),
                    ts2dFN($end_date),
                    $access_link,
                ];
            }
        }
    }

    if ($found > 0) {
        $data = BaseHtmlLib::tableElement('class:doDataTable ' . ADA_SEMANTICUI_TABLECLASS, $thead_dataAr, $tbody_dataAr);
    } else {
        $displayTable = true; // will show an info message, using the same template as the courses table
        $data = CDOMElement::create('div', 'class:ui info icon large message');
        $data->addChild(CDOMElement::create('i', 'class:book icon'));
        $MSGcontent = CDOMElement::create('div', 'class:content');
        $MSGheader = CDOMElement::create('div', 'class:header');
        $MSGtext = CDOMElement::create('span', 'class:message');

        $data->addChild($MSGcontent);
        $MSGcontent->addChild($MSGheader);
        $MSGcontent->addChild($MSGtext);

        $MSGheader->addChild(new CText(translateFN('Non sei iscritto a nessun corso')));
        $MSGtext->addChild(BaseHtmlLib::link(HTTP_ROOT_DIR . '/info.php', translateFN('Clicca qui')));
        $MSGtext->addChild(new CText(' ' . translateFN('per vedere l\'elenco dei corsi a cui puoi iscriverti')));
    }
} else {
    $data = new CText('');
}

$last_access = $userObj->getLastAccessFN(null, "UT", null);
$last_access = AMADataHandler::tsToDate($last_access);
if ($last_access == '' || is_null($last_access)) {
    $last_access = '-';
}

$content_dataAr = [
    'today' => $ymdhms ?? null,
    'user_name' => $user_name,
    'user_level' => translateFN("Nd"),
    'status' => $status,
    'user_type' => $user_type,
    'last_visit' => $last_access,
    'message' => $message ?? null,
    'help' => $help ?? '',
    //    'iscritto' => $sub_course_data,
    //    'iscrivibili' => $to_sub_course_data,
    'course_title' => translateFN("Home dell'utente"),
    //    'corsi' => $corsi,
    //    'profilo' => $profilo,
    'data' => $data->getHtml(),
    'edit_profile' => $userObj->getEditProfilePage(),
    'messages' => $user_messages->getHtml(),
    'agenda' => $user_agenda->getHtml(),
    'events' => $user_events->getHtml(),
    'firstcol_title' => translateFN('Informazioni'),
];

/*
 * Output
 */
if ($displayTable) {
    // set default template
    $self = 'default';
} else {
    // will use user.tpl template here
    // look for passed course in courseInstances array
    /**
     * $courseInstances will always have 1 element here, use the first one
     */
    $c = reset($courseInstances);

    if ($self_instruction) {
        $self = 'userSelfInstruction';
    }

    // @author giorgio 24/apr/2013 students link
    $class_label = translateFN("Classe");
    // $students = "<a href='class_info.php?op=students_list&id_course_instance=$courseInstanceId&id_course=$courseId'>$class_label</a>";
    $students =  BaseHtmlLib::link("class_info.php?op=students_list&id_course_instance=$courseInstanceId&id_course=$courseId'", $class_label);
    $students_link = $students->getHtml();
    // @author giorgio
    // TODO: class_info.php non esiste, va creato o si toglie questo link?
    // unset ($students_link);

    // @author giorgio 26/apr/2013 new nodes
    $provider = $common_dh->getTesterInfoFromIdCourse($courseId);
    $providerId = $provider['id_tester'];

    $new_nodes_html = '';
    if ($displayWhatsNew) {
        $whatsnew = $userObj->getwhatsnew();
        $new_nodes = array_unique(
            array_filter($whatsnew[$provider['puntatore']], fn ($node) => str_contains($node['id_nodo'], $courseId))
        );

        //display a link to node if there are new nodes
        if (count($new_nodes) > 0) {
            $olelem = CDOMElement::create('ol');
            foreach ($new_nodes as $node) {
                $lielem = CDOMElement::create('li', 'class:ui item');
                $link = CDOMElement::create('a', 'href:view.php?id_node=' . $node['id_nodo'] . '&id_course=' . $courseId . '&id_course_instance=' . $courseInstanceId);
                $link->addChild(new CText($node['nome']));
                $lielem->addChild($link);
                $olelem->addChild($lielem);
                unset($lielem);
                unset($link);
            }
            $new_nodes_html = $olelem->getHtml();
        }
    }

    // @author giorgio 24/apr/2013 forum messages (NOTES!!!!! BE WARNED: THESE ARE NOTES!!!)
    $msg_forum_count = MultiPort::countNewNotes($userObj, $courseInstanceId);

    //display a direct link to forum if there are new messages
    if ($msg_forum_count > 0) {
        $link = CDOMElement::create('a', 'href:main_index.php?op=forum&id_course=' . $courseId . '&id_course_instance=' . $courseInstanceId);
        $link->addChild(new CText($msg_forum_count));
        $msg_forum_count = $link->getHtml();
        unset($link);
    }

    // @author giorgio 24/apr/2013 private messages
    $msg_simple_count = 0;
    $msg_simpleAr =  MultiPort::getUserMessages($userObj);
    foreach ($msg_simpleAr as $msg_simple_provider) {
        $msg_simple_count += count($msg_simple_provider);
    }

    // @author giorgio 24/apr/2013 agenda messages
    $msg_agenda_count = 0;
    $msg_agendaAr = MultiPort::getUserAgenda($userObj);
    foreach ($msg_agendaAr as $msg_agenda_provider) {
        $msg_agenda_count += count($msg_agenda_provider);
    }

    // @author giorgio 24/apr/2013 gocontinue link
    $last_visited_node_id = $userObj->getLastAccessFN($courseInstanceId, "N", AMADataHandler::instance(MultiPort::getDSN($provider['puntatore'])));
    if ((!empty($last_visited_node_id)) and (!is_object($last_visited_node_id)) && $isStarted && !$isEnded) {
        $last_node_visitedObj = BaseHtmlLib::link("view.php?id_course=$courseId&id_node=$last_visited_node_id&id_course_instance=$courseInstanceId", translateFN("Continua"));
        // echo "<!--"; var_dump($last_node_visitedObj);echo "-->";
        $last_node_visited_link =  $last_node_visitedObj->getHtml();
    } else {
        //$last_node_visitedObj = BaseHtmlLib::link("view.php?id_node=$nodeId&id_course=$courseId&id_course_instance=$courseInstanceId",translateFN('Continua'));
        $last_node_visitedObj = CDOMElement::create('span', 'class:disabled');
        $last_node_visitedObj->addChild(new CText(translateFN('Continua')));
        $last_node_visited_link = $last_node_visitedObj->getHtml();
    }

    // @author giorgio 24/apr/2013 gostart, goindex, goforum and gocontinue link
    // va sostituita con una select in AMA

    //      Graphical disposition:

    $gostart_link = translateFN('Il corso non è ancora iniziato');
    if (!in_array($subscription_status, [ADA_STATUS_SUBSCRIBED, ADA_STATUS_VISITOR, ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED, ADA_STATUS_TERMINATED])) {
        $gostart = BaseHtmlLib::link(
            "#",
            translateFN('Abilitazione in corso...')
        );
        $gostart_link = $gostart->getHtml();
        $last_node_visited_link = '';
    } elseif (!$isStarted) {
        $goindex  = CDOMElement::create('span', 'class:disabled');
        $goindex->addChild(new CText(translateFN('Indice')));
        $goindex_link = $goindex->getHtml();
        $goforum   = CDOMElement::create('span', 'class:disabled');
        $goforum->addChild(new CText(translateFN('Forum')));
        $goforum_link = $goforum->getHtml();
        $gohistory = CDOMElement::create('span', 'class:disabled');
        $gohistory->addChild(new CText(translateFN('Cronologia')));
    } elseif ($isStarted && !$isEnded) {
        if ($isEnded || $subscription_status == ADA_STATUS_TERMINATED || $subscription_status == ADA_STATUS_COMPLETED) {
            $startLabel = translateFN('Rivedi il corso');
        } elseif ($isStarted && !$isEnded) {
            $startLabel = translateFN('Inizia');
        }

        $gostart = BaseHtmlLib::link("view.php?id_node=$nodeId&id_course=$courseId&id_course_instance=$courseInstanceId", $startLabel);
        $gostart_link = $gostart->getHtml();
        $goindex  = BaseHtmlLib::link("main_index.php?id_course=$courseId&id_course_instance=$courseInstanceId", translateFN('Indice'));
        $goindex_link = $goindex->getHtml();
        $goforum   = BaseHtmlLib::link("main_index.php?id_course=$courseId&id_course_instance=$courseInstanceId&op=forum", translateFN('Forum'));
        $goforum_link = $goforum->getHtml();
        $gohistory = BaseHtmlLib::link('history.php?id_course=' . $courseId . '&id_course_instance=' . $courseInstanceId, translateFN('Cronologia'));

        $enddateForTemplate = AMADataHandler::tsToDate(min($c['data_fine'], $subscritionEndDate));
    }

    // must set the DH to the course provider one
    $GLOBALS['dh'] = AMADataHandler::instance(MultiPort::getDSN($provider['puntatore']));

    /**
     * @author giorgio 22/feb/2016
     * get course description
     */
    $cd_res = $GLOBALS['dh']->findCoursesList(['descrizione'], 'id_corso=' . $courseId);
    if (!AMADB::isError($cd_res) && is_array($cd_res) && count($cd_res) > 0) {
        $cd_el = reset($cd_res);
        $course_description = $cd_el['descrizione'];
    }

    $gochat_link = "";
    $content_dataAr['gostart'] = $gostart_link;
    $content_dataAr['gocontinue'] = $last_node_visited_link;
    $content_dataAr['goindex'] = $goindex_link ?? null;
    if ($new_nodes_html !== '') {
        $content_dataAr['course_description'] = $new_nodes_html;
        $content_dataAr['firstcol_title'] = translateFN("Cosa c'&egrave; di nuovo?");
    } else {
        $content_dataAr['course_description'] = $course_description ?? null;
    }
    // msg forum sono le note in realta'
    $content_dataAr['msg_forum'] = $msg_forum_count;
    $content_dataAr['msg_agenda'] =  $msg_agenda_count;
    $content_dataAr['msg'] = $msg_simple_count;
    $content_dataAr['goclasse'] = $students_link;
    $content_dataAr['goforum'] = $goforum_link ?? null;
    $content_dataAr['gochat'] = $gochat_link ?? null;
    $content_dataAr['course_title'] = $c['titolo'] . ' - ' . $c['title'];
    $content_dataAr['enddate'] = $enddateForTemplate ?? '-';
    $content_dataAr['gohistory'] = isset($gohistory) ? $gohistory->getHtml() : null;
    $content_dataAr['subscription_status'] = Subscription::subscriptionStatusArray()[$subscription_status];

    if (ModuleLoaderHelper::isLoaded('SERVICECOMPLETE')) {
        // need the service-complete module data handler
        $mydh = AMACompleteDataHandler::instance(MultiPort::getDSN($provider['puntatore']));
        // load the conditionset for this course
        $conditionSet = $mydh->getLinkedConditionsetForCourse($courseId);
        $mydh->disconnect();

        if ($conditionSet instanceof CompleteConditionSet) {
            $_SESSION['sess_selected_tester'] = $provider['puntatore'];
            // evaluate the conditionset for this instance ID and course ID
            $summary = $conditionSet->buildSummary([$courseInstanceId, $userObj->getId()]);
            unset($_SESSION['sess_selected_tester']);
            if (is_array($summary) && count($summary) > 0) {
                $content_dataAr['completeSummary'] = '';
                foreach ($summary as $condition => $condData) {
                    $content_dataAr['completeSummary'] .= $condition::getCDOMSummary($condData)->getHtml();
                }
            }
        }
    }

    if (ModuleLoaderHelper::isLoaded('BADGES')) {
        // need the badges module data handler
        $bdh = AMABadgesDataHandler::instance(MultiPort::getDSN($provider['puntatore']));
        // load all the badges for this course
        $courseBadges = $bdh->findBy('CourseBadge', ['id_corso' => $courseId]);
        if (!AMADB::isError($courseBadges) && is_array($courseBadges) && count($courseBadges) > 0) {
            $badgesLink = CDOMElement::create('div', 'class:item');
            $badgesLink->addChild(CDOMElement::create('i', 'class:certificate icon'));
            $badgesLink->setAttribute('data-dataurl', MODULES_BADGES_HTTP . '/ajax/getUserBadges.php?courseInstanceId=' . $courseInstanceId);
            $badgesLink->setAttribute('data-jsurl', MODULES_BADGES_HTTP . '/js/badgesToHTML.js');
            $popupLink = CDOMElement::create('a', 'id:bagesPopupLink,href:javascript:void(0);');
            $popupLink->addChild(new CText(translateFN('Badges')));
            $badgesLink->addChild($popupLink);
            $content_dataAr['badgesLink'] = $badgesLink->getHtml();
        }
    }

    if (!isset($content_dataAr['completeSummary'])) {
        $userObj->setCourseInstanceForHistory($courseInstanceId);
        $user_history = $userObj->getHistoryInCourseInstance($courseInstanceId);
        $span = CDOMElement::create('span', 'class:percent label item');
        $span->addChild(CDOMElement::create('i', 'class:ok circle icon'));
        $span->addChild(new CText(translateFN('Contenuti visitati') . ': <strong>' . $user_history->historyNodesVisitedpercentFN([ADA_GROUP_TYPE, ADA_LEAF_TYPE]) . '%</strong>'));
        $content_dataAr['completeSummary'] = $span->getHtml();
    }
    $GLOBALS['dh']->disconnect();
}

$layout_dataAr['CSS_filename'] = [
    JQUERY_UI_CSS,
    SEMANTICUI_DATATABLE_CSS,
    'user.css', // this file may use different templates, force user.css inclusion here
];
$layout_dataAr['JS_filename'] = [
    JQUERY,
    JQUERY_UI,
    JQUERY_DATATABLE,
    SEMANTICUI_DATATABLE,
    JQUERY_DATATABLE_DATE,
    ROOT_DIR . '/js/include/jquery/dataTables/formattedNumberSortPlugin.js',
    JQUERY_NO_CONFLICT,
    'user.js', // this file may use different templates, force user.js inclusion here
];

$layout_dataAr['widgets']['studentsOfInstance'] = compact(['courseId', 'courseInstanceId']);

ARE::render($layout_dataAr, $content_dataAr, null, ['onload_func' => 'initDoc();']);
