<?php

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;
use Lynxlab\ADA\Module\Impexport\FormSelectDatasForImport;
use Lynxlab\ADA\Module\Impexport\FormUploadImportFile;
use Lynxlab\ADA\Module\Impexport\ImportHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

ini_set('display_errors', '0');
error_reporting(E_ALL);
/**
 * Base config file
 */
require_once(realpath(__DIR__) . '/../../config_path.inc.php');

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];
/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_SWITCHER, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
        AMA_TYPE_SWITCHER => ['layout'],
        AMA_TYPE_AUTHOR => ['layout'],
];

if (array_key_exists('id_course', $_GET) || array_key_exists('id_node', $_GET)) {
    $neededObjAr[AMA_TYPE_AUTHOR] = ['node', 'layout', 'course'];
    $isAuthorImporting = true;
} else {
    $isAuthorImporting = false;
}

/**
 * Performs basic controls before entering this module
 */
require_once ROOT_DIR . '/include/module_init.inc.php';
BrowsingHelper::init($neededObjAr);

$self = 'impexport';

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST'  && !empty($_POST)) {
    $importHelper = new ImportHelper($_POST);
    $result = $importHelper->runImportAll();

    if (AMADB::isError($result)) {
        $data = translateFN("ERRORE NELL'IMPORTAZIONE: ") . $result->errorMessage();

        /* only a call to the addCourse data handler method should
         * generate a duplicate record error. Shall give out a 'special' error for it
        */
        if ($result->code == AMA_ERR_ADD || $result->code == AMA_ERR_UNIQUE_KEY) {
            $data .= '<br/>' . translateFN('Provare a modificare il campo nome del corso nel file ada_export.xml contenuto nel file .zip e riprovare.');
        }
    } else {
        $data = "<h3>" . translateFN('RISULTATI IMPORTAZIONE') . "</h3>";
        $str = "";
        foreach ($result as $courseId => $importedItems) {
            $str .= "<br/>" . translateFN('IL CORSO &Egrave; STATO CREATO CON id:') . $courseId;
            $str .= "<ul>";
            foreach ($importedItems as $type => $count) {
                $str .= "<li><b>" . $count . "</b> " . translateFN('oggetti di tipo') . " <b>" . $type . "</b> " .
                translateFN('aggiunti') . "</li>";
            }
            $str .= "</ul>";
        }
        $data .= $str;
    }

    if (isset($_POST['op']) && $_POST['op'] == 'ajaximport') {
        // if it's an ajax request, echo the html and die
        sleep(1); // if we're too fast, the jquery switching divs is going to flicker
        echo json_encode(['html' => $data]);
        die();
    }
} else {
    $error = false;
    /**
     * load authors list from the DB
     */
    $providerAuthors = $dh->findAuthorsList(['username'], '');
    $authors = [];
    foreach ($providerAuthors as $author) {
        $authors[$author[0]] = $author[1];
    }

    $selAuthor = $isAuthorImporting ? $userObj->getId() : 0;
    $selCourse = $isAuthorImporting ? $courseObj->getId() : 0;

    /**
     * load course list from the DB
     */
    $providerCourses = $dh->getCoursesList(['nome','titolo']);

    $courses = [];
    if (!empty($providerCourses) && !AMADB::isError($providerCOurses)) {
        foreach ($providerCourses as $course) {
            $courses[$course[0]] = '(' . $course[0] . ') ' . $course[1] . ' - ' . $course[2];
        }
    }

    if (empty($authors)) {
        $data = translateFN("Nessun autore trovato. Impossibile continuare l'importazione");
        $error = true;
    }

    if (!$error) {
        /**
         * generate the HTML used for import steps, strictyl handled by javascript (import.js)
         */

        /**
         * form1 has a css class in form.css to hide the submit button
         * should someone ever chagne its name, pls reflect change in css
         */
        if (isset($_GET['repofile']) && strlen(trim($_GET['repofile'])) > 0) {
            $form1opts = [
                'importURL' => str_replace(
                    ROOT_DIR,
                    HTTP_ROOT_DIR,
                    MODULES_IMPEXPORT_REPOBASEDIR . trim(urldecode($_GET['repofile']))
                ),
                'forceRunImport' => true,
                'isAuthorImporting' => $isAuthorImporting,
            ];
        } else {
            $form1opts = [];
        }
        $form1 = new FormUploadImportFile('importStep1Form', $form1opts);
        $form2 = new FormSelectDatasForImport('importStep2Form', $authors, $courses, $selAuthor, $selCourse);

        $step1DIV = CDOMElement::create('div', 'class:importFormStep1');
        $step1DIV->addChild(new CText($form1->getHtml()));
        $step1DIV->addChild(CDOMElement::create('span', 'id:importUrlStatus'));

        $step2DIV = CDOMElement::create('div', 'class:importFormStep2');
        $step2DIV->setAttribute('style', 'display:none');

        $paragraph = CDOMElement::create('div');
        $paragraph->addChild(new CText(translateFN("File caricato per l'importazione: ")));
        $paragraph->addChild(CDOMElement::create('span', 'id:uploadedFileName'));
        $step2DIV->addChild($paragraph);
        $step2DIV->addChild(new CText($form2->getHtml()));

        $importSelectNode = CDOMElement::create('div', 'class:divImportSN');
        $importSelectNode->setAttribute('style', 'display:none');

        $spanHelpText = CDOMElement::create('span', 'class:importSNHelp');
        $spanHelpText->addChild(new CText(translateFN('Scegli il nodo del corso che sar&agrave; genitore dei nodi importati.')));

        $courseTreeDIV = CDOMElement::create('div', 'id:courseTree');

        $courseTreeLoading = CDOMElement::create('span', 'id:courseTreeLoading');
        $courseTreeLoading->addChild(new CText(translateFN('Caricamento albero del corso') . '...<br/>'));

        $spanSelCourse = CDOMElement::create('span', 'id:selCourse');
        $spanSelCourse->setAttribute('style', 'display:none');
        $spanSelNode = CDOMElement::create('span', 'id:selNode');
        $spanSelNode->setAttribute('style', 'display:none');
        if ($isAuthorImporting) {
            $spanSelCourse->addChild(new CText($courseObj->getId()));
            $spanSelNode->addChild(new CText($nodeObj->id));
        }

        $buttonDIV = CDOMElement::create('div', 'class:importSN2buttons');

        $buttonPrev = CDOMElement::create('button', 'id:backButton');
        $buttonPrev->setAttribute('type', 'button');
        $buttonPrev->setAttribute('onclick', 'javascript:returnToImportStepTwo();');
        $buttonPrev->addChild(new CText('&lt;&lt;' . translateFN('Indietro')));

        $buttonNext = CDOMElement::create('button', 'id:exportButton');
        $buttonNext->setAttribute('type', 'button');
        $buttonNext->setAttribute('onclick', 'javascript:goToImportStepThree();');
        $buttonNext->addChild(new CText(translateFN('Importa')));

        $buttonDIV->addChild($buttonPrev);
        $buttonDIV->addChild($buttonNext);

        $importSelectNode->addChild($spanHelpText);
        $importSelectNode->addChild($courseTreeDIV);
        $importSelectNode->addChild($courseTreeLoading);
        $importSelectNode->addChild($spanSelCourse);
        $importSelectNode->addChild($spanSelNode);
        $importSelectNode->addChild($buttonDIV);

        $step3DIV = CDOMElement::create('div', 'class:importFormStep3');
        $step3DIV->setAttribute('style', 'display:none');

        $divProgressBar = CDOMElement::create('div', 'id:progressbar');
        $divProgressLabel = CDOMElement::create('div', 'id:progress-label');
        $divProgressBar->addChild($divProgressLabel);

        $divCourse =  CDOMElement::create('div', 'class:currentCourse');
        $divCourse->addChild(new CText(translateFN('Importazione dal corso:') . '&nbsp;'));
        $spanCourse = CDOMElement::create('span', 'id:coursename');
        $divCourse->addChild(new CText($spanCourse->getHtml()));

        $divCopyZip = CDOMElement::create('div', 'class:copyzip');
        $divCopyZip->addChild(new CText(translateFN('Copia files multimediali in corso')));
        $divCopyZip->setAttribute('style', 'display:none');

        $step3DIV->addChild($divProgressBar);
        $step3DIV->addChild($divCourse);
        $step3DIV->addChild($divCopyZip);

        $data = $step1DIV->getHtml() . $step2DIV->getHtml() . $importSelectNode->getHtml() . $step3DIV->getHtml();
    }
}

