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

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\Main\ADAError;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\DBRead;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Menu;
use Lynxlab\ADA\Main\Output\GenericXML;
use Lynxlab\ADA\Main\Output\Html;
use Lynxlab\ADA\Main\Output\PDF;
use Lynxlab\ADA\Module\EventDispatcher\ADAEventDispatcher;
use Lynxlab\ADA\Module\EventDispatcher\Events\CoreEvent;
use Lynxlab\ADA\Module\EventDispatcher\Events\MenuEvent;
use Lynxlab\ADA\Module\Impersonate\ImpersonateActions;
use Lynxlab\ADA\Module\Impersonate\Utils;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\todayDateFN;

/**
 * ARE
 *
 */
class ARE
{
    public static function render($layout_dataAr = [], $content_dataAr = [], $renderer = null, $options = [], $menuoptions = [])
    {

        /**
         * @author giorgio 03/apr/2014
         *
         * If query string wants a pdf, let's obey by setting the $renderer
         */
        if (isset($_GET['pdfExport']) && intval($_GET['pdfExport']) === 1) {
            $renderer = ARE_PDF_RENDER;
        }

        if (!isset($id_profile)) {
            $id_profile = null;
        }

        if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
            $event = ADAEventDispatcher::buildEventAndDispatch(
                [
                    'eventClass' => CoreEvent::class,
                    'eventName' => 'PAGEPRERENDER',
                    'eventPrefix' => basename($_SERVER['SCRIPT_FILENAME']),
                ],
                basename($_SERVER['SCRIPT_FILENAME']),
                [
                    'layout_dataAr' => $layout_dataAr,
                    'content_dataAr' => $content_dataAr,
                    'renderer' => $renderer,
                    'options' => $options,
                    'menuoptions' => $menuoptions,
                ]
            );
            foreach ($event->getArguments() as $key => $val) {
                ${$key} = $val;
            }
        }

