<?php

namespace Lynxlab\ADA\Switcher\Functions;

function formatBytes($bytes, $precision = 2)
{
    if ($bytes > 1024 ** 3) {
        return round($bytes / 1024 ** 3, $precision) . "GB";
    } elseif ($bytes > 1024 ** 2) {
        return round($bytes / 1024 ** 2, $precision) . "MB";
    } elseif ($bytes > 1024) {
        return round($bytes / 1024, $precision) . "KB";
    } else {
        return ($bytes) . "B";
    }
}
