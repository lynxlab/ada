<?php

/**
 * UserRemovalForm file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 *
 */
class UserRemovalForm extends FForm
{
    public function __construct($isRestore = false)
    {
        parent::__construct();
        $this->addRadios(
            $isRestore ? 'restore' : 'delete',
            translateFN("Vuoi davvero " . ($isRestore ? "abilitare" : "disabilitare") . " l'utente selezionato?"),
            [0 => translateFN('No'), 1 => translateFN('Si')],
            0
        );
        $this->addHidden('id_user');
        $this->addHidden('isRestore')->withData($isRestore ? 1 : 0);
    }
}
