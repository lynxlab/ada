<?php

/**
 * @package     encrypt-fields module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Encryptfields;

class EchoHelper
{
    public const RESET = "\033[0m";
    public const RED = "\033[31m";
    public const GREEN = "\033[32m";
    public const YELLOW = "\033[33m";

    public static $useColors = true;

    private static function output($color, $str, $withEOL = false)
    {
        if (self::$useColors) {
            $str =  $color . $str . self::RESET;
        }
        if ($withEOL) {
            $str .= PHP_EOL;
        }
        return $str;
    }

    public static function warn($str = '', $withEOL = false)
    {
        return self::output(self::YELLOW, $str, $withEOL);
    }

    public static function ok($str = '', $withEOL = false)
    {
        return self::output(self::GREEN, $str, $withEOL);
    }

    public static function error($str = '', $withEOL = false)
    {
        return self::output(self::RED, $str, $withEOL);
    }
}
