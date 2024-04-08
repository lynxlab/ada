<?php

/**
 * NEWSLETTER MODULE.
 *
 * @package     newsletter module
 * @author          giorgio <g.consorti@lynxlab.com>
 * @copyright       Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link            newsletter
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Newsletter\Functions;

use Lynxlab\ADA\Module\Newsletter\AMANewsletterDataHandler;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

function convertFilterArrayToString($filterArray, $dh, $futureSentence = true)
{
    $html = translateFN('La newsletter') . ' ';

    $html .= ($futureSentence) ? translateFN("sar&agrave;") : translateFN("&egrave; stata");
    $html .= ' ' . translateFN('inviata a');

    $idNewsletter = isset($filterArray['id']) ? intval($filterArray['id']) : 0;

    if (isset($filterArray['userType']) && $filterArray['userType'] > 0) {
        if ($filterArray['userType'] != AMA_TYPE_STUDENT) {
            $html .= " <strong>" . translateFN("tutti") . "</strong> ";
            if ($filterArray['userType'] == AMA_TYPE_AUTHOR) {
                $html .= translateFN("gli") . " <strong>" . translateFN("autori");
            } elseif ($filterArray['userType'] == AMA_TYPE_SWITCHER) {
                $html .= translateFN("gli") . " <strong>" . translateFN("switcher");
            } elseif ($filterArray['userType'] == AMA_TYPE_TUTOR) {
                $html .= translateFN("i") . " <strong>" . translateFN("tutor");
            } elseif ($filterArray['userType'] == 9999) {
                $html .= translateFN("gli") . " <strong>" . translateFN("utenti");
            }
            $html .= '</strong>';
        } else {
            if (
                !((isset($filterArray['userPlatformStatus']) &&  $filterArray['userPlatformStatus'] != -1) ||
                    (isset($filterArray['userCourseStatus']) && $filterArray['userCourseStatus'] != -1))
            ) {
                $html .= " <strong>" . translateFN("tutti") . "</strong> ";
            }
            $html .= translateFN("gli") . " <strong>" . translateFN("studenti") . "</strong>";


            if (
                (isset($filterArray['userPlatformStatus']) &&  $filterArray['userPlatformStatus'] != -1) ||
                (isset($filterArray['userCourseStatus']) && $filterArray['userCourseStatus'] != -1)
            ) {
                $html .= " " . translateFN("con");

                if (isset($filterArray['userPlatformStatus']) &&  $filterArray['userPlatformStatus'] != -1) {
                    $html .= " " . translateFN("stato nella piattaforma") . ": <strong>";
                    if ($filterArray['userPlatformStatus'] == ADA_STATUS_PRESUBSCRIBED) {
                        $html .= translateFN("Non Confermato");
                    } elseif ($filterArray['userPlatformStatus'] == ADA_STATUS_REGISTERED) {
                        $html .= translateFN("Confermato");
                    }
                    $html .= "</strong>";

                    if (isset($filterArray['userCourseStatus']) && $filterArray['userCourseStatus'] != -1) {
                        $html .= " " . translateFN("e") . " ";
                    }
                }

                if (isset($filterArray['userCourseStatus']) && $filterArray['userCourseStatus'] != -1) {
                    $html .= " " . translateFN("stato") . ": <strong>";
                    if ($filterArray['userCourseStatus'] == ADA_SERVICE_SUBSCRIPTION_STATUS_UNDEFINED) {
                        $html .= translateFN('In visita');
                    } elseif ($filterArray['userCourseStatus'] == ADA_SERVICE_SUBSCRIPTION_STATUS_REQUESTED) {
                        $html .= translateFN('Preiscritto');
                    } elseif ($filterArray['userCourseStatus'] == ADA_SERVICE_SUBSCRIPTION_STATUS_ACCEPTED) {
                        $html .= translateFN('Iscritto');
                    } elseif ($filterArray['userCourseStatus'] == ADA_SERVICE_SUBSCRIPTION_STATUS_SUSPENDED) {
                        $html .= translateFN('Rimosso');
                    } elseif ($filterArray['userCourseStatus'] == ADA_SERVICE_SUBSCRIPTION_STATUS_COMPLETED) {
                        $html .=  translateFN('Completato');
                    }
                    $html .= "</strong>";
                }
            }
        }

        if (isset($filterArray['idInstance']) && intval($filterArray['idInstance']) > 0) {
            $html .= " " . translateFN("dell' istanza") . ': <strong>';
            $instanceInfo = $dh->course_instance_get(intval($filterArray['idInstance']));
            $html .= '<instancename></strong>';
        } else {
            $html .= " <strong>" . translateFN("di tutte le istanze") . "</strong>";
        }

        if (isset($filterArray['idCourse']) && intval($filterArray['idCourse']) > 0) {
            $html .= " " . translateFN("del corso") . ': <strong>';
            $courseInfo = $dh->get_course(intval($filterArray['idCourse']));
            $html .= '<coursename></strong>';
        } else {
            $html .= " <strong>" . translateFN("di tutti i corsi") . "</strong>";
        }

        $html = ucfirst(strtolower($html)) . '.';

        if (!isset($instanceInfo['title'])) {
            $instanceInfo['title'] = '';
        }

        $html = str_replace('<instancename>', $instanceInfo['title'], $html);

        if (!isset($courseInfo['nome'])) {
            $courseInfo['nome'] = '';
        }
        if (!isset($courseInfo['titolo'])) {
            $courseInfo['titolo'] = '';
        }

        $html = str_replace('<coursename>', '(' . $filterArray['idCourse'] . ')' . ' ' . $courseInfo['nome'] . '-' . $courseInfo['titolo'], $html);
    } else {
        $html = translateFN(DEFAULT_FILTER_SENTENCE);
    }

    return  $html;
}

function get_domain($url)
{
    $pieces = parse_url($url);
    $domain = $pieces['host'] ?? '';
    if (preg_match('/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i', $domain, $regs)) {
        return $regs['domain'];
    }
    return '';
}

// should something have gone wrong, hopefully this gets called!
function shutDown($dh, $history_id)
{
    $dh->set_history_status($history_id, AMANewsletterDataHandler::MODULES_NEWSLETTER_HISTORY_STATUS_UNDEFINED);
}
