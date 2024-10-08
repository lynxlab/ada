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

use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\DataValidator;
use Lynxlab\ADA\Main\HtmlLibrary\BaseHtmlLib;
use Lynxlab\ADA\Main\HtmlLibrary\EguidanceSession;
use Lynxlab\ADA\Main\User\ADALoggableUser;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class TutorModuleHtmlLib
{
    /*
     * methods used to display forms and data for the eguidance session
     */
    // MARK: methods used to display forms and data for the eguidance session

    public static function getServiceDataTable($service_dataAr)
    {

        // S.nome, S.descrizione, S.livello, S.durata_servizio, S.min_incontri, S.max_incontri, S.durata_max_incontro
        $thead = [translateFN('Service data'), ''];
        $tbody = [
            [translateFN('Name'), $service_dataAr[1]],
            [translateFN('Description'), $service_dataAr[2]],
            [translateFN('Level'), $service_dataAr[3]],
            [translateFN('Duration'), $service_dataAr[4]],
            [translateFN('Min incontri'), $service_dataAr[5]],
            [translateFN('Max incontri'), $service_dataAr[6]],
            [translateFN('Durata max incontro'), $service_dataAr[7]],
        ];
        return BaseHtmlLib::tableElement('', $thead, $tbody);
    }

    public static function getSubscribedUsersList($user_dataAr, $id_course, $id_course_instance)
    {

        $form = CDOMElement::create('form', 'id:pe_subscribed, method:post, action:course_instance_subscribe.php');

        $thead = [
            translateFN('studente'),
            translateFN('iscritto'),
            translateFN('sospeso'),
            translateFN('in visita'),
            translateFN('cancellato'),
            translateFN('data iscrizione'),
        ];
        $tbody = [];
        foreach ($user_dataAr as $user) {
            $user_id = $user['id_utente'];

            $subscribed = CDOMElement::create('radio', "name:student[$user_id] value:" . ADA_STATUS_SUBSCRIBED);
            if ($user['status'] == ADA_STATUS_SUBSCRIBED) {
                $subscribed->setAttribute('checked', 'true');
            }
            $suspended = CDOMElement::create('radio', "name:student[$user_id] value:" . ADA_STATUS_PRESUBSCRIBED);
            if ($user['status'] == ADA_STATUS_PRESUBSCRIBED) {
                $suspended->setAttribute('checked', 'true');
            }
            $visiting = CDOMElement::create('radio', "name:student[$user_id] value:" . ADA_STATUS_VISITOR);
            if ($user['status'] == ADA_STATUS_VISITOR) {
                $visiting->setAttribute('checked', 'true');
            }
            $removed = CDOMElement::create('radio', "name:student[$user_id] value:" . ADA_STATUS_REMOVED);
            if ($user['status'] == ADA_STATUS_REMOVED) {
                $removed->setAttribute('checked', 'true');
            }


            $tbody[] = [
                $user['nome'] . ' ' . $user['cognome'],
                $subscribed,
                $suspended,
                $visiting,
                $removed,
                '',
            ];
        }

        $table = BaseHtmlLib::tableElement('', $thead, $tbody);

        $form->addChild($table);
        $form->addChild(CDOMElement::create('hidden', 'name: id_course, value:' . $id_course));
        $form->addChild(CDOMElement::create('hidden', 'name: id_course_instance, value:' . $id_course_instance));
        $submit = CDOMElement::create('submit', 'id:subscribed, name:subscribed');
        $form->addChild($submit);
        return $form;
    }

    public static function getPresubscribedUsersList($user_dataAr, $id_course, $id_course_instance)
    {
        $form = CDOMElement::create('form', 'id:pe_unsubscribed, method:post, action:course_instance_presubscribe.php');

        $thead = [
            translateFN('studente'),
            translateFN('iscrivi'),
            translateFN('rimuovi richiesta'),
            translateFN('data richiesta'),
        ];
        $tbody = [];
        foreach ($user_dataAr as $user) {
            $user_id = $user['id_utente'];

            $subscribe = CDOMElement::create('radio', "name:student[$user_id] value:" . ADA_STATUS_SUBSCRIBED);
            $subscribe->setAttribute('checked', 'true');
            $remove = CDOMElement::create('radio', "name:student[$user_id] value:" . ADA_STATUS_REMOVED);
            $tbody[] = [
                $user['nome'] . ' ' . $user['cognome'],
                $subscribe,
                $remove,
                '',
            ];
        }

        $table = BaseHtmlLib::tableElement('', $thead, $tbody);

        $form->addChild($table);
        $form->addChild(CDOMElement::create('hidden', 'name: id_course, value:' . $id_course));
        $form->addChild(CDOMElement::create('hidden', 'name: id_course_instance, value:' . $id_course_instance));
        $submit = CDOMElement::create('submit', 'id:unsubscribed, name:unsubscribed');

        $form->addChild($submit);
        return $form;
    }

    public static function getClassroomForm($students_Ar, $presubscribed_Ar, $id_course, $id_course_instance)
    {
        $div = CDOMElement::create('div');

        $ol = CDOMElement::create('ol', 'class:pager');
        $subscribed = CDOMElement::create('li', 'id:subscribed');
        $subscribed->addChild(new CText(translateFN('Classe')));
        $subscribed->setAttribute('onclick', "PAGER.showPage('subscribed')");
        $unsubscribed = CDOMElement::create('li', 'id:unsubscribed');
        $unsubscribed->addChild(new CText(translateFN('Preiscrizioni')));
        $unsubscribed->setAttribute('onclick', "PAGER.showPage('unsubscribed')");
        $ol->addChild($subscribed);
        $ol->addChild($unsubscribed);

        $div->addChild($ol);
        $div->addChild(self::getSubscribedUsersList($students_Ar, $id_course, $id_course_instance));
        $div->addChild(self::getPresubscribedUsersList($presubscribed_Ar, $id_course, $id_course_instance));

        return $div;
    }

    private static function getUserSubscriptionStatusText($status)
    {
        switch ($status) {
            case ADA_STATUS_PRESUBSCRIBED:
                return translateFN('preiscritto');
            case ADA_STATUS_REGISTERED:
                return translateFN('registrato');
            case ADA_STATUS_REMOVED:
                return translateFN('cancellato');
            case ADA_STATUS_SUBSCRIBED:
                return translateFN('iscritto');
            case ADA_STATUS_VISITOR:
                return translateFN('in visita');
            default:
                return '';
        }
    }
    public static function getEguidanceSessionUserDataTable(ADALoggableUser $tutoredUserObj)
    {

        $user_fiscal_code = $tutoredUserObj->getFiscalCode();
        if (is_null($user_fiscal_code)) {
            $user_fiscal_code = translateFN("L'utente non ha fornito il codice fiscale");
        }

        $thead = [translateFN("Dati utente"), ''];
        $tbody = [
            [translateFN("Codice fiscale dell'utente"), $user_fiscal_code],
            [translateFN("Nome e cognome dell'utente"), $tutoredUserObj->getFullName()],
            [translateFN("Nazionalità dell'utente"), $tutoredUserObj->getCountry()],
        ];
        return BaseHtmlLib::tableElement('', $thead, $tbody);
    }

    public static function displayEguidanceSessionData(ADALoggableUser $tutoredUserObj, $service_infoAr = [], $eguidance_session_dataAr = [])
    {

        $div = CDOMElement::create('div', 'id:eguidance_data');

        $thead_service_info = [translateFN('Informazioni sul servizio'), ''];
        $field = 'sl_' . $eguidance_session_dataAr['tipo_eguidance'];
        $tbody_service_info = [
            [translateFN('Servizio'), $service_infoAr[1]],
            [translateFN('Livello'), $service_infoAr[3]],
            [EguidanceSession::textLabelForField('toe_title'), EguidanceSession::textLabelForField($field)],
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_service_info, $tbody_service_info));

        $div->addChild(self::getEguidanceSessionUserDataTable($tutoredUserObj));

        $pointsString = translateFN('Punteggio');

        $div->addChild(new CText(EguidanceSession::textLabelForField('area_pc')));

        $thead_ud = [EguidanceSession::textLabelForField('ud_title'), $pointsString];
        $tbody_ud = [
            [EguidanceSession::textLabelForField('ud_1'), EguidanceSession::textForScore($eguidance_session_dataAr['ud_1'])],
            [EguidanceSession::textLabelForField('ud_2'), EguidanceSession::textForScore($eguidance_session_dataAr['ud_2'])],
            [EguidanceSession::textLabelForField('ud_3'), EguidanceSession::textForScore($eguidance_session_dataAr['ud_3'])],
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_ud, $tbody_ud));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('ud_comments'), $eguidance_session_dataAr['ud_comments']));

        $thead_pc = [EguidanceSession::textLabelForField('pc_title'), $pointsString];
        $tbody_pc = [
            [EguidanceSession::textLabelForField('pc_1'), EguidanceSession::textForScore($eguidance_session_dataAr['pc_1'])],
            [EguidanceSession::textLabelForField('pc_2'), EguidanceSession::textForScore($eguidance_session_dataAr['pc_2'])],
            [EguidanceSession::textLabelForField('pc_3'), EguidanceSession::textForScore($eguidance_session_dataAr['pc_3'])],
            [EguidanceSession::textLabelForField('pc_4'), EguidanceSession::textForScore($eguidance_session_dataAr['pc_4'])],
            [EguidanceSession::textLabelForField('pc_5'), EguidanceSession::textForScore($eguidance_session_dataAr['pc_5'])],
            [EguidanceSession::textLabelForField('pc_6'), EguidanceSession::textForScore($eguidance_session_dataAr['pc_6'])],
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_pc, $tbody_pc));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('pc_comments'), $eguidance_session_dataAr['pc_comments']));

        $div->addChild(new CText(EguidanceSession::textLabelForField('area_pp')));

        $thead_ba = [EguidanceSession::textLabelForField('ba_title'), $pointsString];
        $tbody_ba = [
            [EguidanceSession::textLabelForField('ba_1'), EguidanceSession::textForScore($eguidance_session_dataAr['ba_1'])],
            [EguidanceSession::textLabelForField('ba_2'), EguidanceSession::textForScore($eguidance_session_dataAr['ba_2'])],
            [EguidanceSession::textLabelForField('ba_3'), EguidanceSession::textForScore($eguidance_session_dataAr['ba_3'])],
            [EguidanceSession::textLabelForField('ba_4'), EguidanceSession::textForScore($eguidance_session_dataAr['ba_4'])],
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_ba, $tbody_ba));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('ba_comments'), $eguidance_session_dataAr['ba_comments']));

        $thead_t = [EguidanceSession::textLabelForField('t_title'), $pointsString];
        $tbody_t = [
            [EguidanceSession::textLabelForField('t_1'), EguidanceSession::textForScore($eguidance_session_dataAr['t_1'])],
            [EguidanceSession::textLabelForField('t_2'), EguidanceSession::textForScore($eguidance_session_dataAr['t_2'])],
            [EguidanceSession::textLabelForField('t_3'), EguidanceSession::textForScore($eguidance_session_dataAr['t_3'])],
            [EguidanceSession::textLabelForField('t_4'), EguidanceSession::textForScore($eguidance_session_dataAr['t_4'])],
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_t, $tbody_t));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('t_comments'), $eguidance_session_dataAr['t_comments']));

        $thead_pe = [EguidanceSession::textLabelForField('pe_title'), $pointsString];
        $tbody_pe = [
            [EguidanceSession::textLabelForField('pe_1'), EguidanceSession::textForScore($eguidance_session_dataAr['pe_1'])],
            [EguidanceSession::textLabelForField('pe_2'), EguidanceSession::textForScore($eguidance_session_dataAr['pe_2'])],
            [EguidanceSession::textLabelForField('pe_3'), EguidanceSession::textForScore($eguidance_session_dataAr['pe_3'])],
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_pe, $tbody_pe));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('pe_comments'), $eguidance_session_dataAr['pe_comments']));


        $thead_ci = [EguidanceSession::textLabelForField('ci_title'), $pointsString];
        $tbody_ci = [
            [EguidanceSession::textLabelForField('ci_1'), EguidanceSession::textForScore($eguidance_session_dataAr['ci_1'])],
            [EguidanceSession::textLabelForField('ci_2'), EguidanceSession::textForScore($eguidance_session_dataAr['ci_2'])],
            [EguidanceSession::textLabelForField('ci_3'), EguidanceSession::textForScore($eguidance_session_dataAr['ci_3'])],
            [EguidanceSession::textLabelForField('ci_4'), EguidanceSession::textForScore($eguidance_session_dataAr['ci_4'])],
            //  array(EguidanceSession::textLabelForField('ci_comments') , $eguidance_session_dataAr['ci_comments']),
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_ci, $tbody_ci));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('ci_comments'), $eguidance_session_dataAr['ci_comments']));

        $thead_m = [EguidanceSession::textLabelForField('m_title'), $pointsString];
        $tbody_m = [
            [EguidanceSession::textLabelForField('m_1'), EguidanceSession::textForScore($eguidance_session_dataAr['m_1'])],
            [EguidanceSession::textLabelForField('m_2'), EguidanceSession::textForScore($eguidance_session_dataAr['m_2'])],
            //  array(EguidanceSession::textLabelForField('m_comments') , $eguidance_session_dataAr['m_comments']),
        ];
        $div->addChild(BaseHtmlLib::tableElement('', $thead_m, $tbody_m));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('m_comments'), $eguidance_session_dataAr['m_comments']));

        $div->addChild(self::displayTutorCommentsForArea(EguidanceSession::textLabelForField('other_comments'), $eguidance_session_dataAr['other_comments']));

        return $div;
    }

    private static function displayTutorCommentsForArea($label, $text)
    {

        $div_comments = CDOMElement::create('div');
        $div_comments_title = CDOMElement::create('div', 'class:textarea_title');
        $div_comments_title->addChild(new CText($label));
        $div_comments_text = CDOMElement::create('div', 'class:textarea_container');
        $div_comments_text->addChild(new CText($text));
        $div_comments->addChild($div_comments_title);
        $div_comments->addChild($div_comments_text);

        return $div_comments;
    }

    private static function displayTextAreaForTutorComments($name, $label, $form_dataAr = [], $use_existing_data = false)
    {

        $textarea = CDOMElement::create('textarea', "id:$name, name:$name");

        if ($use_existing_data && is_array($form_dataAr) && isset($form_dataAr[$name])) {
            $textarea->addChild(new CText($form_dataAr[$name]));
        } else {
            $textarea->addChild(new CText(translateFN('Inserire i vostri commenti')));
        }

        $div = CDOMElement::create('div');
        $div_textarea_title = CDOMElement::create('div', 'class:textarea_title');
        $div_textarea_title->addChild(new CText($label));
        $div_textarea = CDOMElement::create('div', 'class:textarea_container');
        $div_textarea->addChild($textarea);
        $div->addChild($div_textarea_title);
        $div->addChild($div_textarea);

        return $div;
    }

    public static function getEditEguidanceDataForm(ADALoggableUser $tutoredUserObj, $service_infoAr = [], $form_dataAr = [])
    {
        return self::getEguidanceTutorForm($tutoredUserObj, $service_infoAr, $form_dataAr, true);
    }


    public static function getEguidanceTutorForm(ADALoggableUser $tutoredUserObj, $service_infoAr = [], $form_dataAr = [], $fill_textareas = false)
    {
        $form = CDOMElement::create('form', 'id:eguidance_tutor_form, name: eguidance_tutor_form, action:eguidance_tutor_form.php, method:post');

        $area_personal_conditions = CDOMElement::create('div');
        $area_personal_conditions->addChild(new CText(EguidanceSession::textLabelForField('area_pc')));
        $form->addChild($area_personal_conditions);
        /*
         * Fiscal code
         */
        $user_fiscal_code = $tutoredUserObj->getFiscalCode();
        if (!is_null($user_fiscal_code)) {
            $hidden_fc = CDOMElement::create('hidden', 'id:user_fc, name:user_fc');
            $hidden_fc->setAttribute('value', $user_fiscal_code);
            $form->addChild($hidden_fc);
            $ufc = $user_fiscal_code;
        } else {
            $ufc = translateFN("L'utente non ha fornito il codice fiscale");
        }
        if (isset($form_dataAr['is_popup'])) {
            $hidden_popup = CDOMElement::create('hidden', 'id:is_popup, name:is_popup');
            $hidden_popup->setAttribute('value', '1');
            $form->addChild($hidden_popup);
        }
        /*
         * Hidden user data
         */
        $user_fullname = $tutoredUserObj->nome . ' ' . $tutoredUserObj->cognome;
        $user_country = $tutoredUserObj->getCountry();
        $user_birthdate = $tutoredUserObj->getBirthDate();
        $user_birthcity = $tutoredUserObj->getBirthCity();
        $user_birthprovince = $tutoredUserObj->getBirthProvince();
        $user_gender = $tutoredUserObj->getGender();
        $user_foreign_culture = 'FOREIGN CULTURE';

        if (($id = DataValidator::isUinteger($form_dataAr['id'])) !== false) {
            $hidden_id_eguidance_session  = CDOMElement::create('hidden', 'id:id_eguidance_session, name:id_eguidance_session');
            $hidden_id_eguidance_session->setAttribute('value', $id);
            $form->addChild($hidden_id_eguidance_session);
        }

        $hidden_id_utente  = CDOMElement::create('hidden', 'id:id_utente, name:id_utente');
        $hidden_id_utente->setAttribute('value', $tutoredUserObj->getId());

        $hidden_id_istanza_corso = CDOMElement::create('hidden', 'id:id_istanza_corso, name:id_istanza_corso');
        $hidden_id_istanza_corso->setAttribute('value', $service_infoAr['id_istanza_corso']);

        $hidden_event_token = CDOMElement::create('hidden', 'id:event_token, name:event_token');
        $hidden_event_token->setAttribute('value', $service_infoAr['event_token']);

        $hidden_user_fullname = CDOMElement::create('hidden', 'id:user_fullname, name: user_fullname');
        $hidden_user_fullname->setAttribute('value', $user_fullname);

        $hidden_user_country = CDOMElement::create('hidden', 'id:user_country, name:user_country');
        $hidden_user_country->setAttribute('value', $user_country);
        $hidden_service_duration = CDOMElement::create('hidden', 'id:service_duration, name:service_duration');
        $hidden_service_duration->setAttribute('value', 10);
        $hidden_user_birthdate = CDOMElement::create('hidden', 'id:ud_1, name:ud_1');
        $hidden_user_birthdate->setAttribute('value', $user_birthdate);
        $hidden_user_gender = CDOMElement::create('hidden', 'id:ud_2, name:ud_2');
        $hidden_user_gender->setAttribute('value', $user_gender);
        $hidden_user_foreign_culture = CDOMElement::create('hidden', 'id:ud_3, name:ud_3');
        $hidden_user_foreign_culture->setAttribute('value', $user_foreign_culture);
        $hidden_user_birthcity = CDOMElement::create('hidden', 'id:ud_4, name:ud_4');
        $hidden_user_birthcity->setAttribute('value', $user_birthcity);
        $hidden_user_birthprovince = CDOMElement::create('hidden', 'id:ud_5, name:ud_5');
        $hidden_user_birthprovince->setAttribute('value', $user_birthprovince);

        $form->addChild($hidden_id_utente);
        $form->addChild($hidden_id_istanza_corso);
        $form->addChild($hidden_event_token);
        $form->addChild($hidden_user_fullname);
        $form->addChild($hidden_user_country);
        $form->addChild($hidden_service_duration);
        $form->addChild($hidden_user_birthdate);
        $form->addChild($hidden_user_birthcity);
        $form->addChild($hidden_user_birthprovince);
        $form->addChild($hidden_user_gender);
        $form->addChild($hidden_user_foreign_culture);

        //    $ufc_thead = array(translateFN("Dati utente"),'');
        //    $ufc_tbody = array(
        //      array(translateFN("Codice fiscale dell'utente"), $user_fiscal_code),
        //      array(translateFN("Nome e cognome dell'utente"), $user_ns),
        //      array(translateFN("Nazionalità dell'utente"), $user_country)
        //    );
        //    $ufc_table = BaseHtmlLib::tableElement('', $ufc_thead, $ufc_tbody);
        $ufc_table = self::getEguidanceSessionUserDataTable($tutoredUserObj);
        $form->addChild($ufc_table);

        /*
         * Type of e-guidance action
         */
        if (is_array($service_infoAr) && isset($service_infoAr[3])) {
            $service_level = $service_infoAr[3];
        }
        if ($service_level == 2) {
            $typeAr = [
                1 => EguidanceSession::textLabelForField('sl_1'),
                2 => EguidanceSession::textLabelForField('sl_2'),
            ];
        } elseif ($service_level == 3) {
            $typeAr =  [
                3 => EguidanceSession::textLabelForField('sl_3'),
                4 => EguidanceSession::textLabelForField('sl_4'),
            ];
        } elseif ($service_level == 4) {
            $typeAr =  [
                5 => EguidanceSession::textLabelForField('sl_5'),
                6 => EguidanceSession::textLabelForField('sl_6'),
                7 => EguidanceSession::textLabelForField('sl_7'),
            ];
        } else {
            $typeAr = [];
        }


        //FIXME: qui passo $form_dataAr['tipo_eguidance'], ma dovrei passare $form_dataAr['type_of_guidance']
        $toe_thead = [EguidanceSession::textLabelForField('toe_title')];
        $toe_tbody = [
            [BaseHtmlLib::selectElement2('id:type_of_guidance, name:type_of_guidance', $typeAr, $form_dataAr['tipo_eguidance'])],
        ];
        $toe_table = BaseHtmlLib::tableElement('', $toe_thead, $toe_tbody);
        $form->addChild($toe_table);

        $scoresAr = EguidanceSession::scoresArray();

        //$textarea_default_text = translateFN('Inserire i vostri commenti');

        /*
         * User's features
         */
        // Critical socio anagraphic data

        $ud_1_select = BaseHtmlLib::selectElement2('id:ud_1, name:ud_1', $scoresAr, $form_dataAr['ud_1']);
        $ud_2_select = BaseHtmlLib::selectElement2('id:ud_2, name:ud_2', $scoresAr, $form_dataAr['ud_2']);
        $ud_3_select = BaseHtmlLib::selectElement2('id:ud_3, name:ud_3', $scoresAr, $form_dataAr['ud_3']);
        $ud_4_select = BaseHtmlLib::selectElement2('id:ud_4, name:ud_4', $scoresAr, $form_dataAr['ud_4']);
        $ud_5_select = BaseHtmlLib::selectElement2('id:ud_5, name:ud_5', $scoresAr, $form_dataAr['ud_5']);


        $csa_thead = [EguidanceSession::textLabelForField('ud_title'), ''/*translateFN('Select a score')*/];
        $csa_tbody = [
            [EguidanceSession::textLabelForField('ud_1'), $ud_1_select], //$user_birthdate),
            [EguidanceSession::textLabelForField('ud_4'), $ud_4_select], //$user_birthcity),
            [EguidanceSession::textLabelForField('ud_5'), $ud_5_select], //$user_birthprovince),
            [EguidanceSession::textLabelForField('ud_2'), $ud_2_select], //$user_gender),
            [EguidanceSession::textLabelForField('ud_3'), $ud_3_select], //$user_foreign_culture),
        ];
        $csa_table = BaseHtmlLib::tableElement('', $csa_thead, $csa_tbody);
        $form->addChild($csa_table);

        $label = EguidanceSession::textLabelForField('ud_comments');
        $form->addChild(self::displayTextAreaForTutorComments('ud_comments', $label, $form_dataAr, $fill_textareas));

        // Personal critical items
        $pcitems_1_select = BaseHtmlLib::selectElement2('id:pc_1, name:pc_1', $scoresAr, $form_dataAr['pc_1']);
        $pcitems_2_select = BaseHtmlLib::selectElement2('id:pc_2, name:pc_2', $scoresAr, $form_dataAr['pc_2']);
        $pcitems_3_select = BaseHtmlLib::selectElement2('id:pc_3, name:pc_3', $scoresAr, $form_dataAr['pc_3']);
        $pcitems_4_select = BaseHtmlLib::selectElement2('id:pc_4, name:pc_4', $scoresAr, $form_dataAr['pc_4']);
        $pcitems_5_select = BaseHtmlLib::selectElement2('id:pc_5, name:pc_5', $scoresAr, $form_dataAr['pc_5']);
        $pcitems_6_select = BaseHtmlLib::selectElement2('id:pc_6, name:pc_6', $scoresAr, $form_dataAr['pc_6']);

        $pcitems_thead = [EguidanceSession::textLabelForField('pc_title'), translateFN('Select a score')];
        $pcitems_tbody = [
            [EguidanceSession::textLabelForField('pc_1'), $pcitems_1_select],
            [EguidanceSession::textLabelForField('pc_2'), $pcitems_2_select],
            [EguidanceSession::textLabelForField('pc_3'), $pcitems_3_select],
            [EguidanceSession::textLabelForField('pc_4'), $pcitems_4_select],
            [EguidanceSession::textLabelForField('pc_5'), $pcitems_5_select],
            [EguidanceSession::textLabelForField('pc_6'), $pcitems_6_select],
        ];
        $pcitems_table = BaseHtmlLib::tableElement('', $pcitems_thead, $pcitems_tbody);
        $form->addChild($pcitems_table);

        $label = EguidanceSession::textLabelForField('pc_comments');
        $form->addChild(self::displayTextAreaForTutorComments('pc_comments', $label, $form_dataAr, $fill_textareas));


        $area_of_the_job = CDOMElement::create('div');
        $area_of_the_job->addChild(new CText(EguidanceSession::textLabelForField('area_pp')));
        $form->addChild($area_of_the_job);

        /*
         * Bonds/availability
         */
        $ba_1_select = BaseHtmlLib::selectElement2('id:ba_1, name:ba_1', $scoresAr, $form_dataAr['ba_1']);
        $ba_2_select = BaseHtmlLib::selectElement2('id:ba_2, name:ba_2', $scoresAr, $form_dataAr['ba_2']);
        $ba_3_select = BaseHtmlLib::selectElement2('id:ba_3, name:ba_3', $scoresAr, $form_dataAr['ba_3']);
        $ba_4_select = BaseHtmlLib::selectElement2('id:ba_4, name:ba_4', $scoresAr, $form_dataAr['ba_4']);

        $ba_thead = [EguidanceSession::textLabelForField('ba_title'), translateFN('Select a score')];
        $ba_tbody = [
            [EguidanceSession::textLabelForField('ba_1'), $ba_1_select],
            [EguidanceSession::textLabelForField('ba_2'), $ba_2_select],
            [EguidanceSession::textLabelForField('ba_3'), $ba_3_select],
            [EguidanceSession::textLabelForField('ba_4'), $ba_4_select],
        ];
        $ba_table = BaseHtmlLib::tableElement('', $ba_thead, $ba_tbody);
        $form->addChild($ba_table);

        $label = EguidanceSession::textLabelForField('ba_comments');
        $form->addChild(self::displayTextAreaForTutorComments('ba_comments', $label, $form_dataAr, $fill_textareas));

        /*
         * Training
         */
        $t_1_select = BaseHtmlLib::selectElement2('id:t_1, name:t_1', $scoresAr, $form_dataAr['t_1']);
        $t_2_select = BaseHtmlLib::selectElement2('id:t_2, name:t_2', $scoresAr, $form_dataAr['t_2']);
        $t_3_select = BaseHtmlLib::selectElement2('id:t_3, name:t_3', $scoresAr, $form_dataAr['t_3']);
        $t_4_select = BaseHtmlLib::selectElement2('id:t_4, name:t_4', $scoresAr, $form_dataAr['t_4']);

        $t_thead = [EguidanceSession::textLabelForField('t_title'), translateFN('Select a score')];
        $t_tbody = [
            [EguidanceSession::textLabelForField('t_1'), $t_1_select],
            [EguidanceSession::textLabelForField('t_2'), $t_2_select],
            [EguidanceSession::textLabelForField('t_3'), $t_3_select],
            [EguidanceSession::textLabelForField('t_4'), $t_4_select],
        ];
        $t_table = BaseHtmlLib::tableElement('', $t_thead, $t_tbody);
        $form->addChild($t_table);

        $label = EguidanceSession::textLabelForField('t_comments');
        $form->addChild(self::displayTextAreaForTutorComments('t_comments', $label, $form_dataAr, $fill_textareas));

        /*
         * Professional experiences
         */
        $pe_1_select = BaseHtmlLib::selectElement2('id:pe_1, name:pe_1', $scoresAr, $form_dataAr['pe_1']);
        $pe_2_select = BaseHtmlLib::selectElement2('id:pe_2, name:pe_2', $scoresAr, $form_dataAr['pe_2']);
        $pe_3_select = BaseHtmlLib::selectElement2('id:pe_3, name:pe_3', $scoresAr, $form_dataAr['pe_3']);

        $pe_thead = [EguidanceSession::textLabelForField('pe_title'), translateFN('Select a score')];
        $pe_tbody = [
            [EguidanceSession::textLabelForField('pe_1'), $pe_1_select],
            [EguidanceSession::textLabelForField('pe_2'), $pe_2_select],
            [EguidanceSession::textLabelForField('pe_3'), $pe_3_select],
        ];
        $pe_table = BaseHtmlLib::tableElement('', $pe_thead, $pe_tbody);
        $form->addChild($pe_table);

        $label = EguidanceSession::textLabelForField('pe_comments');
        $form->addChild(self::displayTextAreaForTutorComments('pe_comments', $label, $form_dataAr, $fill_textareas));

        /*
         * Critical issues ...
         */
        $ci_1_select = BaseHtmlLib::selectElement2('id:ci_1, name:ci_1', $scoresAr, $form_dataAr['ci_1']);
        $ci_2_select = BaseHtmlLib::selectElement2('id:ci_2, name:ci_2', $scoresAr, $form_dataAr['ci_2']);
        $ci_3_select = BaseHtmlLib::selectElement2('id:ci_3, name:ci_3', $scoresAr, $form_dataAr['ci_3']);
        $ci_4_select = BaseHtmlLib::selectElement2('id:ci_4, name:ci_4', $scoresAr, $form_dataAr['ci_4']);

        $ci_thead = [EguidanceSession::textLabelForField('ci_title'), translateFN('Select a score')];
        $ci_tbody = [
            [EguidanceSession::textLabelForField('ci_1'), $ci_1_select],
            [EguidanceSession::textLabelForField('ci_2'), $ci_2_select],
            [EguidanceSession::textLabelForField('ci_3'), $ci_3_select],
            [EguidanceSession::textLabelForField('ci_4'), $ci_4_select],
        ];
        $ci_table = BaseHtmlLib::tableElement('', $ci_thead, $ci_tbody);
        $form->addChild($ci_table);

        $label = EguidanceSession::textLabelForField('ci_comments');
        $form->addChild(self::displayTextAreaForTutorComments('ci_comments', $label, $form_dataAr, $fill_textareas));

        /*
         * Motivazione + Other particular comments
         */
        $m_1_select = BaseHtmlLib::selectElement2('id:m_1, name:m_1', $scoresAr, $form_dataAr['m_1']);
        $m_2_select = BaseHtmlLib::selectElement2('id:m_2, name:m_2', $scoresAr, $form_dataAr['m_2']);

        $m_thead = [EguidanceSession::textLabelForField('m_title'), translateFN('Select a score')];
        $m_tbody = [
            [EguidanceSession::textLabelForField('m_1'), $m_1_select],
            [EguidanceSession::textLabelForField('m_2'), $m_2_select],
        ];
        $m_table = BaseHtmlLib::tableElement('', $m_thead, $m_tbody);
        $form->addChild($m_table);

        $label = EguidanceSession::textLabelForField('m_comments');
        $form->addChild(self::displayTextAreaForTutorComments('m_comments', $label, $form_dataAr, $fill_textareas));

        $label = EguidanceSession::textLabelForField('other_comments');
        $form->addChild(self::displayTextAreaForTutorComments('other_comments', $label, $form_dataAr, $fill_textareas));

        /*
          * Form buttons
          */
        $buttons = CDOMElement::create('div', 'id:buttons, name:buttons');
        $submit  = CDOMElement::create('submit', 'id:submit, name:submit');
        $submit->setAttribute('value', translateFN('Save'));
        //    $reset   = CDOMElement::create('reset');
        $buttons->addChild($submit);
        //    $buttons->addChild($reset);
        $form->addChild($buttons);

        return $form;
    }
}
