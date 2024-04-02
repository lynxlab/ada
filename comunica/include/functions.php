<?php

namespace Lynxlab\ADA\Comunica\Functions;

use function Lynxlab\ADA\Main\Utilities\ts2tmFN;

/**
 * function exitWith_JSON_Error
 *
 * Used to exit from script execution returning a json error object.
 * @param string $error_msg - the text string to display
 * @return void
 * @author vito
 */
function exitWith_JSON_Error($error_msg, $error_code = 1)
{
    $json_string = '{"error":' . $error_code . ',"message":"' . $error_msg . '"}';
    print $json_string;
    exit();
}
