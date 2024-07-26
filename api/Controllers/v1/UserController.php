<?php

/**
 * UserController.php
 *
 * @package        API
 * @author         Giorgio Consorti <g.consorti@lynxlab.com>
 * @copyright      Copyright (c) 2014, Lynx s.r.l.
 * @license        http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link           API
 * @version        0.1
 */

namespace Lynxlab\ADA\API\Controllers\v1;

use Fig\Http\Message\StatusCodeInterface;
use Lynxlab\ADA\API\Controllers\Common\AbstractController;
use Lynxlab\ADA\API\Controllers\Common\AdaApiInterface;
use Lynxlab\ADA\API\Controllers\Common\APIException;
use Lynxlab\ADA\Comunica\DataHandler\MessageHandler;
use Lynxlab\ADA\Main\AMA\AMADataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\Forms\UserRegistrationForm;
use Lynxlab\ADA\Main\Token\TokenManager;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Main\User\ADAUser;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * User controller for handling /users API endpoint
 *
 * @author giorgio
 */
class UserController extends AbstractController implements AdaApiInterface
{
    /**
     * Users own array key mappings
     *
     * @var array
     */
    private static $userKeyMappings = [
        'tipo' => 'type',
        'codice_fiscale' => 'tax_code',
        'sesso' => 'gender',
        'stato' => 'status',
        'matricola' => 'student_number',
    ];

