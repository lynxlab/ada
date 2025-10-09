<?php

/**
 * ModuleLoaderHelper is included in ada_config.inc.php file
 */

use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Utilities;

if (!Utilities::inInstall()) {
    ModuleLoaderHelper::loadModuleFromArray([
        // keep eventdispatcher as first module, so others may use it
        [ 'name' => 'eventdispatcher', 'dirname' => 'event-dispatcher' ],
        [ 'name' => 'encryptfields', 'dirname' => 'encrypt-fields', 'forcedisable' => true ],
        [ 'name' => 'extractlogger', 'dirname' => 'extract-logger' ],
        [ 'name' => 'debugbar' ],
        [ 'name' => 'codeman', 'dirname' => 'code_man' ],
        [ 'name' => 'test' ],
        [ 'name' => 'newsletter' ],
        [ 'name' => 'servicecomplete', 'dirname' => 'service-complete' ],
        [ 'name' => 'apps' ],
        [ 'name' => 'impexport' ],
        [ 'name' => 'classroom' ],
        [ 'name' => 'classagenda' ],
        [ 'name' => 'classbudget' ],
        [ 'name' => 'login' ],
        [ 'name' => 'slideimport' ],
        [ 'name' => 'formmail' ],
        [ 'name' => 'gdpr' ],
        [ 'name' => 'secretquestion' ],
        [ 'name' => 'forkedpaths', 'dirname' => 'forked-paths' ],
        [ 'name' => 'badges' ],
        [ 'name' => 'studentsgroups' ],
        [ 'name' => 'bigbluebutton', 'dirname' => 'bbb-integration' ],
        [ 'name' => 'zoomconf', 'dirname' => 'zoom-integration' ],
        [ 'name' => 'jitsi', 'dirname' => 'jitsi-meet-integration' ],
        [ 'name' => 'collaboraacl', 'dirname' => 'collabora-access-list' ],
        [ 'name' => 'impersonate' ],
        [ 'name' => 'etherpad', 'dirname' => 'etherpad-integration' ],
        [ 'name' => 'notifications' ],
        [ 'name' => 'cloneinstance' ],
        [ 'name' => 'instancesreport' ],
        // timednode is disabled by default in standard ada
        [ 'name' => 'timednode', 'forcedisable' => true ],
        // maxtries is disabled by default in standard ada
        [ 'name' => 'maxtries', 'dirname' => 'test-max-tries', 'forcedisable' => true ],
    ]);
}
