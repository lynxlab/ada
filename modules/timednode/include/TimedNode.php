<?php

/**
 * @package     timednode module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Timednode;

class TimedNode
{
    /**
     * Magic words that if found in the keywords will trigger the timed node.
     *
     * @var array
     */
    private static $magicWords = [
        'durata',
        'duration',
        't',
        'time',
    ];

    /**
     * checks if a keyword is one of the magic words
     *
     * @param string $el
     *   the keyword to check
     * @return boolean
     *   true if it's a magic word
     */
    public static function hasMagicWord($el): bool
    {
        $el = str_replace(' ', '', $el);
        foreach (static::$magicWords as $magicWord) {
            if (str_starts_with($el, $magicWord . '=')) {
                return true;
            }
        }
        return false;
    }

    /**
     * calc the time a user has spent in a node
     *
     * @param array $data
     *   array used to get the time, must have keys (prefixed by $dataPrefix):
     *   - id_user
     *   - id_course_instance
     *   - id_node
     * @param string $dataPrefix
     *   prefix for the keys of the data array ('sess_' if using session)
     * @return integer
     *   time the user has spent in node, in seconds
     */
    public static function calcTimeSpentInNode($data = [], $dataPrefix = 'sess_'): int
    {
        /**
         * @var \Lynxlab\ADA\Main\AMA\AMATesterDataHandler $dh
         */
        $dh = $GLOBALS['dh'];

        $history = array_filter(
            $dh?->getLastVisitedNodesInPeriod($data[$dataPrefix . 'id_user'], $data[$dataPrefix . 'id_course_instance'], 0) ?? [],
            fn ($el) => $el['id_nodo'] == $data[$dataPrefix . 'id_node'] ?? -1
        );
        return array_sum(array_map(fn ($el) => ($el['data_uscita'] ?? 0) - ($el['data_visita'] ?? 0), $history));
    }

    /**
     * If a positive timestamp is passed, round it to nearest upper minute.
     *
     * @param int $timestamp
     * @return int rounded timestamp or passed timestamp
     */
    public static function ceilMinute($timestamp)
    {
        return ($timestamp > 0 ? ceil($timestamp / 60) * 60 : $timestamp);
    }

    public static function timeFromKeyWords($keywords)
    {
        $magic = static::getMagicWord($keywords);
        if (!empty($magic)) {
            $timeArr = static::extractTime($magic);
            return sprintf("%02d:%02d:%02d", ...$timeArr);
        }
        return null;
    }

    /**
     * Given a string like '<magicword>=h:m:s'
     * extracts an array like: [ h, m,s ]
     *
     * @param string $keyword
     * @return array
     */
    public static function extractTime($keyword)
    {
        [$keyword, $time] = explode('=', $keyword);
        return array_map('trim', explode(':', $time));
    }

    /**
     * Given the node keywords, gets the magic word
     *
     * @param string $keywords
     * @return string|null
     *   String is like 't=h:m:s'
     */
    public static function getMagicWord($keywords)
    {
        $retval = null;
        $found = array_filter(
            array_map(
                'trim',
                explode(
                    ',',
                    trim($keywords ?? '')
                )
            ),
            fn ($el) => static::hasMagicWord($el)
        );
        if (is_array($found) && count($found) == 1) {
            $retval = reset($found);
        }
        return $retval;
    }
}
