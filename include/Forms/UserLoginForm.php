<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\Forms\UserLoginForm;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class UserLoginForm was declared with namespace Lynxlab\ADA\Main\Forms. //

/**
 * UserLoginForm file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms;

use Lynxlab\ADA\Main\Forms\lib\classes\FForm;
use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * Description of UserLoginForm
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010-2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
class UserLoginForm extends FForm
{
    public function __construct()
    {
        parent::__construct();

        $this->addTextInput('username', translateFN('Username'))
             ->setRequired()
             ->setValidator(FormValidator::USERNAME_VALIDATOR);

        $this->addPasswordInput('password', translateFN('Password'))
             ->setRequired()
             ->setValidator(FormValidator::PASSWORD_VALIDATOR);

        $this->addHidden('performLogin');
    }
}