$content_dataAr = [
        'user_name' => $user_name,
        'user_type' => $user_type,
        'messages' => $user_messages->getHtml(),
        'agenda' => $user_agenda->getHtml(),
        'status' => $status,
        'label' => translateFN('Importazione corso'),
        'data' => $data,
];

$layout_dataAr['JS_filename'] = [
        JQUERY,
        JQUERY_UI,
        JS_VENDOR_DIR . '/dropzone/dist/min/dropzone.min.js',
        ROOT_DIR . '/js/include/dropzone/adaDropzone.js',
        MODULES_IMPEXPORT_PATH . '/js/tree.jquery.js',
        MODULES_IMPEXPORT_PATH . '/js/import.js',
];

$tplFamily = $_SESSION['sess_userObj']?->template_family;
$tplFamily = empty($tplFamily) ? ADA_TEMPLATE_FAMILY : $tplFamily;

$layout_dataAr['CSS_filename'] = [
        JQUERY_UI_CSS,
        JS_VENDOR_DIR . '/dropzone/dist/min/dropzone.min.css',
        ROOT_DIR . '/layout/' . $tplFamily . '/css/adadropzone.css',
        MODULES_IMPEXPORT_PATH . '/layout/jqtree.css',
];

$maxFileSize = (int) (ADA_FILE_UPLOAD_MAX_FILESIZE / (1024 * 1024));

$optionsAr['onload_func'] = 'initDoc(' . $maxFileSize . ');';

ARE::render($layout_dataAr, $content_dataAr, null, $optionsAr);
