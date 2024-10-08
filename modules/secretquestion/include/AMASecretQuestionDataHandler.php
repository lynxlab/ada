<?php

/**
 * @package     secretquestion module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2018, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Secretquestion;

use Exception;
use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\AMA\Traits\WithCUD;
use Lynxlab\ADA\Main\AMA\Traits\WithInstance;
use Lynxlab\ADA\Main\Token\TokenManager;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class AMASecretQuestionDataHandler extends AMACommonDataHandler
{
    use WithCUD;
    use WithInstance;

    /**
     * module's own data tables prefix
     *
     * @var string
     */
    public const PREFIX = 'module_secretquestion_';

    private const EXCEPTIONCLASS = SecretQuestionException::class;

    /**
     * Gets the user question text
     *
     * @var int $userId
     */
    public function getUserQuestion($userId)
    {
        $sql = 'SELECT `question` FROM `' . self::PREFIX . 'qa` WHERE `id_utente`=?';
        $result = $this->getOnePrepared($sql, $userId);
        return ($result !== false ? $result : '');
    }

    /**
     * Checks the user answer correctness, generates the TokenForPasswordChange and builds the
     * redirect url if the answer is correct or throws an exception
     *
     * @param int $userId
     * @param string $answer
     * @return array with 'redirecturl' key set to the redirection url to change the password
     */
    public function checkAnswer($userId, $answer)
    {
        $sql = 'SELECT COUNT(DISTINCT(`id_utente`)) FROM `' . self::PREFIX . 'qa` WHERE `id_utente`=? AND `answerhash`=?';
        $result = $this->getOnePrepared($sql, [$userId, self::prepareAnswer($answer)]);
        if ($result == 1) {
            $userObj = MultiPort::findUser($userId);
            if (!self::isError($userObj)) {
                /*
                * Create a token to authorize this user to change his/her password
                */
                $tokenObj = TokenManager::createTokenForPasswordChange($userObj);
                if ($tokenObj != false) {
                    return ['redirecturl' => HTTP_ROOT_DIR . "/browsing/forget.php?uid=$userId&tok=" . $tokenObj->getTokenString()];
                } else {
                    throw new Exception(translateFN('Errore interno: impossibile generare il token di autorizzazione'));
                }
            } else {
                throw new Exception(translateFN('Utente non valido'));
            }
        } else {
            throw new Exception(translateFN('La risposta non è corretta'));
        }
    }

    /**
     * Saves user question and answer
     *
     * @param int $userId
     * @param string $question
     * @param string $answer
     * @return mixed false or AMAError on error
     */
    public function saveUserQandA($userId, $question, $answer)
    {
        $sql = 'DELETE FROM `' . self::PREFIX . 'qa` WHERE `id_utente`=?';
        // use queryPrepared because executeCriticalPrepared will return
        // an error if no deleted rows
        $result = $this->queryPrepared($sql, [intval($userId)]);
        if (!AMADB::isError($result)) {
            $saveArr = [
                'id_utente' => $userId,
                'question' => trim($question),
                'answerhash' => self::prepareAnswer($answer),
            ];
            $result = $this->executeCriticalPrepared(
                $this->sqlInsert(self::PREFIX . 'qa', $saveArr),
                array_values($saveArr)
            );
            return $result;
        }
        return false;
    }

    /**
     * Prepares the answer string by applying at least a trim and a hashing function
     *
     * @param string $answer
     * @return string the prepared answer
     */
    private static function prepareAnswer($answer)
    {
        $answer = trim($answer);
        if (defined('SECRETQUESTION_CI_ANSWER') && SECRETQUESTION_CI_ANSWER === true) {
            $answer = strtoupper($answer);
        }
        return sha1($answer);
    }
}
