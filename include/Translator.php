<?php

/**
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Main;

use Lynxlab\ADA\Main\AMA\AMACommonDataHandler;
use Lynxlab\ADA\Main\AMA\AMADB;

/**
 * class Translator: used to retrieve message translations, given a message to translate.
 */
class Translator
{
    private static $already_translated_messages;

    /**
     * function messageHash: used to calculate a unique hash to temporary store translations without access database every time.
     *
     * @param  string $message       - the message to translate
     * @param  string $user_language_code - the user language in which translate the message
     * @return string - the correspondent hash.
     */
    public static function messageHash($message, $code)
    {
        return md5($message . $code);
    }

    /**
     * function translate: used to obtain the translation in the given user language for the given message.
     * If no translation is found, returns the given message.
     *
     * @param  string $message       - the message to translate
     * @param  string $user_language_code - the user language in which translate the message
     * @return string - the translated message or the given message if no translation is found.
     */
    public static function translate($message, $user_language_code)
    {
        //ADALogger::log('Translator::translate');

        $common_dh = AMACommonDataHandler::getInstance();

        $language_code = $user_language_code;

        if (!empty(self::$already_translated_messages[self::messageHash($message, $language_code)])) {
            return self::$already_translated_messages[self::messageHash($message, $language_code)];
        }

        $translated_message = $common_dh->findMessageTranslation($message, $language_code);

        if (AMADB::isError($translated_message)) {
            /*
             * In case an error occurs during translation retrieval,
             * return the original message to the user.
             */
            return $message;
        } else {
            self::$already_translated_messages[self::messageHash($message, $language_code)] = $translated_message;
        }
        return self::$already_translated_messages[self::messageHash($message, $language_code)];
    }

    /**
     * function loadSupportedLanguagesInSession: used to load ADA supported languages for
     * user interface messages translation into a session variable.
     *
     * @return TRUE if there arn't errors, ADAError object otherwise
     */
    public static function loadSupportedLanguagesInSession()
    {
        if (isset($_SESSION['sess_ada_supported_languages'])) {
            unset($_SESSION['sess_ada_supported_languages']);
        }

        $common_dh = AMACommonDataHandler::getInstance();

        $supported_languages = [];
        $supported_languages = $common_dh->findLanguages();

        if (AMADB::isError($supported_languages)) {
            // FIXME: qui si verifica questo errore anche quando c'è un errore di connessione al database.
            $errObj = new ADAError(
                $supported_languages,
                'No languages for user interface translation were found.',
                'Translator'
            );
        }

        $_SESSION['sess_ada_supported_languages'] = $supported_languages;
        return true;
    }

    /**
     * function getSupportedLanguages(): returns ADA supported languages as stored in
     * the session variable sess_ada_supported_languages.
     * If this variable isn't set, return an ADAError.
     *
     * @return mixed - array of supported languages or ADAError object.
     */
    public static function getSupportedLanguages()
    {
        if (!isset($_SESSION['sess_ada_supported_languages'])) {
            $errObj = new ADAError(
                null,
                'No languages for user interface translation were found.',
                'Translator'
            );
        }
        return $_SESSION['sess_ada_supported_languages'];
    }

    public static function getLanguagesIdAndName()
    {
        if (!isset($_SESSION['sess_ada_supported_languages'])) {
            $errObj = new ADAError(null, 'lingua non trovata', 'Translator');
        }

        $l = $_SESSION['sess_ada_supported_languages'];
        $languages = [];
        foreach ($l as $language) {
            $languages[$language['id_lingua']] = $language['nome_lingua'];
        }

        return $languages;
    }

    /**
     * function getLanguageCodeForLanguageName: used to obtain the ISO 639-1 code associated with
     * the user language name passed as argument.
     *
     * @param string $user_language - the user language name (e.g. 'italiano', 'english', etc...)
     *
     * @return string - the ISO 639-1 code associated with $user_language (e.g. 'it' for 'italiano', 'en' for 'english', etc...)
     */

    public static function getLanguageCodeForLanguageName($user_language)
    {
        if (!isset($_SESSION['sess_ada_supported_languages'])) {
            $errObj = new ADAError(null, 'lingua non trovata', 'Translator');
        }

        $l = $_SESSION['sess_ada_supported_languages'];

        foreach ($l as $language) {
            if ($language['nome_lingua'] == $user_language) {
                return $language['codice_lingua'];
            }
        }

        $errObj = new ADAError(null, 'Language code not found.', 'Translator');
        return '';
    }

    /**
     * get id of the language associated to the ISO 639-1 code
     * passed as argument.
     *
     * @param string $ISO_code
     * @return int|string|false
     */
    public static function getLanguageIDFromCode($ISO_code)
    {
        return array_search(
            static::getLanguageNameForLanguageCode($ISO_code),
            static::getLanguagesIdAndName()
        );
    }

    /**
     * function getLanguageNameForLanguageCode: used to obtain name of the language associated to the ISO 639-1 code
     * passed as argument.
     *
     * @param string $user_language - the user language name (e.g. 'italiano', 'english', etc...)
     *
     * @return string - the ISO 639-1 code associated with $user_language (e.g. 'it' for 'italiano', 'en' for 'english', etc...)
     */

