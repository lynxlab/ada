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
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\AMA\MultiPort;
use Lynxlab\ADA\Main\DataValidator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
    public function get(Request $request, Response $response, array $params): array
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

    public function post(Request $request, Response $response, array $args): Response
    {
        return $response;
    }

    public function put(Request $request, Response $response, array $args): Response
    {
        return $response;
    }

    public function delete(Request $request, Response $response, array $args): Response
    {
        return $response;
    }
}
