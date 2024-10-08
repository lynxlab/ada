<?php

use Lynxlab\ADA\Browsing\CourseViewer;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\Helper\BrowsingHelper;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */

require_once realpath(__DIR__) . '/../../config_path.inc.php';

/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'user'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR];

/**
 * Get needed objects
 */
$neededObjAr = [
  AMA_TYPE_VISITOR      => ['layout','course'],
  AMA_TYPE_STUDENT      => ['layout','tutor','course','course_instance'],
  AMA_TYPE_TUTOR        => ['layout','course','course_instance'],
  AMA_TYPE_AUTHOR       => ['layout','course'],
];

/**
 * Performs basic controls before entering this module
 */
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';
//$self = 'index';

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
 * @var \Lynxlab\ADA\Main\User\ADALoggableUser $userObj
 *
 * WARNING: $media_path is used as a global somewhere else,
 * e.g.: node_classes.inc.php:990
 */
BrowsingHelper::init($neededObjAr);

/**
 * YOUR CODE HERE
 */
if (!isset($hide_visits)) {
    $hide_visits = 1; // default: no visits countg
}

if (!isset($order)) {
    $order = 'struct'; // default
}

if (!isset($expand)) {
    $expand = 1; // default: 1 level of nodes
}

$div_link = CDOMElement::create('div');
$link_expand = CDOMElement::create('a');
$link_expand->setAttribute('id', 'expandNodes');
$link_expand->setAttribute('href', 'javascript:void(0);');
$link_expand->setAttribute('onclick', "toggleVisibilityByDiv('structIndex','show');");
$link_expand->addChild(new CText(translateFN('Apri Nodi')));
$link_collapse = CDOMElement::create('a');
$link_collapse->setAttribute('href', 'javascript:void(0);');
$link_collapse->setAttribute('onclick', "toggleVisibilityByDiv('structIndex','hide');");
$link_collapse->addChild(new CText(translateFN('Chiudi Nodi')));

$div_link->addChild($link_expand);
$div_link->addChild(new CText(' | '));
$div_link->addChild($link_collapse);

$exp_link = $div_link->getHtml();

$order_div = CDOMElement::create('div', 'id:ordering');

$order = 'struct';
$alfa = CDOMElement::create('span', 'class:not_selected');
$link = CDOMElement::create('a', "href:main_index.php?order=alfa&expand=$expand");
$link->addChild(new CText(translateFN('Ordina per titolo')));
$alfa->addChild($link);
$order_div->addChild($alfa);
$order_div->addChild(new CText('|'));
$struct = CDOMElement::create('span', 'class:selected');
$struct->addChild(new CText(translateFN('Ordina per struttura')));
$order_div->addChild($struct);
$expand_nodes = true;

$search_label = translateFN('Cerca nell\'Indice:');
$node_type = 'standard_node';

/*
 * vito, 23 luglio 2008
 */

if ($expand_nodes) {
    $node_index  = $exp_link;
}
$main_index = CourseViewer::displayMainIndex($userObj, $sess_id_course, $expand, $order, $sess_id_course_instance, 'structIndex');
if (!AMADataHandler::isError($main_index)) {
    $node_index .= $main_index->getHtml();
}
$node_index = preg_replace('#</?' . 'img' . '[^>]*>#is', '', $node_index);

echo($node_index);
