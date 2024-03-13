<?php

/**
 * FormValidator file
 *
 * PHP version 5
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Description of FormValidator
 *
 * @package   Default
 * @author    vito <vito@lynxlab.com>
 * @copyright Copyright (c) 2010, Lynx s.r.l.
 * @license   http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

namespace Lynxlab\ADA\Main\Forms\lib\classes;

class FormValidator
{
    public function __construct()
    {
    }
    /**
     * Validates the data contained in the given form control using the validator
     * specified by the given form control.
     *
     * @param FormControl $control
     *
     * @return boolean
     */
    public function validate($control)
    {
        if (preg_match($this->getRegexpForValidator($control->getValidator()), $control->getData())) {
            return true;
        }
        return false;
    }
    /**
     * Returns the regular expression associated to the given validator.
     *
     * @param integer $validator
     *
     * @return boolean
     */
    public function getRegexpForValidator($validator)
    {
        switch ($validator) {
            case self::DEFAULT_VALIDATOR:
                return self::DEFAULT_VALIDATOR_REGEXP;
            case self::USERNAME_VALIDATOR:
                return self::USERNAME_VALIDATOR_REGEXP;
            case self::PASSWORD_VALIDATOR:
                return self::PASSWORD_VALIDATOR_REGEXP;
            case self::EMAIL_VALIDATOR:
                return self::EMAIL_VALIDATOR_REGEXP;
            case self::NON_NEGATIVE_NUMBER_VALIDATOR:
                return self::NON_NEGATIVE_NUMBER_VALIDATOR_REGEXP;
            case self::POSITIVE_NUMBER_VALIDATOR:
                return self::POSITIVE_NUMBER_VALIDATOR_REGEXP;
            case self::FIRSTNAME_VALIDATOR:
            case self::LASTNAME_VALIDATOR:
                return self::FIRSTNAME_LASTNAME_VALIDATOR_REGEXP;
            case self::NOT_EMPTY_STRING_VALIDATOR:
                return self::NOT_EMPTY_STRING_VALIDATOR_REGEXP;
            case self::DATE_VALIDATOR:
                return self::DATE_VALIDATOR_REGEXP;
            case self::MULTILINE_TEXT_VALIDATOR:
                return self::MULTILINE_TEXT_VALIDATOR_REGEXP;
            case self::NON_NEGATIVE_MONEY_VALIDATOR:
                return self::NON_NEGATIVE_MONEY_VALIDATOR_REGEXP;
            case self::NUMERIC_INTERVAL_TO_0_FROM_50:
                return self::NUMERIC_INTERVAL_TO_0_FROM_50_VALIDATOR_REGEX;
            case self::NONZERO_NUMBER:
                return self::NONZERO_NUMBER_REGEX;
            default:
                return self::DEFAULT_VALIDATOR_REGEXP;
        }
    }

    public const DEFAULT_VALIDATOR = 0;
    public const USERNAME_VALIDATOR = 1;
    public const PASSWORD_VALIDATOR = 2;
    public const EMAIL_VALIDATOR = 3;
    public const NON_NEGATIVE_NUMBER_VALIDATOR = 4;
    public const POSITIVE_NUMBER_VALIDATOR = 5;
    public const FIRSTNAME_VALIDATOR = 6;
    public const LASTNAME_VALIDATOR = 6;
    public const NOT_EMPTY_STRING_VALIDATOR = 7;
    public const DATE_VALIDATOR = 8;
    public const TIME_VALIDATOR = 9;
    public const MULTILINE_TEXT_VALIDATOR = 10;
    public const NON_NEGATIVE_MONEY_VALIDATOR = 11;
    public const NUMERIC_INTERVAL_TO_0_FROM_50 = 12;
    public const NONZERO_NUMBER = 13;


    public const DEFAULT_VALIDATOR_REGEXP = '/^.*|\s$/';
    //const USERNAME_VALIDATOR_REGEXP = '/^[a-zA-Z][\d\w\.]{8,20}$/';
    public const USERNAME_VALIDATOR_REGEXP = '/^[A-Za-z0-9_][A-Za-z0-9_@\-\.]{7,255}$/';
    public const PASSWORD_VALIDATOR_REGEXP = '/^[\d\w\_]{8,20}$/';
    public const EMAIL_VALIDATOR_REGEXP = '/^(?:[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\'\[\]]+)@(?:(?:(?:[a-z0-9][a-z0-9\-_\[\]]*\.)+(?:aero|arpa|biz|com|cat|coop|edu|gov|info|int|jobs|mil|museum|name|nato|net|org|pro|travel|mobi|media|[a-z]{2}))|(?:[0-9]{1,3}(?:\.[0-9]{1,3}){3})|(?:[0-9a-fA-F]{1,4}(?:\:[0-9a-fA-F]{1-4}){7}))$/i';
    public const NON_NEGATIVE_NUMBER_VALIDATOR_REGEXP = '/^[0]|[1-9][0-9]*$/';
    public const POSITIVE_NUMBER_VALIDATOR_REGEXP = '/^[1-9][0-9]*$/';
    public const FIRSTNAME_LASTNAME_VALIDATOR_REGEXP = '/^.{2,}$/';
    public const NOT_EMPTY_STRING_VALIDATOR_REGEXP = '/^(?:[\d]+|[\w]+).*$/';
    public const DATE_VALIDATOR_REGEXP = '/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}$/';
    public const TIME_VALIDATOR_REGEXP = '/^[0-9]{2}\:[0-9]{2}\:[0-9]{2}$/';
    public const MULTILINE_TEXT_VALIDATOR_REGEXP = '/^.*$/m'; // /m is equivalent to /s in javascript regex (multiline)
    public const NON_NEGATIVE_MONEY_VALIDATOR_REGEXP = '/0\.00|^[1-9][0-9]*\.[0-9]{2}$/'; // /^[0]|^[1-9][0-9]*\.[0-9]{2}$/';
    public const NUMERIC_INTERVAL_TO_0_FROM_50_VALIDATOR_REGEX = '/^([1-4]{0,1}[0-9]{1}(\.[0-9])?|50|50.0)$/';
    public const NONZERO_NUMBER_REGEX = '/^-?[1-9]\d*$/';
}
