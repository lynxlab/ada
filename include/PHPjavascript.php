<?php

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");    // Date in the past
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");          // always modified
header("Cache-Control: no-store, no-cache, must-revalidate");  // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");                          // HTTP/1.0
header("Content-type: application/x-javascript");
//header("Content-Disposition: attachment; filename=javascript_conf.js");

require_once '../config_path.inc.php';
$allowedUsersAr = [AMA_TYPE_VISITOR, AMA_TYPE_STUDENT, AMA_TYPE_TUTOR, AMA_TYPE_AUTHOR, AMA_TYPE_ADMIN, AMA_TYPE_SWITCHER];
$trackPageToNavigationHistory = false;
require_once ROOT_DIR . '/include/module_init.inc.php';

$JS_i18n = [
    'confirmDelete' => translateFN('Stai per cancellare l\'elemento in modo definitivo. Confermi?'),
    'confirm' => translateFN('Conferma'),
    'cancel' => translateFN('Annulla'),
    'confirmTabChange' => translateFN('Ci sono dati non salvati in questa scheda. Continuare senza salvarli?'),
    'confirmLeavePage' => translateFN('Ci sono dati non salvati in questa scheda.'),
];

/**
 * GIORGIO, this is not needed and exposes a security hole.
 * Removed and placed here on 13/set/2013
 *
 * // var MODULES_DIR='<?php echo MODULES_DIR;?>';
 */
?>
//main vars
var HTTP_ROOT_DIR='<?php echo HTTP_ROOT_DIR;?>';
var HTTP_UPLOAD_PATH='<?php echo HTTP_UPLOAD_PATH;?>';
var ADA_DEFAULT_AVATAR='<?php echo ADA_DEFAULT_AVATAR; ?>';
<?php if (!empty($_SESSION['sess_template_family'])) : ?>
var ADA_TEMPLATE_FAMILY = '<?php echo $_SESSION['sess_template_family'];?>';
<?php else : ?>
var ADA_TEMPLATE_FAMILY = '<?php echo ADA_TEMPLATE_FAMILY;?>';
<?php endif; ?>
<?php if (!empty($_SESSION['sess_user_language'])) : ?>
var USER_LANGUAGE = '<?php echo $_SESSION['sess_user_language'];?>';
<?php else : ?>
var USER_LANGUAGE = null;
<?php endif; ?>
<?php if (defined('GCAL_HOLIDAYS_FEED')) : ?>
var GCAL_HOLIDAYS_FEED = '<?php echo GCAL_HOLIDAYS_FEED; ?>';
<?php else :?>
var GCAL_HOLIDAYS_FEED = '';
<?php endif; ?>
<?php if (!empty($_SESSION['sess_id_user'])) : ?>
var USER_ID = <?php echo $_SESSION['sess_id_user'];?>;
<?php else : ?>
var USER_ID = null;
<?php endif; ?>
<?php if (isset($_SESSION['IE-version']) && $_SESSION['IE-version'] !== false) : ?>
var IE_version = <?php echo $_SESSION['IE-version']; ?>;
<?php else : ?>
var IE_version = false;
<?php endif; ?>


//media type
var MEDIA_IMAGE = '<?php echo _IMAGE;?>';
var MEDIA_SOUND = '<?php echo _SOUND;?>';
var MEDIA_VIDEO = '<?php echo _VIDEO;?>';
var MEDIA_LINK = '<?php echo _LINK;?>';
var MEDIA_DOC = '<?php echo _DOC;?>';
var MEDIA_EXE = '<?php echo _EXE;?>';
var MEDIA_INTERNAL_LINK = '<?php echo INTERNAL_LINK;?>';
var MEDIA_POSSIBLE_TYPE = '<?php echo POSSIBLE_TYPE;?>';
var MEDIA_PRONOUNCE = '<?php echo _PRONOUNCE;?>';
var MEDIA_FINGER_SPELLING = '<?php echo _FINGER_SPELLING;?>';
var MEDIA_LABIALE = '<?php echo _LABIALE;?>';
var MEDIA_LIS = '<?php echo _LIS;?>';
var MEDIA_MONTESSORI = '<?php echo _MONTESSORI;?>';

const load_js = function(data, callback) {
  if(typeof data === 'string') {
    data = [data];
  }
  const head = document.getElementsByTagName("head")[0];
  var script = null;
  data.forEach(function(scriptSrc) {
    script = document.createElement("script");
    script.type = "text/javascript";
    script.src = scriptSrc;
    head.appendChild(script);
  });
  if(script!= null && callback != undefined) {
    if(script.onreadystatechange) {
      script.onreadystatechange = function() {
        if(script.readyState == "complete"  || script.readyState=="loaded") {
          script.onreadystatechange = false;
          callback();
        }
      }
    } else {
      script.onload = function() {
        callback();
      }
    }
  }
};

//translations
<?php
if (!empty($JS_i18n)) {
    echo "var i18n = Array();\n";
    foreach ($JS_i18n as $k => $v) {
        echo "i18n['" . $k . "'] = '" . str_replace("'", "\'", $v) . "';\n";
    }
}

//return $javascript_content;
exit();
