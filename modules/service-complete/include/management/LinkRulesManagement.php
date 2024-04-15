<?php

use Lynxlab\ADA\Module\Servicecomplete\LinkRulesManagement;

use Lynxlab\ADA\Module\Servicecomplete\FormLinkRules;

use Lynxlab\ADA\Main\AMA\AMADB;

// Trigger: ClassWithNameSpace. The class LinkRulesManagement was declared with namespace Lynxlab\ADA\Module\Servicecomplete. //

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

namespace Lynxlab\ADA\Module\Servicecomplete;

/**
 * management class for completeRules form
 *
 * @author giorgio
 */
class LinkRulesManagement
{
    /**
     * the array of the courses linked
     * to the passed conditionset id
     *
     * @var array
     */
    private $coursesList;

    /**
     * LinkRulesManagement constructor.
     */
    public function __construct()
    {
        $this->coursesList = [];
    }

    /**
     * generates the form instance and returns the html
     *
     * @return the usual array with: html, path and status keys
     */
    public function form($data = null)
    {
        $dh = $GLOBALS['dh'];

        // load the courses list to be passed to the form
        $coursesAr = $dh->findCoursesList(['nome','titolo']);
        if (!AMADB::isError($coursesAr)) {
            foreach ($coursesAr as $courseEl) {
                $this->coursesList[$courseEl[0]] = $courseEl[1] . ' - ' . $courseEl[2];
            }
        }

        $form = new FormLinkRules($data, $this->coursesList);

        /**
         * path and status are not used for time being (03/dic/2013)
         */
        return [
                'html'   => $form->getHtml(),
                'path'   => '',
                'status' => '',
        ];
    }
}
