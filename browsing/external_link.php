<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\HtmlLibrary\UserModuleHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;

use function Lynxlab\ADA\Browsing\Functions\findInClientDir;
use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/../config_path.inc.php';
/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_STUDENT, AMA_TYPE_VISITOR, AMA_TYPE_AUTHOR, AMA_TYPE_TUTOR, AMA_TYPE_SWITCHER, AMA_TYPE_ADMIN];
/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_STUDENT         => ['layout'],
  AMA_TYPE_VISITOR      => ['layout'],
  AMA_TYPE_AUTHOR       => ['layout'],
  AMA_TYPE_TUTOR => ['layout'],
  AMA_TYPE_ADMIN => ['layout'],
  AMA_TYPE_SWITCHER => ['layout'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';

/**
 * This will at least import in the current symbol table the following vars.
 * For a complete list, please var_dump the array returned by the init method.
 *
 * @var boolean $reg_enabled
 * @var boolean $log_enabled
 * @var boolean $mod_enabled
 * @var boolean $com_enabled
 * @var string $user_level
 * @var string $user_score
 * @var string $user_name
 * @var string $user_type
 * @var string $user_status
 * @var string $media_path
 * @var string $template_family
 * @var string $status
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_messages
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_agenda
 * @var \Lynxlab\ADA\CORE\html4\CElement $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

$self =  whoami();

$external_link_id = isset($_GET['id']) ? DataValidator::isUinteger($_GET['id']) : false;
$filename = isset($_GET['file']) ? DataValidator::validateLocalFilename($_GET['file']) : false;

//$url = DataValidator::validateUrl($_GET['url']);
$url = $_GET['url'] ?? null;

if ($external_link_id != false) {
    $external_resourceAr = $dh->getRisorsaEsternaInfo($external_link_id);
    if (AMADataHandler::isError($external_resourceAr)) {
        $data = '';
    } elseif ($external_resourceAr['tipo'] != _LINK) {
        $data = '';
    } else {
        $address = $external_resourceAr['nome_file'];
        $data = UserModuleHtmlLib::getExternalLinkNavigationFrame($address)->getHtml();
    }
} elseif ($filename != false) {
    if (basename($filename) == $filename) {
        $address = '';

        /**
         * @author giorgio 07/mag/2015
         *
         * look for possible translations of passed file
         * e.g.: file=privacy.html will look for privacy_en.html, privacy_it.html...
         *       file=guest_it.html will look for
         */

        // $foundFile = false;
        // if (!MULTIPROVIDER && isset ($GLOBALS['user_provider'])) {
        //   $foundFile = is_readable(ROOT_DIR . '/clients/'.$GLOBALS['user_provider'].'/docs/' . $filename);
        // }

        // if (!$foundFile) $foundFile = is_readable(ROOT_DIR . '/docs/' . $filename);
        $foundFile = findInClientDir($filename);
        /**
         * NOTE: it's safe to assume that $filename has a dot, the
         * validateLocalFilename would have returned false if it had not
         */
        $exploded_filename = explode('.', $filename);

        if ($foundFile === false) {
            $extension = '.' . end($exploded_filename);
            $underscoreDelimited = explode('_', reset($exploded_filename));
            /**
             * If the last piece of $underscoreDelimited has length 2
             * it's assumed to be lang part of the file name, remove it
             */
            if (strlen(end($underscoreDelimited)) === 2) {
                unset($underscoreDelimited[count($underscoreDelimited) - 1]);
            }

            /*
             * attempt to find the file in the user actual language
             */
            if (is_object($userObj)) {
                $userActualLangId = $userObj->getLanguage();
                if ($userActualLangId != false) {
                    $userActualLang = Translator::getLanguageInfoForLanguageId($userActualLangId);
                    $userActualLangCod = $userActualLang['codice_lingua'];
                }
                if (isset($userActualLangCod)) {
                    $filename = implode('_', $underscoreDelimited) . '_' . $userActualLangCod . $extension;
                    // $foundFile = is_file(ROOT_DIR . '/docs/' . $filename) && is_readable(ROOT_DIR . '/docs/' . $filename);
                    $foundFile = findInClientDir($filename);
                }
            }

            /**
             * build the array of candidate languages
             */
            $tryLangs = [$login_page_language_code = Translator::negotiateLoginPageLanguage()];
            if (!in_array(ADA_LOGIN_PAGE_DEFAULT_LANGUAGE, $tryLangs)) {
                $tryLangs[] = ADA_LOGIN_PAGE_DEFAULT_LANGUAGE;
            }
            /**
             * loop the array until a file has been found
             * or end of array has been reached
             */
            for ($currentLang = reset($tryLangs); ((current($tryLangs) !== false) && $foundFile === false); $currentLang = next($tryLangs)) {
                $filename = implode('_', $underscoreDelimited) . '_' . $currentLang . $extension;
                $foundFile = findInClientDir($filename);
                // $foundFile = is_file(ROOT_DIR . '/docs/' . $filename) && is_readable(ROOT_DIR . '/docs/' . $filename);
            }
        }

        if ($foundFile !== false) {
            // $http_path_to_file = HTTP_ROOT_DIR . '/docs/' . $filename;
            $http_path_to_file = str_replace(ROOT_DIR, HTTP_ROOT_DIR, $foundFile);
            $pdf_filename = $exploded_filename[0] . '.pdf';
            $foundPdf = findInClientDir($pdf_filename);
            if ($foundPdf !== false) {
                $href = str_replace(ROOT_DIR, HTTP_ROOT_DIR, $foundPdf);
                // $href = HTTP_ROOT_DIR . '/docs/' . $pdf_filename;
                $pdf_link = CDOMElement::create('a', "href: $href");
                $pdf_link->addChild(new CText(translateFN('Download pdf version')));
            } else {
                $pdf_link = new CText('');
            }
            $data = $pdf_link->getHtml()
            . UserModuleHtmlLib::getExternalLinkNavigationFrame($http_path_to_file)->getHtml();
        } else {
            $data = translateFN('The required resource is currently not available.')
            . '<br />'
            . translateFN('Please try again later.');
        }
    } else {
        $data = translateFN('The required resource is not available.');
    }
} elseif ($url != false) {
    $data = UserModuleHtmlLib::getExternalLinkNavigationFrame($url)->getHtml();
} else {
    $data = '';
}

$title = translateFN('ADA - External link navigation');

$content_dataAr = [
  'data'      => $data,
  'address'   => $address,
  'status'    => $status,
  'user_name' => $user_name,
  'user_type' => $user_type,
];

ARE::render($layout_dataAr, $content_dataAr);
