<?php

/**
 *
 * @package
 * @author      Stefano Penge <steve@lynxlab.com>
 * @author      Maurizio "Graffio" Mazzoneschi <graffio@lynxlab.com>
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Main\HtmlLibrary;

use Lynxlab\ADA\CORE\html4\CBase;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\AMA\AMADB;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\HtmlLibrary\FormElementCreator;
use Lynxlab\ADA\Module\CollaboraACL\GrantAccessForm;
use Lynxlab\ADA\Module\Login\AbstractLogin;
use Lynxlab\ADA\Switcher\Subscription;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class UserModuleHtmlLib
{
    /*
     * CALLED BY index.php
     */
    public static function loginForm($form_action = HTTP_ROOT_DIR, $supported_languages = [], $login_page_language_code = null, $login_error_message = '')
    {

        $div = CDOMElement::create('div', 'id:login_div');
        $form = CDOMElement::create('form', "id:login_form, name:login_form, method:post, action:$form_action");

        $div_username = CDOMElement::create('div', 'id:username');
        $span_label_uname = CDOMElement::create('span', 'id:label_uname, class:page_text');
        $label_uname      = CDOMElement::create('label', 'for:p_username');
        $label_uname->addChild(new CText(translateFN('Username')));
        $span_label_uname->addChild($label_uname);
        $span_username = CDOMElement::create('span', 'id:span_username, class:page_input');
        $username_input = CDOMElement::create('text', 'id:p_username, name:p_username');
        $span_username->addChild($username_input);

        $div_username->addChild($span_label_uname);
        $div_username->addChild($span_username);

        $div_password = CDOMElement::create('div', 'id:password');
        $span_label_pwd = CDOMElement::create('span', 'id:label_pwd, class:page_text');
        $label_pwd      = CDOMElement::create('label', 'for:p_password');
        $label_pwd->addChild(new CText(translateFN('Password')));
        $span_label_pwd->addChild($label_pwd);
        $span_password = CDOMElement::create('span', 'id:span_password, class:page_input');
        $password_input = CDOMElement::create('password', 'id:p_password, name:p_password');
        $span_password->addChild($password_input);

        $div_password->addChild($span_label_pwd);
        $div_password->addChild($span_password);

        $div_remindme = CDOMElement::create('div', 'id:remindme');
        $span_label_remindme = CDOMElement::create('span', 'id:label_remindme, class:page_text');
        $label_remindme = CDOMElement::create('label', 'for:p_remindme');
        $label_remindme->addChild(new CText(translateFN('Resta collegato')));
        $span_label_remindme->addChild($label_remindme);
        $span_remindme = CDOMElement::create('span', 'id:span_remindme, class:page_input');
        $remindme_input = CDOMElement::create('checkbox', 'id:p_remindme,name:p_remindme,value:1');
        $span_remindme->addChild($remindme_input);
        $div_remindme->addChild($span_remindme);
        $div_remindme->addChild($span_label_remindme);

        $div_select = CDOMElement::create('div', 'id:language_selection');
        $select = CDOMElement::create('select', 'id:p_selected_language, name:p_selected_language');
        foreach ($supported_languages as $language) {
            $option = CDOMElement::create('option', "value:{$language['codice_lingua']}");
            if ($language['codice_lingua'] == $login_page_language_code) {
                $option->setAttribute('selected', 'selected');
            }
            $option->addChild(new CText($language['nome_lingua']));
            $select->addChild($option);
        }
        $div_select->addChild($select);

        $div_submit = CDOMElement::create('div', 'id:login_button');
        if (ModuleLoaderHelper::isLoaded('LOGIN')) {
            // load login providers
            $loginProviders = AbstractLogin::getLoginProviders();
        } else {
            $loginProviders = null;
        }

        if (!AMADB::isError($loginProviders) && is_array($loginProviders) && count($loginProviders) > 0) {
            $submit = CDOMElement::create('div', 'id:loginProviders');
            $form->addChild(CDOMElement::create('hidden', 'id:selectedLoginProvider, name:selectedLoginProvider'));
            $form->addChild(CDOMElement::create('hidden', 'id:selectedLoginProviderID, name:selectedLoginProviderID'));
            // add a DOM element (or html) foreach loginProvider
            foreach ($loginProviders as $providerID => $loginProvider) {
                $className = AbstractLogin::getNamespaceName() . "\\" . $loginProvider;
                if (class_exists($className)) {
                    $loginObject = new $className($providerID);
                    $CDOMElement = $loginObject->getCDOMElement();
                    if (!is_null($CDOMElement)) {
                        $submit->addChild($CDOMElement);
                    } else {
                        $htmlString  = $loginObject->getHtml();
                        if (!is_null($htmlString)) {
                            $submit->addChild(new CText($htmlString));
                        }
                    }
                }
            }
        } else {
            // standard submit button if no MODULES_LOGIN
            $value      = translateFN('Accedi');
            $submit     = CDOMElement::create('submit', "id:p_login, name:p_login");
            $submit->setAttribute('value', $value);
        }

        $div_submit->addChild($submit);

        $form->addChild($div_username);
        $form->addChild($div_password);
        $form->addChild($div_remindme);
        $form->addChild($div_select);

        if ($login_error_message != '') {
            $div_error_message = CDOMElement::create('div', 'id:login_error_message, class:error');
            $div_error_message->addChild(new CText($login_error_message));
            $form->addChild($div_error_message);
        }
        $form->addChild($div_submit);

        if (isset($_REQUEST['r']) && strlen(trim($_REQUEST['r'])) > 0) {
            $form->addChild(CDOMElement::create('hidden', 'name:r,value:' . trim($_REQUEST['r'])));
        }

        $div->addChild($form);
        return $div;
    }

    public static function uploadForm($action, $id_user, $id_course, $id_course_instance, $id_node, $error_message = null)
    {

        $div  = CDOMElement::create('div', 'class:fform form ui');

        if ($error_message !== null) {
            $div_error = CDOMElement::create('div', 'class:error_field');
            $div_error->addChild(new CText($error_message));
            $div->addChild($div_error);
        }

        $form = CDOMElement::create('form', "id:upload_form, name: upload_form, action:$action, method:post, class:ui form");
        $form->setAttribute('onsubmit', 'return checkNec();');
        $form->setAttribute('enctype', 'multipart/form-data');

        $sender = CDOMElement::create('hidden', "id:sender, name:sender, value:$id_user");
        $id_course = CDOMElement::create('hidden', "id:id_course, name:id_course, value:$id_course");
        $Hid_course_instance = CDOMElement::create('hidden', "id:id_course_instance, name:id_course_instance, value:$id_course_instance");
        $id_node = CDOMElement::create('hidden', "id:id_node, name:id_node, value:$id_node");

        $input_file    = CDOMElement::create('file', 'id:file_up, name:file_up');
        $table_data = [
        [translateFN('File da inviare'), $input_file],
        ];
        /*
        $copyright_yes = CDOMElement::create('radio','id:copyright, name:copyright, value:1');
        $copyright_no  = CDOMElement::create('radio','id:copyright, name:copyright, value:0');
        $div_copyright = CDOMElement::create('div');
        $div_copyright->addChild($copyright_yes);
        $div_copyright->addChild(new CText(translateFN('Si')));
        $div_copyright->addChild($copyright_no);
        $div_copyright->addChild(new CText(translateFN('No')));
        */
        if (defined('MODULES_COLLABORAACL') &&  MODULES_COLLABORAACL && array_key_exists('userObj', $GLOBALS)) {
            // use the userObj global
            if ($GLOBALS['userObj']->getType() == AMA_TYPE_TUTOR) {
                $users = array_map(fn ($s) => [
                'id' => $s->getSubscriberId(),
                'nome' => $s->getSubscriberFirstname(),
                'cognome' => $s->getSubscriberLastname(),
                'granted' => false,
                ], Subscription::findSubscriptionsToClassRoom($id_course_instance));
            } else {
                // build the tutors list
                $users = array_map(fn ($tutor_id) => ['id' => $tutor_id, 'granted' => false ] + $GLOBALS['dh']->getTutor($tutor_id), $GLOBALS['dh']->courseInstanceTutorGet($id_course_instance, 'ALL'));
            }
            // sort by lastname asc
            usort($users, fn ($a, $b) => strcasecmp($a['cognome'], $b['cognome']));
            // build the grantaccess form
            $grantAccess = new GrantAccessForm('grantaccess', null, [
            'allUsers' => $users,
            'isTutor' => $GLOBALS['userObj']->getType() == AMA_TYPE_TUTOR,
            ]);
            // build a container and add form controls
            $grantAccessDiv = CDOMElement::create('div', 'class:grantaccess-container');
            foreach ($grantAccess->getControls() as $c) {
                if ($c instanceof CBase) {
                    $grantAccessDiv->addChild($c);
                }
            }
            $table_data[] = [translateFN('Utenti con cui condividerlo'), $grantAccessDiv];
        }

        $submit_text = translateFN('Invia');
        $submit = CDOMElement::create('submit', "id:submit, class:ui green submit button, name:submit, value:$submit_text");
        $reset  = CDOMElement::create('reset', 'id:reset, class:ui red reset button, name:reset');
        $buttons_div = CDOMElement::create('div', 'class:ui center aligned basic segment');
        $buttons_div->addChild($submit);
        $buttons_div->addChild($reset);

        //<div id='cfl' title='sender,id_course,id_course_instance,id_node'>

        $form->addChild($sender);
        $form->addChild($id_course);
        $form->addChild($Hid_course_instance);
        $form->addChild($id_node);

        // $table_data[] = array($buttons_div, null);

        $form->addChild(BaseHtmlLib::tableElement('class:upload', null, $table_data));
        $form->addChild($buttons_div);

        $div->addChild($form);
        return $div;
    }

    public static function getExternalLinkNavigationFrame($address)
    {
        $iframe = CDOMElement::create('iframe');
        $iframe->setAttribute('src', $address);
        $iframe->setAttribute('id', 'external_link_browsing');
        return $iframe;
    }
}
