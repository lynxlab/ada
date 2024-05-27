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
class ChatRemovalForm extends FForm
{
    public function __construct()
    {
        parent::__construct();
        $desc = translateFN("Vuoi davvero cancellare la chat selezionata?") . ' ' .
                translateFN('Saranno cancellate anche le conversazioni della chat');
        $this->addRadios(
            'delete',
            //                translateFN("Vuoi davvero cancellare la chat selezionata?"),
            $desc,
            [0 => translateFN('No'), 1 => translateFN('Si')],
            0
        );
        $this->addHidden('id_room');
    }
}
