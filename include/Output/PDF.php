<?php

/**
 * NEW Output classes
 *
 *
 * PHP version >= 5.0
 *
 * @package     ARE
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        output_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Output;

/**
 * Classe generica di output PDF
 *
 * @author giorgio
 *
 */
class PDF extends Html
{
    public $outputfile;
    public $orientation;
    public $forcedownload;
    public $returnasstring;

    public function __construct(
        $template,
        $CSS_filename,
        $user_name,
        $course_title,
        $node_title = "",
        $meta_keywords = "",
        $author = "",
        $meta_refresh_time = "",
        $meta_refresh_url = "",
        $onload_func = "",
        $layoutObj = null,
        $outputfile = "ada",
        $orientation = "landscape",
        $forcedownload = false,
        $returnasstring = false
    ) {
        $this->outputfile = $outputfile;
        $this->orientation = $orientation;
        $this->forcedownload = $forcedownload;
        $this->returnasstring = $returnasstring;

        parent::__construct($template, $CSS_filename, $user_name, $course_title, $node_title, $meta_keywords, $author, $meta_refresh_time, $meta_refresh_url, $onload_func, $layoutObj);
    }
}
