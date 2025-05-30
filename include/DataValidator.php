<?php

/**
 *
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @author      Giorgio Consorti <g.consorti@lynxlab.com>
 * @author
 * @copyright   Copyright (c) 2024, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link        index
 * @version     0.2
 */

namespace Lynxlab\ADA\Main;

use Lynxlab\ADA\Main\Forms\lib\classes\FormValidator;

class DataValidator
{
    private const MINPASSWORDLEN = 8;
    private const MAXPASSWORDLEN = 40;


    /**
     * checkInputValues
     *
     * @param  string $parameterName
     * @param  string $filter
     * @param  int $inputType
     * @param  mixed $defaultValue
     * @return mixed
     */
    public static function checkInputValues(string $parameterName, string $filter, int $inputType = INPUT_GET, $defaultValue = false): mixed
    {
        $value = filter_input($inputType, $parameterName, FILTER_CALLBACK, ['options' => 'Lynxlab\ADA\Main\DataValidator::validate' . $filter]) ?? $defaultValue;
        return ($value !== false) ? $value : $defaultValue;
    }

    /**
     * validateValueWithPattern
     *
     * @param  string $value
     * @param  string $pattern
     * @return bool
     */
    public static function validateValueWithPattern(?string $value, string $pattern): bool|string
    {
        return match ($value) {
            null,'' => false,
            default => (preg_match($pattern, $value)) ? $value : false
        };
    }


    /**
     * validateValue
     *
     * @param  mixed $value
     * @return mixed
     */
    public static function validateValue(?string $value): bool|string
    {
        return match ($value) {
            null,'' => false,
            default => $value
        };
    }


    public static function validateLocalFilename(?string $filename): bool|string
    {
        if (self::validateNotEmptyString($filename)) {
            $pattern = '/^[a-zA-Z\_]+\.[a-zA-Z0-9\.]+$/';
            return static::validateValueWithPattern($filename, $pattern);
        }
        return false;
    }

    public static function validateString(?string $string): bool|string
    {
        // Caution: this function may return '', a bool or a string
        if (!isset($string) || empty($string)) {
            return '';
        } else {
            return $string;
        }
        return false;
    }

    public static function validateNotEmptyString(?string $string): bool|string
    {
        return static::validateValue($string);
    }

    public static function validateBirthdate(?string $date): bool
    {
        // Caution: this function may only a bool
        $ok = self::validateDateFormat($date);
        if ($ok) {
            [$giorno, $mese, $anno] = explode("/", $date);

            $check = mktime(0, 0, 0, $mese, $giorno, $anno);
            $today = mktime(0, 0, 0, date("m"), date("d"), date("y"));

            if ($check > $today) {
                $ok = false;
            } elseif ($anno < 1900) {
                $ok = false;
            } elseif (!checkdate($mese, $giorno, $anno)) {
                $ok = false;
            }
        }
        return $ok;
    }

    public static function validateDateFormat(?string $date): bool|string
    {
        $pattern = FormValidator::DATE_VALIDATOR_REGEXP;
        return static::validateValueWithPattern($date, $pattern);
    }

    public static function validateEventToken(?string $event_token): bool|string
    {
        $pattern = '/^[1-9][0-9]*_[1-9][0-9]*_[1-9][0-9]*_[1-9][0-9]+$/';
        return static::validateValueWithPattern($event_token, $pattern);
    }

    public static function validateActionToken(?string $action_token): bool|string
    {
        $pattern = '/^[a-f0-9]{40}$/';
        return static::validateValueWithPattern($action_token, $pattern);
    }

    public static function isUinteger($value): bool|int
    {
        if (isset($value) && !empty($value)) {
            if (is_int($value) && $value >= 0) {
                return $value;
            }

            if (is_string($value) && ctype_digit($value)) {
                return (int)$value;
            }
        }
        return false;
    }

    public static function validateNodeId(?string $nodeId): bool|string
    {
        $pattern = '/^[1-9][0-9]*\_[0-9]*$/';
        return static::validateValueWithPattern($nodeId, $pattern);
    }

    public static function validateCourseId(?string $courseId): bool|int
    {
        return static::validateInteger($courseId);
    }

    public static function validateCourseInstanceId(?string $courseInstanceId): bool|int
    {
        return static::validateInteger($courseInstanceId);
    }

    public static function validateUserId(?string $userId): bool|int
    {
        return static::validateInteger($userId);
    }




