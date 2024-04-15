<?php

use Lynxlab\ADA\Main\Node\Node;

use Lynxlab\ADA\Main\Node\Media;

use Lynxlab\ADA\Main\Node\EmptyMedia;

// Trigger: ClassWithNameSpace. The class EmptyMedia was declared with namespace Lynxlab\ADA\Main\Node. //

/**
 * Node, Media, Link classes
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        node_classes
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\Node;

class EmptyMedia extends Media
{
    public function __construct()
    {
        $this->full = false;
    }
}