        switch ($renderer) {
            case ARE_PRINT_RENDER:
                $layoutObj = DBRead::readLayoutFromDB(
                    $id_profile,
                    $layout_dataAr['family'] ?? '',
                    $layout_dataAr['node_type'] ?? '',
                    $layout_dataAr['node_author_id'] ?? '',
                    $layout_dataAr['node_course_id'] ?? '',
                    $layout_dataAr['module_dir'] ?? ''
                );

                // TODO: controlli su layoutObj
                $layout_template = $layoutObj->template;
                $layout_CSS      = $layoutObj->CSS_filename;
                if (!empty($layout_dataAr['CSS_filename']) && is_array($layout_dataAr['CSS_filename'])) {
                    $tmp = explode(';', $layoutObj->CSS_filename);
                    $tmp = array_merge($tmp, $layout_dataAr['CSS_filename']);
                    //$tmp = array_merge($layout_dataAr['JS_filename'],$tmp);
                    $layoutObj->CSS_filename = implode(';', $tmp);
                    $layout_CSS = implode(';', $tmp);
                }
                /*
                 * optional arguments for HTML constructor
                */
                $user_name         = $options['user_name'] ?? '';
                $course_title      = $options['course_title'] ?? '';
                $node_title        = isset($options['node_title']) ? $options['user_name'] : '';
                $meta_keywords     = $options['meta_keywords'] ?? '';
                $author            = $options['author'] ?? '';
                $meta_refresh_time = $options['meta_refresh_time'] ?? '';
                $meta_refresh_url  = $options['meta_refresh_url'] ?? '';
                $onload_func       = $options['onload_func'] ?? '';
                $static_dir        = $options['static_dir'] ?? ROOT_DIR . 'services/media/cache/';

                $html_renderer = new Html(
                    $layout_template,
                    $layout_CSS,
                    $user_name,
                    $course_title,
                    $node_title,
                    $meta_keywords,
                    $author,
                    null,
                    null,
                    $onload_func,
                    $layoutObj
                );


                $html_renderer->fillinTemplateFN($content_dataAr);

                $imgpath = (dirname($layout_template));
                $html_renderer->resetImgSrcFN($imgpath, $layoutObj->family ?? ADA_TEMPLATE_FAMILY);
                $html_renderer->applyStyleFN();

                $html_renderer->outputFN('page');

                break;

            case ARE_XML_RENDER:
                $today = todayDateFN();
                $title = $options['course_title'];
                $portal =  $options['portal'];
                $xml_renderer = new GenericXML($portal, $today, $title);
                $xml_renderer->idNode = $options['id'];
                $xml_renderer->URL = $options['URL'];
                $xml_renderer->fillinFN($content_dataAr);
                $xml_renderer->outputFN('page');
                break;

            case ARE_FILE_RENDER:
                $layoutObj = DBRead::readLayoutFromDB(
                    $id_profile,
                    $layout_dataAr['family'] ?? null,
                    $layout_dataAr['node_type'] ?? null,
                    $layout_dataAr['node_author_id'] ?? null,
                    $layout_dataAr['node_course_id'] ?? null,
                    $layout_dataAr['module_dir'] ?? null
                );
                // TODO: controlli su layoutObj

                $layout_template = $layoutObj->template;
                $layout_CSS      = $layoutObj->CSS_filename;

                /*
                 * optional arguments for HTML constructor
                 */
                $user_name         = $options['user_name'] ?? '';
                $course_title      = $options['course_title'] ?? '';
                $node_title        = isset($options['node_title']) ? $options['user_name'] : '';
                $meta_keywords     = $options['meta_keywords'] ?? '';
                $author            = $options['author'] ?? '';
                $meta_refresh_time = $options['meta_refresh_time'] ?? '';
                $meta_refresh_url  = $options['meta_refresh_url'] ?? '';
                $onload_func       = $options['onload_func'] ?? '';
                $static_dir        = $options['static_dir'] ?? ROOT_DIR . 'services/media/cache/';

                if (!file_exists($static_dir)) {
                    mkdir($static_dir);
                }
                $static_filename = md5($_SERVER['REQUEST_URI'] . $_SERVER['QUERY_STRING']);
                $cached_file = $static_dir . $static_filename;

                $html_renderer = new Html(
                    $layout_template,
                    $layout_CSS,
                    $user_name,
                    $course_title,
                    $node_title,
                    $meta_keywords,
                    $author,
                    $meta_refresh_time,
                    $meta_refresh_url,
                    $onload_func,
                    $layoutObj
                );

                $html_renderer->full_static_filename = $cached_file;
                $html_renderer->fillinTemplateFN($content_dataAr);

                $imgpath = (dirname($layout_template));
                $html_renderer->resetImgSrcFN($imgpath, $layoutObj->family ?? ADA_TEMPLATE_FAMILY);
                $html_renderer->applyStyleFN();

                $html_renderer->outputFN('file');

                break;


            case ARE_HTML_RENDER:
            case ARE_PDF_RENDER:
            default:
                $layoutObj = DBRead::readLayoutFromDB(
                    $id_profile,
                    $layout_dataAr['family'] ?? null,
                    $layout_dataAr['node_type'] ?? null,
                    $layout_dataAr['node_author_id'] ?? null,
                    $layout_dataAr['node_course_id'] ?? null,
                    $layout_dataAr['module_dir'] ?? null
                );
                // TODO: controlli su layoutObj

                $layout_template = $layoutObj->template;
                $layout_CSS      = $layoutObj->CSS_filename;

                /**
                 * @author giorgio 19/ago/2014
                 *
                 * fix javascript inclusion as follows:
                 * - if the PhP has not included JQUERY, include it as first element
                 * - if the PhP has not included SEMANTICUI_JS, include it just after JQUERY
                 * - if the PhP has not included JQUERY_NO_CONFLICT include it as last element
                 *
                 * This way, any PhP can include what it needs and in the right order of inclusion
                 */

                /**
                 * @author giorgio 10/nov/2014
                 *
                 * If the browser is InternetExplorer 8 or less, use smartmenus instead of semantic-ui
                 *
                 * NOTE: $_SESSION['IE-version'] is set by module_init_functions.inc.php
                 */
                $JSToUse = (isset($_SESSION['IE-version']) &&
                    $_SESSION['IE-version'] !== false && $_SESSION['IE-version'] <= 8) ? SMARTMENUS_JS : SEMANTICUI_JS;
                $CSSToUse = (isset($_SESSION['IE-version']) &&
                    $_SESSION['IE-version'] !== false && $_SESSION['IE-version'] <= 8) ? SMARTMENUS_CSS : SEMANTICUI_CSS;

                if (!empty($layout_dataAr['JS_filename']) && is_array($layout_dataAr['JS_filename'])) {
                    // if jquery is not included in the script itself, add it at first position
                    if (!in_array(JQUERY, $layout_dataAr['JS_filename'])) {
                        $layout_dataAr['JS_filename'] = array_merge([JQUERY], $layout_dataAr['JS_filename']);
                    }

                    // if $JSToUse is not included in the script itself, add it just after JQUERY
                    if (!in_array($JSToUse, $layout_dataAr['JS_filename'])) {
                        // find the key for JQUERY
                        $key = array_search(JQUERY, $layout_dataAr['JS_filename']);
                        // add $JSToUse after JQUERY slicing the original array
                        $layout_dataAr['JS_filename'] = array_merge(
                            array_slice($layout_dataAr['JS_filename'], 0, $key + 1),
                            [$JSToUse],
                            array_slice($layout_dataAr['JS_filename'], $key + 1)
                        );
                    }

                    // if jquery noconflict is not included in the script itself, add it at last position
                    if (!in_array(JQUERY_NO_CONFLICT, $layout_dataAr['JS_filename'])) {
                        array_push($layout_dataAr['JS_filename'], JQUERY_NO_CONFLICT);
                    }

                    $tmp = explode(';', $layoutObj->JS_filename);
                    $tmp = array_merge($tmp, $layout_dataAr['JS_filename']);
                    //$tmp = array_merge($layout_dataAr['JS_filename'],$tmp);
                    $layoutObj->JS_filename = implode(';', $tmp);
                } else {
                    // add jquery, semantic and jquery noconflict
                    $layoutObj->JS_filename .= ';' . JQUERY . ';' . $JSToUse . ';' . JQUERY_NO_CONFLICT;
                }

                $tmp = explode(';', $layoutObj->CSS_filename);

                if (!empty($layout_dataAr['CSS_filename']) && is_array($layout_dataAr['CSS_filename'])) {
                    $tmp = array_merge($tmp, $layout_dataAr['CSS_filename']);
                }
                /**
                 * @author giorgio 06/ago/2014
                 * add $CSSToUse last
                 */
                $tmp[] = $CSSToUse;

                /**
                 * @author giorgio 27/jul/2022
                 * add provider custom JQUERY_UI_CSS
                 */
                if (!MULTIPROVIDER && isset($GLOBALS['user_provider']) && in_array(JQUERY_UI_CSS, $tmp)) {
                    $clientJQCSS = ROOT_DIR . '/clients/' . $GLOBALS['user_provider'] . '/layout/' .
                        $layoutObj->family . '/css/' . basename(JQUERY_UI_CSS);
                    if (is_readable($clientJQCSS)) {
                        $tmp[] = $clientJQCSS;
                    }
                }

                //$tmp = array_merge($layout_dataAr['JS_filename'],$tmp);
                $layoutObj->CSS_filename = implode(';', $tmp);
                $layout_CSS = implode(';', $tmp);

                /*
                 * optional arguments for HTML constructor
                 */
                $user_name         = $options['user_name'] ?? '';
                $course_title      = $options['course_title'] ?? '';
                $node_title        = isset($options['node_title']) ? $options['user_name'] : '';
                $meta_keywords     = $options['meta_keywords'] ?? '';
                $author            = $options['author'] ?? '';
                $meta_refresh_time = $options['meta_refresh_time'] ?? '';
                $meta_refresh_url  = $options['meta_refresh_url'] ?? '';
                $onload_func       = $options['onload_func'] ?? '';

                /**
                 * @author giorgio 19/ago/2014
                 *
                 * make menu here
                 */
                if (0 !== strcasecmp('install.php', basename($_SERVER['SCRIPT_FILENAME']))) {
                    // menu property created 'on-the-fly'
                    $layoutObj->menu = new Menu(
                        $layoutObj->module_dir,
                        basename(($_SERVER['SCRIPT_FILENAME'])),
                        $_SESSION['sess_userObj']->getType(),
                        $menuoptions
                    );
                } else {
                    $layoutObj->menu = null;
                }

                if ($renderer == ARE_PDF_RENDER) {
                    $orientation   = $options['orientation'] ?? '';
                    $outputfile    = $options['outputfile'] ?? '';
                    $forcedownload = $options['forcedownload'] ?? false;
                    $returnasstring = $options['returnasstring'] ?? false;

                    // must be called $html_renderer for below code, but it's not :)
                    $html_renderer = new PDF(
                        $layout_template,
                        $layout_CSS,
                        $user_name,
                        $course_title,
                        $node_title,
                        $meta_keywords,
                        $author,
                        $meta_refresh_time,
                        $meta_refresh_url,
                        $onload_func,
                        $layoutObj,
                        $outputfile,
                        $orientation,
                        $forcedownload,
                        $returnasstring
                    );
                } else {
                    $html_renderer = new Html(
                        $layout_template,
                        $layout_CSS,
                        $user_name,
                        $course_title,
                        $node_title,
                        $meta_keywords,
                        $author,
                        $meta_refresh_time,
                        $meta_refresh_url,
                        $onload_func,
                        $layoutObj
                    );
                }

                /**
                 * @author giorgio 25/set/2013
                 * merge the content_dataAr with the one generated by the widgets if it's needed
                 */
                if (!is_null($layoutObj->WIDGET_filename)) {
                    if (!isset($layout_dataAr['widgets'])) {
                        $layout_dataAr['widgets'] = '';
                    }
                    $widgets_dataAr = $html_renderer->fillinWidgetsFN($layoutObj->WIDGET_filename, $layout_dataAr['widgets']);
                    if (!ADAError::isError($widgets_dataAr)) {
                        $content_dataAr = array_merge($content_dataAr, $widgets_dataAr);
                    }
                }

                /**
                 * adamenu must be the first key of $content_dataAr
                 * for the template_field substitution to work inside the menu
                 */
                if (!is_null($layoutObj->menu)) {
                    if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
                        ADAEventDispatcher::buildEventAndDispatch(
                            [
                                'eventClass' => MenuEvent::class,
                                'eventName' => 'PRERENDER',
                            ],
                            $layoutObj->menu,
                            ['userType' => $_SESSION['sess_userObj']->getType()]
                        );
                    }

                    $content_dataAr = ['adamenu' => $layoutObj->menu->getHtml()] + $content_dataAr;

                    if (ModuleLoaderHelper::isLoaded('EVENTDISPATCHER')) {
                        ADAEventDispatcher::buildEventAndDispatch(
                            [
                                'eventClass' => MenuEvent::class,
                                'eventName' => 'POSTRENDER',
                            ],
                            $layoutObj->menu,
                            ['userType' => $_SESSION['sess_userObj']->getType()]
                        );
                    }
                    $content_dataAr['isVertical'] = ($layoutObj->menu->isVertical()) ? ' vertical' : '';
                }