    public static function getLanguageNameForLanguageCode($ISO_code)
    {
        if (!isset($_SESSION['sess_ada_supported_languages'])) {
            $errObj = new ADAError(null, 'lingua non trovata', 'Translator');
        }

        $l = $_SESSION['sess_ada_supported_languages'];

        foreach ($l as $language) {
            if ($language['codice_lingua'] == $ISO_code) {
                return $language['nome_lingua'];
            }
        }
        return null;
        //    $errObj = new ADAError(NULL,'Language code not found.', 'Translator');
    }



    public static function getLanguageInfoForLanguageId($language_id)
    {
        if (!isset($_SESSION['sess_ada_supported_languages'])) {
            $errObj = new ADAError(null, 'lingua non trovata', 'Translator');
        }

        $l = $_SESSION['sess_ada_supported_languages'];

        foreach ($l as $language) {
            if ($language['id_lingua'] == $language_id) {
                return $language;
            }
        }

        return ['id_lingua' => 0, 'nome_lingua' => '', 'codice_lingua' => ''];
    }


    //  public static function getLanguageCodeForNativeName($native_name) {
    //    if (!isset($_SESSION['sess_ada_supported_languages'])) {
    //      $errObj = new ADAError(NULL,'lingua non trovata', 'Translator');
    //    }
    //
    //    $l = $_SESSION['sess_ada_supported_languages'];
    //    foreach($l as $language_code => $language_native_name) {
    //      if($language_native_name == $native_name) {
    //        return $language_code;
    //      }
    //    }
    //    $errObj = new ADAError(NULL,'Language code not found.','Translator');
    //  }

    public static function negotiateLoginPageLanguage($lang_get = null)
    {
        $server_http_accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $dynamicLanguage = ADA_DYNAMIC_LANGUAGE;
        if (!$dynamicLanguage) {
            return ADA_LOGIN_PAGE_DEFAULT_LANGUAGE;
        }
        /*
         * the following regexp searches for user's browser accepted language
         * preferences.
         *
         *      standard  for HTTP_ACCEPT_LANGUAGE is defined under
         *      http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4
         *      pattern to find is therefore something like this:
         *          1#( language-range [ ";" "q" "=" qvalue ] )
         *      where:
         *          language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
         *          qvalue         = ( "0" [ "." 0*3DIGIT ] )
         *                         | ( "1" [ "." 0*3("0") ] )
         */
        $regexp = "/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?" .
            "(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i";
        $matches = [];
        preg_match_all($regexp, $server_http_accept_language, $matches, PREG_SET_ORDER);

        if (count($matches) > 0) {
            foreach ($matches as $match) {
                /**
                 * if matched string ends with a comma, remove it and assign to $foundLang
                 */
                if (str_ends_with($match[0], ',')) {
                    $foundLang = substr($match[0], 0, -1);
                } else {
                    $foundLang = $match[0];
                }
                /**
                 * if foundLang has a semicolon it is in the form  of
                 * "en;q=0.8", extract its characters up to the semicolon
                 */
                $hasSemicolon = stripos($foundLang, ';');
                if ($hasSemicolon !== false) {
                    $foundLang = substr($foundLang, 0, $hasSemicolon);
                }
                $l2[] = $foundLang;
            }
            $user_defined_languages_count  = count($l2);
        } else {
            $user_defined_languages_count  = 0;
        }

        $ada_supported_languages       = self::getSupportedLanguages();
        $ada_supported_languages_count = count($ada_supported_languages);

        if ($ada_supported_languages_count == 0) {
            $errObj = new ADAError(null, 'No supported languages found.', 'Translator');
        }
        /*
         * No user defined languages were given, return a default language
         */
        if ($user_defined_languages_count == 0) {
            //return ADA_LOGIN_PAGE_DEFAULT_LANGUAGE;
            // FIXME: dovrebbe restituire la lingua di default, ora
            // restituisce la prima che trova
            return $ada_supported_languages[0]['codice_lingua'];
        }

        /*
         * Find a user defined language that is supported by ADA
         */
        if ($lang_get != null) {
            for ($j = 0; $j < $ada_supported_languages_count; $j++) {
                if ($lang_get == $ada_supported_languages[$j]['codice_lingua']) {
                    return $ada_supported_languages[$j]['codice_lingua'];
                }
            }
        }
        for ($i = 0; $i < $user_defined_languages_count; $i++) {
            for ($j = 0; $j < $ada_supported_languages_count; $j++) {
                if (strcasecmp($l2[$i], $ada_supported_languages[$j]['codice_lingua']) === 0) {
                    return $ada_supported_languages[$j]['codice_lingua'];
                } elseif (str_starts_with($l2[$i], $ada_supported_languages[$j]['codice_lingua'])) {
                    // if browser request lang startsWith current checking language
                    // it is a bestmatch that can be returned when out of the loops
                    $bestMatch = $ada_supported_languages[$j]['codice_lingua'];
                }
            }
            // if there's a best match, it's our man
            if (isset($bestMatch) && strlen($bestMatch) > 0) {
                return $bestMatch;
            }
        }
        /*
         * No supported user language found, return a default language
         */
        //return ADA_LOGIN_PAGE_DEFAULT_LANGUAGE;
        // FIXME: dovrebbe restituire la lingua di default, ora
        // restituisce la prima che trova
        return $ada_supported_languages[0]['codice_lingua'];
    }
}
