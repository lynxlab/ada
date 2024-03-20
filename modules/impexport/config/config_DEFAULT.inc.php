<?php
/**
 * @package 	import/export course
 * @author		giorgio <g.consorti@lynxlab.com>
 * @copyright	Copyright (c) 2012, Lynx s.r.l.
 * @license	http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version	0.1
 */

use Lynxlab\ADA\Main\Course\Course;

 require_once(MODULES_IMPEXPORT_PATH.'/include/AMAImpExportDataHandler.inc.php');

 define ('XML_EXPORT_FILENAME', 'ada_export.xml');
 define ('MODULES_IMPEXPORT_LOGDIR' , ROOT_DIR.'/log/impexport/');

 define ('MODULES_IMPEXPORT_REPOBASEDIR', Course::MEDIA_PATH_DEFAULT);
 define ('MODULES_IMPEXPORT_REPODIR', 'exported');

 return true;