                if (isset($_SESSION['sess_userObj'])) {
                    if (!array_key_exists('user_avatar', $content_dataAr)) {
                        $content_dataAr['user_avatar'] = CDOMElement::create('img', 'class,img_user_avatr,src:' . $_SESSION['sess_userObj']->getAvatar())->getHtml();
                    }

                    if (!array_key_exists('user_uname', $content_dataAr)) {
                        $content_dataAr['user_uname'] = $_SESSION['sess_userObj']->getUserName();
                    }

                    if (!array_key_exists('last_visit', $content_dataAr)) {
                        $tmpla = trim(AMADataHandler::tsToDate($_SESSION['sess_userObj']->getLastAccessFN(null, "UT", null)) ?? '');
                        if (strlen($tmpla) > 0) {
                            $content_dataAr['last_visit'] = translateFN('ultimo accesso') . ': ' . $tmpla;
                        }
                    } else {
                        $content_dataAr['last_visit'] = translateFN('ultimo accesso') . ': ' . $content_dataAr['last_visit'];
                    }

                    if (!array_key_exists('user_level', $content_dataAr)) {
                        if (isset($GLOBALS['user_lever']) && strlen($GLOBALS['user_level']) > 0) {
                            $content_dataAr['user_level'] = translateFN('livello') . ':' . $GLOBALS['user_level'];
                        }
                    } else {
                        $content_dataAr['user_level'] = translateFN('livello') . ':' . $content_dataAr['user_level'];
                    }

                    if (
                        ModuleLoaderHelper::isLoaded('IMPERSONATE') &&
                        ImpersonateActions::canDo(ImpersonateActions::IMPERSONATE) &&
                        !array_key_exists('impersonatelink', $content_dataAr)
                    ) {
                        $content_dataAr['impersonatelink'] = Utils::generateMenu()->getHtml();
                    }
                }

                $html_renderer->fillinTemplateFN($content_dataAr);

                $imgpath = (dirname($layout_template));
                // $html_renderer->resetImgSrcFN($imgpath,$template_family);
                $html_renderer->resetImgSrcFN($imgpath, $layoutObj->family);
                $html_renderer->applyStyleFN();

                if (property_exists($html_renderer, 'returnasstring') && $html_renderer->returnasstring === true) {
                    return $html_renderer->outputFN(($renderer == ARE_PDF_RENDER) ? 'pdf' : 'page');
                } else {
                    $html_renderer->outputFN(($renderer == ARE_PDF_RENDER) ? 'pdf' : 'page');
                }
                break;
        }
    }
}
