<?php

use Lynxlab\ADA\Services\NodeEditing\Utilities;

use Lynxlab\ADA\Comunica\Event\ADAEventProposal;

// Trigger: ClassWithNameSpace. The class ADAEventProposal was declared with namespace Lynxlab\ADA\Comunica\Event. //

namespace Lynxlab\ADA\Comunica\Event;

use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Utilities\getTimezoneOffset;
use function Lynxlab\ADA\Main\Utilities\sumDateTimeFN;

/**
 *
 *
 * @package   comunica
 * @author
 * @copyright Copyright (c) 2009, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version   0.1
 */

class ADAEventProposal
{
    /**
     * Generates an event token string
     *
     * @param  int $tutoredUserId
     * @param  int $tutorId
     * @param  int $courseInstanceId
     * @return string
     */
    public static function generateEventToken($id_tutored_user, $id_tutor, $id_course_instance)
    {

        $event_token = $id_tutored_user    . '_'
            . $id_tutor           . '_'
            . $id_course_instance . '_'
            . time();

        return $event_token;
    }

    /**
     * Returns the course instance id from a given event token
     *
     * @param  string $event_token
     * @return int
     */
    public static function extractCourseInstanceIdFromThisToken($event_token)
    {

        /*
         * first match: tutored user id
         * second match: tutor id
         * third match: course instance id
         * fourth match: timestamp
         */
        $pattern = '/(?:[1-9][0-9]*)_(?:[1-9][0-9]*)_([1-9][0-9]*)_(?:[1-9][0-9]+)/';
        $matches = [];
        if (preg_match($pattern, $event_token, $matches) == 1) {
            return $matches[1];
        }
        return false;
    }

    /**
     * Inspects a string to see if it has an event token prefixed.
     *
     * @param  string $string
     * @return string the event token if found, an empty string otherwise
     */
    public static function extractEventToken($string)
    {
        $pattern = '/^([0-9_]+)#/';
        $matches = [];
        if (preg_match($pattern, $string, $matches) == 1) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Removes the event token from a given string if it is found as a string prefix.
     *
     * @param string $string
     * @return string
     */
    public static function removeEventToken($string)
    {
        $pattern = '/^[0-9_]+#/';
        $clean_string = preg_replace($pattern, '', $string, 1);
        return $clean_string;
    }

    /**
     * Generates the event proposal message content
     *
     * @param  array  $datetimesAr an associative array with dates as keys and times as values
     * @param  int    $id_course_instance
     * @param  string $notes
     * @return string
     */
    public static function generateEventProposalMessageContent($datetimesAr = [], $id_course_instance = null, $notes = null)
    {

        if (is_null($id_course_instance) && is_null($notes)) {
            return '';
        }
        $message = '<proposal>';
        foreach ($datetimesAr as $datetimeAr) {
            $date = $datetimeAr['date'];
            $time = $datetimeAr['time'];

            $message .= "<event><date>$date</date><time>$time</time></event>";
        }
        $message .= "<notes>$notes</notes>"
            . "<id_course_instance>$id_course_instance</id_course_instance>"
            . '</proposal>';

        return $message;
    }

    /**
     * Returns an array containing the dates and times proposed for an event
     *
     * @param string $string
     * @return array on success, FALSE on failure
     */
    public static function extractDateTimesFromEventProposalText($string)
    {
        $pattern = '/<date>([0-9]{2}\/[0-9]{2}\/[0-9]{4})<\/date>(?:\s)*<time>([0-9]{2}:[0-9]{2})<\/time>/';
        $matches = [];
        if (preg_match_all($pattern, $string, $matches)) {
            // costruire array datetimesAr e restituire
            $datetimesAr = [];
            $dates = $matches[1];
            $times = $matches[2];
            $howManyDates = count($dates);
            for ($i = 0; $i < $howManyDates; $i++) {
                $datetimesAr[] = [
                    'date' => $dates[$i],
                    'time' => $times[$i],
                ];
            }

            return $datetimesAr;
        }
        return false;
    }

    /**
     * Returns the practitioner's notes
     * @param string $string
     * @return string
     */
    public static function extractNotesFromEventProposalText($string)
    {
        $pattern = '/<notes>(.*)<\/notes>/';
        $matches = [];
        if (preg_match($pattern, $string, $matches) == 1) {
            return $matches[1];
        }
        return '';
    }

    /**
     * Returns the course instance id
     * @param string $string
     * @return FALSE if not found, an int > 0 otherwise
     */
    public static function extractIdCourseInstanceFromEventProposalText($string)
    {
        $pattern = '/<id_course_instance>(.*)<\/id_course_instance>/';
        $matches = [];
        if (preg_match($pattern, $string, $matches) == 1) {
            return $matches[1];
        }
        return false;
    }
    /**
     * Checks if an event can be proposed in the given date and time
     *
     * @param string $date
     * @param string $time
     * @return TRUE on success, a ADA error code on failure
     */

    public static function canProposeThisDateTime(ADALoggableUser $userObj, $date, $time, $tester = null)
    {

        $date = DataValidator::validateDateFormat($date);
        if ($date === false) {
            return ADA_EVENT_PROPOSAL_ERROR_DATE_FORMAT;
        } else {
            $current_timestamp = time();

            /**
             * @var timezone management
             */
            $offset = 0;
            if ($tester === null) {
                $tester_TimeZone = SERVER_TIMEZONE;
            } else {
                $tester_TimeZone = MultiPort::getTesterTimeZone($tester);
                $offset = getTimezoneOffset($tester_TimeZone, SERVER_TIMEZONE);
            }

            $timestamp_time_zone = sumDateTimeFN([$date, "$time:00"]);
            $timestamp = $timestamp_time_zone - $offset;

            if ($current_timestamp >= $timestamp) {
                return ADA_EVENT_PROPOSAL_ERROR_DATE_IN_THE_PAST;
            }
            if (MultiPort::hasThisUserAnAppointmentInThisDate($userObj, $timestamp)) {
                return ADA_EVENT_PROPOSAL_ERROR_DATE_IN_USE;
            }
        }

        return true;
    }

    /**
     * Returns a new string containing the event token
     * @param string $event_token
     * @param string $string
     * @return string
     */
    public static function addEventToken($event_token, $string)
    {
        $pattern = '/(?:[1-9][0-9]*)_(?:[1-9][0-9]*)_(?:[1-9][0-9]*)_(?:[1-9][0-9]+)/';
        $matches = [];
        if (preg_match($pattern, $event_token, $matches) == 1) {
            return $event_token . '#' . $string;
        }

        return $string;
    }
}