    public static function validateTestername(?string $providername, $multiprovider = true): bool|string
    {
        /**
         * giorgio, set proper pattern validation depending on multiprovider environment
         * modified 14/ago/2013 if the commented lines are kept, admin will not view
         * testers whose name is NOT 'clientX' in singleprovider mode.
         * Thought that this was not a desirable behaviour...
         * anyway, i keep passing the multiprovider params for
         * easy switching to whatsoever behaviour is desired.
         */
        //     if ($multiprovider===true)
        //       $pattern = '/^(?:client)[0-9]{1,2}$/';
        //     else
        $pattern = '/^(\w|-)+$/';
        return static::validateValueWithPattern($providername, $pattern);
    }

    // TODO: definire minima e massima lunghezza per lo username
    public static function validateName(?string $lastname): bool|string
    {
        return static::validateValue($lastname);
    }

    public static function validateFirstname(?string $firstname): bool|string
    {
        return static::validateName($firstname);
    }


    public static function validateLastname(?string $lastname): bool|string
    {
        return static::validateName($lastname);
    }



    // TODO: definire minima e massima lunghezza per lo username
    public static function validateUsername(?string $username): bool|string
    {
        /* username is the user's email
         * ->  return self::validate_email($username);
         * */
        $pattern = FormValidator::USERNAME_VALIDATOR_REGEXP;
        return static::validateValueWithPattern($username, $pattern);
    }


    public static function validatePassword($password, $passwordcheck)
    {
        /**
         * @author steve 28/mag/2020
         *
         *
         * @todo move class constants MINPASSWORDLEN and MAXPASSWORDLEN to configuration file
         */


        if (
            isset($password) && !empty($password) && isset($passwordcheck)
            && !empty($passwordcheck) && $password == $passwordcheck
        ) {
            $pattern = '/^[A-Za-z0-9_\.]{' . self::MINPASSWORDLEN . ',' . self::MAXPASSWORDLEN . '}$/';
            if (preg_match($pattern, $password)) {
                return $password;
            }
        }
        return false;
    }

    public static function validatePasswordModified($password, $passwordcheck)
    {
        if (
            isset($password) && !empty($password) && isset($passwordcheck)
            && !empty($passwordcheck) && $password == $passwordcheck
        ) {
            $pattern = '/^[A-Za-z0-9_\.]{8,40}$/';
            if (preg_match($pattern, $password)) {
                return $password;
            }
        }
        if (isset($password) && !empty($password) && !isset($passwordcheck)) {
            return false;
        }
        if ((isset($password) && empty($password)) && (isset($passwordcheck) && empty($passwordcheck))) {
            return true;
        }
        return false;
    }

    public static function validatePhone(?string $phone): string
    {
        // Caution: this function may return '' or a string, not bool
        // It is not sure that it is invoked always with a string but it is likely
        if (!isset($phone)) {
            return '';
        }
        return $phone;
    }

    public static function validateAge(?string $age): bool|string
    {
        // Caution: this function may return '', bool or a string representing an integer
        if (!isset($age)) {
            return '';
        }
        if (is_numeric($age)) {
            if ((17 < $age) && ($age < 99)) {
                return $age;
            }
        }
        return false;
    }

    public static function validateEmail(?string $email): bool|string
    {
        $pattern = FormValidator::EMAIL_VALIDATOR_REGEXP;
        return static::validateValueWithPattern($email, $pattern);
    }

    public static function validateUrl(?string $url): bool|string
    {
        /**
         * Regular Expression for URL validation by Diego Perini
         * Pls refer to https://gist.github.com/dperini/729294
         * for details and upgrades
         */
        $pattern = '_^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@)?(?:(?!(?:10|127)(?:\.\d{1,3}){3})(?!(?:169\.254|192\.168)(?:\.\d{1,3}){2})(?!172\.(?:1[6-9]|2\d|3[0-1])(?:\.\d{1,3}){2})(?:[1-9]\d?|1\d\d|2[01]\d|22[0-3])(?:\.(?:1?\d{1,2}|2[0-4]\d|25[0-5])){2}(?:\.(?:[1-9]\d?|1\d\d|2[0-4]\d|25[0-4]))|(?:(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]-*)*[a-z\x{00a1}-\x{ffff}0-9]+)*(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))\.?)(?::\d{2,5})?(?:[/?#]\S*)?$_iuS';
        return static::validateValueWithPattern($url, $pattern);
    }

