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

use Exception;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMAError;
use Lynxlab\ADA\Main\ArrayToXML\ArrayToXML;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Output\GenericHtml;
use Lynxlab\ADA\Main\User\ADAGenericUser;
use Lynxlab\ADA\Widgets\Widget;

class Html extends GenericHtml
{
    //vars:
    public $template;
    public $CSS_filename;
    public $JS_filename;
    public $htmlheader;
    public $htmlbody;
    public $htmlfooter;
    public $replace_field_code;
    public $replace_microtemplate_field_code;
    public $module_dir;
    public $family;
    //functions:

    public function __construct($template, $CSS_filename, $user_name, $course_title, $node_title = "", $meta_keywords = "", $author = "", $meta_refresh_time = "", $meta_refresh_url = "", $onload_func = "", $layoutObj = null)
    {

        $HTTP_USER_AGENT =   $_SERVER['HTTP_USER_AGENT'];
        $root_dir =   $GLOBALS['root_dir'];
        $http_root_dir =   $GLOBALS['http_root_dir'];
        $keywords = "ADA, Lynx, ";
        $keywords .= ADA_METAKEYWORDS; // from config file
        $description = ADA_METADESCRIPTION; // from config file
        //$layoutObj = $GLOBALS['layoutObj'];
        if (!is_Object($layoutObj)) { // we use function parameters
            $this->template = $template;
            $this->CSS_filename = $CSS_filename;
            $this->JS_filename = $JS_filename ?? "";
            $this->family = "";
            $this->module_dir = "";
        } else { // we use data from LayOut object
            $this->template = $layoutObj->template;
            $this->CSS_filename = $layoutObj->CSS_filename;
            $this->family = $layoutObj->family;
            $this->JS_filename = $layoutObj->JS_filename;
            $this->module_dir = $layoutObj->module_dir;
            $this->external_module = $layoutObj->external_module;
        }
        $template_name = basename($template);
        $widget_filename = (!is_null($layoutObj)) ? basename($layoutObj->WIDGET_filename) : '';
        $family_name = $this->family;
        $module_dir =  $this->module_dir;
        $this->htmlheader = "
<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . ADA_CHARSET . "\">";

        //this is useful for all those html pages that need a refresh time
        // if the refresh time & url are set, this tag is added into the header part of the html page
        if (!empty($meta_refresh_time)) {
            $this->htmlheader .= "
            <meta http-equiv=\"refresh\" content=\"$meta_refresh_time; url=$meta_refresh_url\">";
        }

        $this->htmlheader .= "
<meta name=\"powered_by\" content=\"ADA v." . ADA_VERSION . "\">
<meta name=\"powered_by\" content=\"PHP v." . phpversion() . "\">
        <meta name=\"address\" content=\"$http_root_dir\">
        <meta name=\"author\" content=\"$author\">
        <meta name=\"template\" content=\"$template_name\">
        <meta name=\"family\" content=\"$family_name\">
        <meta name=\"ADA-module\" content=\"$module_dir\">
        <meta name=\"widgets\" content=\"$widget_filename\">";
        if (isset($layoutObj->menu)) {
            $this->htmlheader .= "
        <meta name=\"menu\" content=\"" . $layoutObj->menu->getId() . "\"";
            if (!is_null($layoutObj->menu->getLinkedFromId())) {
                $this->htmlheader .= " linked-from=\"" . $layoutObj->menu->getLinkedFromId() . "\"";
            }
            $this->htmlheader .= ">";
        }

        $this->htmlheader .= "
        <meta name=\"class\" content=\"HTML\">
        <meta name=\"outputClasses\" content=\"NEW\">
        <meta name=\"description\" content=\"$description\">
        <meta name=\"keywords\" content=\"$keywords,$meta_keywords\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
        <!-- Stile -->
        <!-- Javascript -->\n";

        if (isset($course_title) && !empty($course_title) && isset($node_title) && !empty($node_title)) {
            $this->htmlheader .= "<title>" . PORTAL_NAME . " > $course_title > $node_title</title>\n\n";
        } else {
            $this->htmlheader .= "<title>" . PORTAL_NAME . "</title>\n\n";
        }

        $this->replace_field_code = $GLOBALS['replace_field_code'];
        $this->replace_microtemplate_field_code = $GLOBALS['replace_microtemplate_field_code'];
        $this->htmlheader .= "</head>\n";

        $this->htmlbody = '<body class=\'ada-' .
            str_replace(' ', '-', strtolower(trim(ADAGenericUser::convertUserTypeFN($_SESSION['sess_userObj']->getType(), false)))) .
            '\'';
        if (isset($onload_func) && !empty($onload_func)) {
            $this->htmlbody .= " onload=\"$onload_func\"";
        }
        $this->htmlbody .= ">\n";
        $this->htmlfooter = "</body>\n</html>";
    }

    /**
     * @author giorgio 25/set/2013
     *
     * renders the widgets of the page as described in the passed xml config file
     *
     * @param string $widgetsConfFilename xml configuration filename for the widgets
     * @param arrayn $optionsArray array of option to be passed to the widget loader
     *
     * @return array|AMAError
     */
    public function fillinWidgetsFN($widgetsConfFilename = '', $optionsArray = [])
    {

        if (is_file($widgetsConfFilename)) {
            try {
                $widgetAr = ArrayToXML::toArray(file_get_contents($widgetsConfFilename));
            } catch (Exception $e) {
                /*
                   * see config_errors.inc.php line 167 and following.
                   * depending on the erorr phase / severity something will happen...
                   */
                return new ADAError(null, 'Widget configuration XML is not valid', __METHOD__, ADA_ERROR_ID_XML_PARSING);
            }
        }

        /**
         * @author giorgio 25/feb/2014
         * ArrayToXML::toArray does not return an array of array if there's
         * only one widget in the xml. Let's build an array of array even in this case.
         */
        if (!is_array(reset($widgetAr['widget']))) {
            $widgets = [$widgetAr['widget']];
        } else {
            $widgets = $widgetAr['widget'];
        }
        $retArray = [];

        foreach ($widgets as $widget) {
            // if widget is not active skip the current iteration
            if (
                (isset($widget['active']) && intval($widget['active']) === 0) ||
                (isset($widget[$widget['id']]) && intval($widget[$widget['id']['isActive']]) === 0)
            ) {
                continue;
            }
            $wobj = new Widget($widget);
            /**
             * if there are some params passed in, tell it to the widget
             */
            if (isset($optionsArray[$wobj->templateField]) && !empty($optionsArray[$wobj->templateField])) {
                foreach ($optionsArray[$wobj->templateField] as $name => $value) {
                    $wobj->setParam($name, $value);
                }
            }
            $retArray[$wobj->templateField] = $wobj->getWidget();
        }
        return $retArray;
    }
}
