<?php

/**
 * @package     debugbar module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\DebugBar;

use DebugBar\DataCollector\MemoryCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\PDO\PDOCollector;
use DebugBar\DataCollector\PhpInfoCollector;
use DebugBar\DataCollector\RequestDataCollector;
use DebugBar\DebugBar;
use DebugBar\DebugBarException;
use Lynxlab\ADA\Main\Traits\ADASingleton;
use Lynxlab\ADA\Module\DebugBar\DataCollector\ADAAdminerCollector;
use Lynxlab\ADA\Module\DebugBar\DataCollector\GitInfoCollector;
use Lynxlab\ADA\Module\DebugBar\DataCollector\GlobalsCollector;

class ADADebugBar extends DebugBar
{
    use ADASingleton;

    private $pdoCollector;

    /**
     * @return void
     * @throws DebugBarException
     */
    protected function __construct()
    {
        $this->pdoCollector = new PDOCollector();
        $this->addCollector(new ADAAdminerCollector());
        $this->addCollector(new GitInfoCollector(ROOT_DIR));
        $this->addCollector(new PhpInfoCollector());
        $this->addCollector(new MessagesCollector());
        $this->addCollector(new RequestDataCollector());
        // $this->addCollector(new TimeDataCollector());
        $this->addCollector(new MemoryCollector());
        // $this->addCollector(new ExceptionsCollector());
        $this->addCollector(new GlobalsCollector());
        // $this->addCollector($this->pdoCollector);
        // $this->addCollector(new ConfigCollector([], 'ARE'));
        // $this->addCollector(new ConfigCollector([], 'dispatcher'));
    }

    /**
     * adds a message to the debugbar
     *
     * @param mixed $message
     * @return void
     */
    public static function addMessage(mixed $message)
    {
        $backfiles = debug_backtrace();
        $i = count(debug_backtrace()) > 1 ? 1 : 0;
        $line = $backfiles[$i]['line'] ? 'line ' . $backfiles[$i]['line'] . ': ' : '';
        $file = $backfiles[$i]['file'] ? basename($backfiles[$i]['file']) : 'info';
        ADADebugBar::getInstance()['messages']->addMessage($line . $message, $file, is_string($message));
    }

    /**
     * Get the value of pdoCollector
     */
    public function getPdoCollector()
    {
        return $this->pdoCollector;
    }
}
