<?php

use Lynxlab\ADA\Admin\AdminHelper;
use Lynxlab\ADA\Main\HtmlLibrary\AdminModuleHtmlLib;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Main\Translator;

use function Lynxlab\ADA\Main\Utilities\readDir;
use function Lynxlab\ADA\Main\Utilities\whoami;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['layout'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_ADMIN];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
    AMA_TYPE_SWITCHER => ['layout', 'course'],
];

require_once ROOT_DIR . '/include/module_init.inc.php';
$self = whoami();  // = admin!

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
 * @var object $user_messages
 * @var object $user_agenda
 * @var array $user_events
 * @var array $layout_dataAr
 * @var \Lynxlab\ADA\Main\History\History $user_history
 * @var \Lynxlab\ADA\Main\Course\Course $courseObj
 * @var \Lynxlab\ADA\Main\Course\CourseInstance $courseInstanceObj
 * @var \Lynxlab\ADA\Main\User\ADAPractitioner $tutorObj
 * @var \Lynxlab\ADA\Main\Node\Node $nodeObj
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
AdminHelper::init($neededObjAr);

/*
 * YOUR CODE HERE
 */
$options = '';
$languages = Translator::getSupportedLanguages();
$files_news = readDir(ROOT_DIR . '/docs/news', 'txt');
//print_r($files_news);

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $newsfile =  $_POST['file_edit'];
    $n = fopen($newsfile, 'w');
    $res = fwrite($n, stripslashes($_POST['news']));
    $res = fclose($n);
}

$codeLang = $_GET['codeLang'];
switch ($op) {
    case 'edit':
        $newsmsg = [];
        $fileToOpen = ROOT_DIR . '/docs/news/news_' . $codeLang . '.txt';
        $newsfile = $fileToOpen;
        if ($fid = @fopen($newsfile, 'r')) {
            while (!feof($fid)) {
                $newsmsg['news'] .= fread($fid, 4096);
            }
            fclose($fid);
        } else {
            $newsmsg['news'] = translateFN("Non ci sono news");
        }

        $data = AdminModuleHtmlLib::getEditNewsForm($newsmsg, $fileToOpen, 'news');
        $body_onload = "includeFCKeditor('news');";
        $options = ['onload_func' => $body_onload];

        break;

    default:
        $files_to_edit = [];
        /*
        for ($index = 0; $index < count($files_news); $index++) {
            $file = $files_news[$index]['file'];
            $expr = '/^news_([a-z]{2})/';
            preg_match($expr, $file, $code_lang);
            $languageName = translator::getLanguageNameForLanguageCode($code_lang[1]);
            $href = HTTP_ROOT_DIR .'/admin/edit_news.php?op=edit&codeLang='.$code_lang[1];
            $text = translateFN('edit news in') .' '. $languageName;
            $files_to_edit[$index]['link'] = BaseHtmlLib::link($href, $text);
            $files_to_edit[$index]['data'] = translateFN('last change').': '.$files_news[$index]['data'];
        }
         *
         */
        for ($index = 0; $index < count($languages); $index++) {
            $languageName = $languages[$index]['nome_lingua'];
            $codeLang = $languages[$index]['codice_lingua'];
            $href = HTTP_ROOT_DIR . '/admin/edit_news.php?op=edit&codeLang=' . $codeLang;
            $text = translateFN('edit news in') . ' ' . $languageName;
            $files_to_edit[$index]['link'] = BaseHtmlLib::link($href, $text);
            $fileNews = ROOT_DIR . '/docs/news/news_' . $codeLang . '.txt';
            $lastChange = 'no file';
            foreach ($files_news as $key => $value) {
                //                print_r(array($fileNews,$value['path_to_file']));
                if ($fileNews == $value['path_to_file']) {
                    $lastChange = $value['data'];
                    break;
                }
            }
            $files_to_edit[$index]['data'] = translateFN('last change') . ': ' . $lastChange;
            $data = BaseHtmlLib::tableElement('', $thead_data, $files_to_edit);
        }
        break;
}
$label = translateFN('Modifica delle news');
$help = translateFN('Da qui l\'admin può modificare le news che appaiono in home page');

$content_dataAr = [
    'user_name' => $user_name,
    'user_type' => $user_type,
    'status' => $status,
    'label' => $label,
    'help' => $help,
    'data' => $data->getHtml(),
    'module' => $module,
];
//print_r($options);
//ARE::render($layout_dataAr, $content_dataAr, $options);
ARE::render($layout_dataAr, $content_dataAr, null, $options);
//print_r($files);
//print_r($languages);
