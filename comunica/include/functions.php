<?php

namespace Lynxlab\ADA\Comunica\Functions;

/**
 * function exitWith_JSON_Error
 *
 * Used to exit from script execution returning a json error object.
 * @param string $error_msg - the text string to display
 * @return void
 * @author vito
 */
function exitWithJSONError($error_msg, $error_code = 1)
{
    $json_string = '{"error":' . $error_code . ',"message":"' . $error_msg . '"}';
    print $json_string;
    exit();
}