    public static function validateIpaddress(?string $ipaddress): bool|string
    {
        /**
         * Regular Expression for IBAN validation
         * Pls refer to https://ihateregex.io/expr/ip/
         * for details and upgrades
         */

        $pattern = '(\b25[0-5]|\b2[0-4][0-9]|\b[01]?[0-9][0-9]?)(\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)){3}';
        return static::validateValueWithPattern($ipaddress, $pattern);
    }


    public static function validateIban(?string $iban): bool|string
    {
        /**
         * Regular Expression for IBAN validation
         * Pls refer to https://stackoverflow.com/a/44657292
         * for details and upgrades
         */

        $pattern = '/^([A-Z]{2}[ \-]?[0-9]{2})(?=(?:[ \-]?[A-Z0-9]){9,30}$)((?:[ \-]?[A-Z0-9]{3,5}){2,7})([ \-]?[A-Z0-9]{1,3})?$/m';
        return static::validateValueWithPattern($iban, $pattern);
    }

    public static function validateLanguage(?string $language): bool|string
    {
        return match ($language) {
            null => false,
            default => (count(array_filter(Translator::getSupportedLanguages(), fn ($x) => $x['codice_lingua'] == $language)) > 0) ? $language : false
        };
    }

    public static function validateExtensionType(?string $value): bool|string
    {
        return match ($value) {
            'html','htm','pdf' => $value,
            default => false
        };
    }

    public static function validateMessage(?string $value): bool|string
    {
        return static::validateValue(htmlspecialchars($value, ENT_QUOTES));
    }

    public static function validateInteger(?string $value): bool|int
    {
        return static::validateValue(filter_var($value, FILTER_VALIDATE_INT));
    }

    /**
     * multiValidateValue
     *
     * validate an array of requests ($_POST or $_GET) and returns an array with $variables as keys
     *
     * Use:
     *  these are the names of the variables:
     *  $variableNames = ['Id_node','Id_course','tipo','messages'];
     *  these are the parameters' names as an array of key:
     *  $parameterNames = array_flip(['node_id','courseID','type','message']);
     *  these are the requests (from $_GET or $_POST)
     *  (they are filtered by parameters' names so as to guarantee the correct order):
     *  $requests = array_intersect_key($_GET,$parameterNames,true);
     *  $validatedValues = DataValidator::multiValidateValue($requests,$variableNames);
     *  extract($validatedValues);
     *  Note: $guess can be true or false; if true, tries to guess the validator type from the name of the parameter
     *
     * @param  mixed $parameters
     * @param  mixed $variables
     * @return array
     */
    public static function multiValidateValue(array $parameters, array $variables, string $defaultValidator = 'validateValue', bool $guess = false): array
    {
        if (count($parameters) == count($variables)) {
            if ($guess === true) {
                // try to guess the correct validator on the basis of the name of the parameter
                $values = [];
                foreach ($parameters as $parameterName => $value) {
                    $validator = static::guessValidator($parameterName);
                    $validatedValue = call_user_func($validator, $value);
                    $validatedValues[] = $validatedValue;
                }
            } else {
                // simply applies the same basic validator to all values do this:
                $validatedValues = array_map([self::class,$defaultValidator], $parameters);
            }
            return array_combine($variables, $validatedValues);
        } else {
            return array_fill_keys($parameters, null);
        }
    }

    public static function guessValidator(string $variableName): array
    {
        $validator =  match ($variableName) {
            'username','user_username' => 'validateUsername',
            'firstName','lastName' => 'validateName',
            'nome_file','file_lang','file_edit' => 'validateLocalFilename',
            'id_utente','user_id','userId','ownerId','studentId','id_utente_autore' => 'validateUserId',
            'user_type','chatroom','lastMsgId' => 'validateInteger',
            'id_course_instance','id_instance','instanceId','course_instance_id','course_instance' => 'validateCourseInstanceId',
            'course_id','courseID','courseId','id_course' => 'validateCourseid',
            'id_nodo_parent','parent_node','node_id','nodeId','id_node','id_test','id_nodo_toc','id_nodo_iniziale' => 'validateNodeId',
            'email','receiver_email','payer_email' => 'validateEmail',
            'date' => 'validateDateFormat',
            'user_birthdate' => 'validateBirthdate',
            'language','lan' => 'validateLanguage',
            // 'selectLanguage','cod_lang'=> 'validateLanguage', ??
            'iban','tester_iban' => 'validateIban',
            'tester' => 'validateTesterName',
            'message','message_to_send','welcome_msg','msgbody' => 'validateMessage',
            default => 'validateValue' //anything else...
        };
        return  [self::class,$validator];
    }
}
