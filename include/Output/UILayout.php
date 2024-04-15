<?php

use Lynxlab\ADA\Main\Output\UILayout;

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Output\Layout;

// Trigger: ClassWithNameSpace. The class UILayout was declared with namespace Lynxlab\ADA\Main\Output. //

/**
 * Layout.inc.php file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 *
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Output;

class UILayout
{
    public function __construct()
    {
        if (!MULTIPROVIDER && isset($GLOBALS['user_provider'])) {
            $this->pathToLayoutDir = ROOT_DIR . DIRECTORY_SEPARATOR . 'clients' . DIRECTORY_SEPARATOR . $GLOBALS['user_provider'] . DIRECTORY_SEPARATOR .  'layout';
        } else {
            $this->pathToLayoutDir = ROOT_DIR . DIRECTORY_SEPARATOR . 'layout';
        }

        if (isset($_GET['family']) && !empty($_GET['family'])) {
            $this->layoutsPrecedence[] = $_GET['family'];
        }
        $this->layoutsPrecedence[] = ADA_TEMPLATE_FAMILY;
        // $conf_base = basename(HTTP_ROOT_DIR));
    }

    private function createAvailableLayoutsList()
    {

        $handle = opendir($this->pathToLayoutDir);
        while ($handle && (false !== ($layout = readdir($handle)))) {
            if ($this->isLayoutInstalled($layout)) {
                $this->availableLayouts[$layout] = $layout;
            }
        }
        closedir($handle);
    }

    private function isLayoutInstalled($layout)
    {
        if (
            $layout !== '.' && $layout !== '..'
            && is_dir($this->pathToLayoutDir . DIRECTORY_SEPARATOR . $layout)
        ) {
            return true;
        }
        return false;
    }

    public function getAvailableLayouts()
    {
        $this->createAvailableLayoutsList();
        return $this->availableLayouts;
    }

    private $layoutsPrecedence = [];
    private $availableLayouts = [];
    private $pathToLayoutDir = '';
}
