<?php

namespace Lynxlab\ADA\Browsing\Functions;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

function findInClientDir($filename)
{
    $foundFile = false;
    $fullPath = ROOT_DIR . '/docs/' . $filename;
    if (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) {
        $clientPath = ROOT_DIR . '/clients/' . $GLOBALS['user_provider'] . '/docs/' . $filename;
        $foundFile = is_readable($clientPath);
        if ($foundFile) {
            return $clientPath;
        }
    }
    if (!$foundFile) {
        $foundFile = is_readable($fullPath);
    }
    if ($foundFile) {
        return $fullPath;
    }
    return false;
}

function menuDetailsFN()
{
    $menu_history = translateFN("Nodi visitati recentemente:") . "<br>\n";
    $menu_history .= "<a href=\"history_details.php?period=1\">" . translateFN("1 giorno") . "</a><br>\n";
    $menu_history .= "<a href=\"history_details.php?period=5\">" . translateFN("5 giorni") . "</a><br>\n";
    $menu_history .= "<a href=\"history_details.php?period=15\">" . translateFN("15 giorni") . "</a><br>\n";
    $menu_history .= "<a href=\"history_details.php?period=30\">" . translateFN("30 giorni") . "</a><br>\n";
    $menu_history .= "<a href=\"history_details.php?period=all\">" . translateFN("tutti") . "</a><br>\n";
    $menu_history .= "<br>";
    return $menu_history;
}
