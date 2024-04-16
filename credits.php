<?php

use Lynxlab\ADA\Main\Helper\BrowsingHelper;
use Lynxlab\ADA\Main\Output\ARE;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Base config file
 */
require_once realpath(dirname(__FILE__)) . '/config_path.inc.php';
/**
 * Clear node and layout variable in $_SESSION
 */
$variableToClearAR = ['node', 'layout', 'course', 'course_instance'];

/**
 * Users (types) allowed to access this module.
 */
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT,AMA_TYPE_ADMIN,AMA_TYPE_AUTHOR, AMA_TYPE_TUTOR,AMA_TYPE_SWITCHER];

/**
 * Performs basic controls before entering this module
 */
$neededObjAr = [
  AMA_TYPE_VISITOR      => ['layout'],
  AMA_TYPE_STUDENT         => ['layout'],
  AMA_TYPE_TUTOR => ['layout'],
  AMA_TYPE_AUTHOR       => ['layout'],
  AMA_TYPE_ADMIN        => ['layout'],
];
require_once ROOT_DIR . '/include/module_init.inc.php';


/**
 * Get needed objects
 */
BrowsingHelper::init($neededObjAr);

$self = 'default';

$credits_data = "<p>"
              . translateFN("ADA &egrave; un software libero sviluppato da")
              . ' ' . "<a href='http://www.lynxlab.com'; target='_blank'>Lynx s.r.l.</a>"
              .  "<p>" . translateFN("E' rilasciato con licenza ") . " <a href='" . HTTP_ROOT_DIR . "/browsing/external_link.php?file=gpl.txt'; target='_blank'>GNU GPL.</a></p>" .
              translateFN("Hanno contribuito allo sviluppo:") .
              "<ul>
              <li>Maurizio Mazzoneschi</li>
              <li>Stefano Penge</li>
              <li>Vito Modena</li>
              <li>Giorgio Consorti</li>
              <li>Sara Capotosti</li>
              <li>Valerio Riva</li>
              <li>Guglielmo Celata</li>
              <li>Stamatis Filippis</li>
              </ul>" .
              translateFN("Hanno contribuito al disegno dell'interfaccia:") .
              "<ul>
              <li>Gianluca Toni</li>
              <li>Francesco Fagnini</li>
              <li>Chiara Codino</li>
              </ul>" .
              "</p>";

$title = translateFN('Credits');

$content_dataAr = [
  'home' => $home ?? '',
  'user_name' => $user_name ?? '',
  'user_type' => $user_type ?? '',
  'user_level' => $user_level ?? '',
  'status' => $status ?? '',
  'help' => $credits_data ?? '',
  'menu' => $menu ?? '',
  'course_title' => translateFN("Credits"),
  'message' => $message ?? '',
  'agenda_link' => $agenda_link ?? '',
  'msg_link' => $msg_link ?? '',
];

ARE::render($layout_dataAr, $content_dataAr);