    /**
     * GET method.
     *
     * Must be called with id parameter in the params array
     * Return the user object converted into an array.
     *
     * (non-PHPdoc)
     * @see \AdaApi\AdaApiInterface::get()
     */
    public function get(Request $request, Response &$response, array $params): array
    {
        /**
         * Are passed parameters OK?
         */
        $paramsOK = true;

        if (!empty($params)) {
            /**
             * User Object to return
             */
            $userObj = null;

            if ((int)($params['id'] ?? 0) > 0) {

                /**
                 * Check on user type to prevent multiport to
                 * do its error handling if no user found
                 */
                if (!AMADB::isError($this->common_dh->getUserType($params['id']))) {
                    $userObj = MultiPort::findUser(intval($params['id']));
                }
            } elseif (strlen($params['email'] ?? '') > 0) {

                /**
                 * If an email has been passed, validate it
                 */
                $searchString = DataValidator::validateEmail($params['email']);
            } elseif (strlen($params['username'] ?? '') > 0) {

                /**
                 * If a username has been passed, validate it
                 */
                $searchString = DataValidator::validateUsername($params['username']);
            } else {

                /**
                 * Everything has been tried, passed parameters are not OK
                 */
                $paramsOK = false;
            }

            /**
             * If parameters are ok and userObj is still
             * null try to do a search by username
             */
            if ($paramsOK && is_null($userObj) && ($searchString !== false)) {
                $userObj = MultiPort::findUserByUsername($searchString);
            } elseif ($searchString === false) {
                /**
                 * If either the passed email or username are not validated
                 * the parameters are not OK
                 */
                $paramsOK = false;
            }

            if ($paramsOK && !is_null($userObj) && !AMADB::isError($userObj)) {

                /**
                 * Build the array to be returned from the object
                 */
                $returnArray =  $userObj->toArray();

                /**
                 * Unset unwanted keys
                 */
                unset($returnArray['password']); // hide the password, even if it's encrypted
                unset($returnArray['tipo']);     // hide the user type as of 13/mar/2014
                unset($returnArray['stato']);    // hide the user status as of 13/mar/2014
                unset($returnArray['lingua']);   // hide the user language as of 13/mar/2014

                /**
                 * Perform the ADA=>API array key mapping
                 */
                self::ADAtoAPIArrayMap($returnArray, self::$userKeyMappings);
            } elseif ($paramsOK) {
                throw new APIException('No User Found', StatusCodeInterface::STATUS_NOT_FOUND);
            }
        } else {
            $paramsOK = false;
        }

        /**
         * Final check: if all OK return the data else throw the exception
         */
        if ($paramsOK && is_array($returnArray)) {
            return $returnArray;
        } elseif (!$paramsOK) {
            throw (new APIException('Wrong Parameters', StatusCodeInterface::STATUS_BAD_REQUEST))->setParams([
                'error_description' => 'Please use user id, username or email',
            ]);
        } else {
            throw new APIException('Unkonwn error in users get method', StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * POST method.
     *
     * If it's been reached with an application/json Content-type header
     * it expects the user json object in the request body,
     * else the $args array must contain the user data to be saved
     *
     * (non-PHPdoc)
     * @see \AdaApi\AdaApiInterface::post()
     */
    public function post(Request $request, Response &$response, array $args): array
    {
        /**
         * Check if header says it's json
         */
        if (strcmp($request->getHeaderLine('Content-Type'), 'application/json') === 0) {

            /**
             *  SLIM has converted the body to an array alreay
             */
            $userArr = $request->getParsedBody();
        } elseif (!empty($args) && is_array($args)) {

            /**
             * Assume we've been passed an array
             */
            $userArr = $args;
        } else {
            throw new APIException('Wrong Parameters', StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        /**
         * This GLOBAL is needed by the MultiPort and Translator class
         */
        $GLOBALS['common_dh'] = $this->common_dh;

        /**
         * Load supported languages
         */
        Translator::loadSupportedLanguagesInSession();

        /**
         * Convert API array keys to ADA array keys just
         * before instantiating the user object
         */
        self::APItoADAArrayMap($userArr, self::$userKeyMappings);

        /**
         * Unset the id (if any) to save as new user
         */
        if (isset($userArr['user_id'])) {
            unset($userArr['user_id']);
        }

        /**
         * Set username to the email.
         */
        $userArr['username'] = $userArr['e_mail'] ?? null;
        $userArr['email'] = $userArr['e_mail'] ?? null;

        /**
         * Build a user object
         */
        $userObj = new ADAUser($userArr);
        $userObj->setLayout('');
        if (!isset($userArr['type']) || strlen($userArr['type']) <= 0) {
            $userObj->setType(AMA_TYPE_STUDENT);
        }

        /**
         * New user is always in a presubscribed status
         */
        $userObj->setStatus(ADA_STATUS_PRESUBSCRIBED);

        /**
         * Generate a random password
         */
        $userObj->setPassword(sha1(time()));

        /**
         * Temporarly set a session user object needed
         * to build the UserRegistrationForm and for
         * below email message translations
         */
        $_SESSION['sess_userObj'] = $userObj;
        $form = new UserRegistrationForm();
        $form->fillWithArrayData($userArr);

        /**
         * If form is valid, save the user
         */
        if ($form->isValid()) {

            /**
             * Uncomment if the user is to be associated
             * by default to the public tester.
             */
            //  $regProvider = array (ADA_PUBLIC_TESTER);
            $regProvider = [];

            /**
             * Save the user in the public tester (only if
             * this is a multiprovider environment) and in
             * the authenticated switcher own tester.
             * This should be ok for non multiprovider environments.
             */
            foreach ($this->authUserTesters as $tester) {
                array_push($regProvider, $tester);
            }

            if (MULTIPROVIDER) {
                array_unshift($regProvider, ADA_PUBLIC_TESTER);
            }

            /**
             * Actually saves the user
             */
            $id_user = Multiport::addUser($userObj, $regProvider);

            if ($id_user < 0) {

                /**
                 * an error occoured
                 */
                $saveResults = [
                    'status' => 'FAILURE',
                    'message' => 'Check if a user exists already having passed email and username',
                ];
                $response = $response->withStatus(StatusCodeInterface::STATUS_CONFLICT);
            } else {

                /**
                 * saved ok
                 */
                $saveResults = [
                    'status' => 'SUCCESS',
                    'user_id' => $id_user,
                ];
                /**
                 * Set HTTP status to 201: Created before returning
                 */
                $response = $response->withStatus(StatusCodeInterface::STATUS_CREATED);

                /**
                 * Build and send a registration email as per browsing/registration.php file
                 */

                /**
                 * Create a registration token for this user and send it to the user
                 * with the confirmation request.
                 */
                $tokenObj = TokenManager::createTokenForUserRegistration($userObj);
                if ($tokenObj != false) {
                    $token = $tokenObj->getTokenString();

                    $admTypeAr = [AMA_TYPE_ADMIN];
                    $extended_data = true;
                    $admList = $this->common_dh->getUsersByType($admTypeAr, $extended_data);
                    if (!AMADataHandler::isError($admList) && array_key_exists('username', $admList[0]) && $admList[0]['username'] != '' && $admList[0]['username'] != null) {
                        $adm_uname = $admList[0]['username'] ?? ADA_ADMIN_MAIL_ADDRESS;
                        $adm_email = $admList[0]['e_mail'] ?? ADA_ADMIN_MAIL_ADDRESS;
                    } else {
                        $adm_uname = ADA_ADMIN_MAIL_ADDRESS;
                        $adm_email = ADA_ADMIN_MAIL_ADDRESS;
                    }

                    $switcherObj = Multiport::findUser($this->authUserID);
                    $emailLang = $switcherObj->getLanguage();

                    $title = PORTAL_NAME . ': ' . translateFN('ti chiediamo di confermare la registrazione.', null, $emailLang);

                    $text = sprintf(
                        translateFN('Gentile %s, ti chiediamo di confermare la registrazione ai %s.', null, $emailLang),
                        $userObj->getFullName(),
                        PORTAL_NAME
                    )
                        . PHP_EOL . PHP_EOL
                        . translateFN('Il tuo nome utente è il seguente:', null, $emailLang)
                        . ' ' . $userObj->getUserName()
                        . PHP_EOL . PHP_EOL
                        . sprintf(
                            translateFN('Puoi confermare la tua registrazione ai %s seguendo questo link:', null, $emailLang),
                            PORTAL_NAME
                        )
                        . PHP_EOL
                        . ' ' . HTTP_ROOT_DIR . "/browsing/confirm.php?uid=$id_user&tok=$token"
                        . PHP_EOL . PHP_EOL;

                    $message_ha = [
                        'titolo' => $title,
                        'testo' => $text,
                        'destinatari' => [$userObj->getUserName()],
                        'data_ora' => 'now',
                        'tipo' => ADA_MSG_SIMPLE,
                        'mittente' => $adm_uname,
                    ];

                    $mh = MessageHandler::instance(MultiPort::getDSN($tester));

                    /**
                     * Send the message as an internal message,
                     * don't care if an error occours here
                     *
                     * Commented on 07/mag/2014 15:56:46
                     */
                    // $result = $mh->send_message($message_ha);

                    /**
                     * Send the message as an email message
                     */
                    $message_ha['tipo'] = ADA_MSG_MAIL;
                    $result = $mh->sendMessage($message_ha);
                    if (AMADataHandler::isError($result)) {
                        $saveResults['message'] = 'An error occoured while emailing the user.';
                    }
                } else {
                    $saveResults['message'] = 'An error occourred while building the confirmation token.';
                }

                /**
                 * Done email sending.
                 */
                if (isset($saveResults['message']) && strlen($saveResults['message']) > 0) {
                    $saveResults['message'] .= 'The confirmation email has not been sent, please contact the user directly.';
                }
            }
        } else {

            /**
             * Try to investigate what the missing fields are
             */
            foreach ($form->getControls() as $control) {
                if (method_exists($control, 'getIsMissing') && $control->getIsMissing()) {

                    /**
                     * Build an array with missing fields as keys
                     */
                    $missingValues[$control->getId()] = true;
                }
            }
            if (isset($missingValues) && sizeof($missingValues) > 0) {

                /**
                 * Map the missingValues keys to API keys
                 */
                self::ADAtoAPIArrayMap($missingValues, self::$userKeyMappings);

                /**
                 * Extract the missingValues keys to build the
                 * list of missing or invalid value
                 */
                $missingValues = ': ' . implode(', ', array_keys($missingValues));
            } else {
                $missingValues = ': Unable to build missing fields list';
            }

            /**
             * Throws the exception
             */
            throw new APIException('Missing or Invalid User Fields' . $missingValues, StatusCodeInterface::STATUS_BAD_REQUEST);
        }

        /**
         * The session user object is no longer needed
         */
        unset($_SESSION['sess_userObj']);

        return $saveResults ?? [];
    }

    public function put(Request $request, Response &$response, array $args): Response
    {
        return $response;
    }

    public function delete(Request $request, Response &$response, array $args): Response
    {
        return $response;
    }
}
