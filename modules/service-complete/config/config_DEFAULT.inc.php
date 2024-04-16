<?php

/**
 * SERVICE-COMPLETE MODULE.
 *
 * @package        service-complete module
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2013, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           service-complete
 * @version        0.1
 */

try {
    if (!@include_once(MODULES_SERVICECOMPLETE_PATH . '/vendor/autoload.php')) {
        // @ - to suppress warnings,
        throw new Exception(
            json_encode([
                'header' => 'Service-complete module will not work because autoload file cannot be found!',
                'message' => 'Please run <code>composer install</code> in the module subdir',
            ])
        );
    } else {
        // MODULE'S OWN DEFINES HERE
        // phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols
        /**
         * the num of rulesets placed in logical OR
         * between the. i.e. the number of cols in
         * the UI table when editing the rule
         */
        define('NUM_RULES_SET', 3);
        /**
         * true to output debug info in the ADA log dir
         */
        define('MODULES_SERVICECOMPLETE_LOG', false);
        define('MODULES_SERVICECOMPLETE_LOGDIR', ROOT_DIR . '/log/service-complete/');

        $GLOBALS['completeClasses'][]  = 'completeConditionTime';
        $GLOBALS['completeClasses'][]  = 'completeConditionLevel';
        $GLOBALS['completeClasses'][]  = 'completeConditionNodePercentage';
        if (defined('MODULES_TEST') && MODULES_TEST) {
            // hide this completeCondition if no MODULES_TEST
            $GLOBALS['completeClasses'][]  = 'completeConditionAnsweredSurvey';
        }
        // phpcs:enable
        return true;
    }
} catch (Exception $e) {
    $text = json_decode($e->getMessage(), true);
    // populating $_GET['message'] is a dirty hack to force the error message to appear in the home page at least
    if (!isset($_GET['message'])) {
        $_GET['message'] = '';
    }
    $_GET['message'] .= '<div class="ui icon error message"><i class="ban circle icon"></i><div class="content">';
    if (array_key_exists('header', $text) && strlen($text['header']) > 0) {
        $_GET['message'] .= '<div class="header">' . $text['header'] . '</div>';
    }
    if (array_key_exists('message', $text) && strlen($text['message']) > 0) {
        $_GET['message'] .= '<p>' . $text['message'] . '</p>';
    }
    $_GET['message'] .= '</div></div>';
}
return false;
