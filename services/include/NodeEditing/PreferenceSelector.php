<?php

/**
 * PreferenceSelector class.
 *
 * @package
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Services\NodeEditing;

class PreferenceSelector
{
    public static function getPreferences($user_type, $node_type, $operation_on_node, $preferences_array = [])
    {
        if (isset($preferences_array[$user_type][$node_type][$operation_on_node])) {
            return $preferences_array[$user_type][$node_type][$operation_on_node];
        } else {
            return null;
        }
    }
}
