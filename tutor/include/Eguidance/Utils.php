<?php

use Lynxlab\ADA\Module\EtherpadIntegration\Utils;

use Lynxlab\ADA\Main\Output\Output;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class Utils was declared with namespace Lynxlab\ADA\Tutor\Eguidance. //

namespace Lynxlab\ADA\Tutor\Eguidance;

use Lynxlab\ADA\Main\HtmlLibrary\EguidanceSession;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

/**
 * e-guidance tutor utils class.
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

class Utils
{
    private const ADACSVDELIMITER = "\t";
    private const ADACSVNEWLINE = "\n";

    public static function createCSVFileToDownload($form_dataAr = [])
    {

        if (!isset($form_dataAr['user_fc']) || empty($form_dataAr['user_fc'])) {
            $fiscal_code = translateFN("L'utente non ha fornito il codice fiscale");
        } else {
            $fiscal_code = $form_dataAr['user_fc'];
        }

        $scoresAr = [
            0 => '0 - ' . EguidanceSession::textForScore(0),
            1 => '1 - ' . EguidanceSession::textForScore(1),
            2 => '2 - ' . EguidanceSession::textForScore(2),
            3 => '3 - ' . EguidanceSession::textForScore(3),
        ];

        $typeAr = [
            1 => EguidanceSession::textLabelForField('sl_1'),
            2 => EguidanceSession::textLabelForField('sl_2'),
            3 => EguidanceSession::textLabelForField('sl_3'),
            4 => EguidanceSession::textLabelForField('sl_4'),
            5 => EguidanceSession::textLabelForField('sl_5'),
            6 => EguidanceSession::textLabelForField('sl_6'),
            7 => EguidanceSession::textLabelForField('sl_7'),
        ];

        $t_of_guidance = (int)$form_dataAr['type_of_guidance'];
        $type_of_guidance = $typeAr[$t_of_guidance];

        /*
   * CSA total
   */
        $user_fullname    = $form_dataAr['user_fullname'];
        $user_country     = $form_dataAr['user_country'];
        $service_duration = $form_dataAr['service_duration'];
        $ud_1 = $form_dataAr['ud_1'];
        $ud_2 = $form_dataAr['ud_2'];
        $ud_3 = $form_dataAr['ud_3'];

        //$csa_total =  (int)$form_dataAr['csa_1_score'] + (int)$form_dataAr['csa_2_score'] + (int)$form_dataAr['csa_3_score'];
        $csa_comments = $form_dataAr['ud_comments'];

        $pcitems1 = (int)$form_dataAr['pc_1'];
        $pcitems2 = (int)$form_dataAr['pc_2'];
        $pcitems3 = (int)$form_dataAr['pc_3'];
        $pcitems4 = (int)$form_dataAr['pc_4'];
        $pcitems5 = (int)$form_dataAr['pc_5'];
        $pcitems6 = (int)$form_dataAr['pc_6'];
        $pcitems_total    = $pcitems1 + $pcitems2 + $pcitems3 + $pcitems4 + $pcitems5 + $pcitems6;
        $pcitems_comments = $form_dataAr['pc_comments'];

        $ba1 = (int)$form_dataAr['ba_1'];
        $ba2 = (int)$form_dataAr['ba_2'];
        $ba3 = (int)$form_dataAr['ba_3'];
        $ba4 = (int)$form_dataAr['ba_4'];
        $ba_total = $ba1 + $ba2 + $ba3 + $ba4;
        $ba_comments = $form_dataAr['ba_comments'];

        $t1 = (int)$form_dataAr['t_1'];
        $t2 = (int)$form_dataAr['t_2'];
        $t3 = (int)$form_dataAr['t_3'];
        $t4 = (int)$form_dataAr['t_4'];
        $t_total = $t1 + $t2 + $t3 + $t4;
        $t_comments = $form_dataAr['t_comments'];

        $pe1 = (int)$form_dataAr['pe_1'];
        $pe2 = (int)$form_dataAr['pe_2'];
        $pe3 = (int)$form_dataAr['pe_3'];
        $pe_total    = $pe1 + $pe2 + $pe3;
        $pe_comments = $form_dataAr['pe_comments'];

        $ci1 = (int)$form_dataAr['ci_1'];
        $ci2 = (int)$form_dataAr['ci_2'];
        $ci3 = (int)$form_dataAr['ci_3'];
        $ci4 = (int)$form_dataAr['ci_4'];
        $ci_total = $ci1 + $ci2 + $ci3 + $ci4;
        $ci_comments = $form_dataAr['ci_comments'];

        $m1 = (int)$form_dataAr['m_1'];
        $m2 = (int)$form_dataAr['m_2'];
        $m_total = $m1 + $m2;
        $m_comments = $form_dataAr['m_comments'];

        $oc_comments = $form_dataAr['other_comments'];

        $dataAr = [
            [translateFN("Numero di codice fiscale/passaporto"), translateFN("Tipologia di intervento di orientamento a distanza")],
            [$fiscal_code, $type_of_guidance],
            [translateFN("Nome e cognome dell'utente")],
            [$user_fullname],
            [translateFN("Nazionalità dell'utente")],
            [$user_country],
            [translateFN("Durata totale del vostro percorso di orientamento")],
            [$service_duration],
            [translateFN('Caratteristiche utente'), translateFN('Monitoraggio del percorso di e-guidance')],
            ['', translateFN('Prima sessione di orientamento a distanza'), translateFN('Sessioni di orientamento a distanza successive alla prima'), translateFN('Ultima sessione di orientamento a distanza')],
            [EguidanceSession::textLabelForField('area_pc')],

            [EguidanceSession::textLabelForField('ud_1'), $ud_1],
            [EguidanceSession::textLabelForField('ud_2'), $ud_2],
            [EguidanceSession::textLabelForField('ud_3'), $ud_3],
            // array(translateFN('Totale'), $csa_total),  // COME SI FA A DARE UN PUNTEGGIO QUI?
            [EguidanceSession::textLabelForField('ud_comments'), $csa_comments],

            [EguidanceSession::textLabelForField('pc_title')],
            [EguidanceSession::textLabelForField('pc_1'), $scoresAr[$pcitems1]],
            [EguidanceSession::textLabelForField('pc_2'), $scoresAr[$pcitems2]],
            [EguidanceSession::textLabelForField('pc_3'), $scoresAr[$pcitems3]],
            [EguidanceSession::textLabelForField('pc_4'), $scoresAr[$pcitems4]],
            [EguidanceSession::textLabelForField('pc_5'), $scoresAr[$pcitems5]],
            [EguidanceSession::textLabelForField('pc_6'), $scoresAr[$pcitems6]],
            [translateFN('Totale'), $pcitems_total],
            [EguidanceSession::textLabelForField('pc_comments'), $pcitems_comments],

            [EguidanceSession::textLabelForField('area_pp')],
            [EguidanceSession::textLabelForField('ba_title')],
            [EguidanceSession::textLabelForField('ba_1'), $scoresAr[$ba1]],
            [EguidanceSession::textLabelForField('ba_2'), $scoresAr[$ba2]],
            [EguidanceSession::textLabelForField('ba_3'), $scoresAr[$ba3]],
            [EguidanceSession::textLabelForField('ba_4'), $scoresAr[$ba4]],
            [translateFN('Totale'), $ba_total],
            [EguidanceSession::textLabelForField('ba_comments'), $ba_comments],

            [EguidanceSession::textLabelForField('t_title')],
            [EguidanceSession::textLabelForField('t_1'), $scoresAr[$t1]],
            [EguidanceSession::textLabelForField('t_2'), $scoresAr[$t2]],
            [EguidanceSession::textLabelForField('t_3'), $scoresAr[$t3]],
            [EguidanceSession::textLabelForField('t_4'), $scoresAr[$t4]],
            [translateFN('Totale'), $t_total],
            [EguidanceSession::textLabelForField('t_comments'), $t_comments],

            [EguidanceSession::textLabelForField('pe_title')],
            [EguidanceSession::textLabelForField('pe_1'), $scoresAr[$pe1]],
            [EguidanceSession::textLabelForField('pe_2'), $scoresAr[$pe2]],
            [EguidanceSession::textLabelForField('pe_3'), $scoresAr[$pe3]],
            [translateFN('Totale'), $pe_total],
            [EguidanceSession::textLabelForField('pe_comments'), $pe_comments],

            [EguidanceSession::textLabelForField('ci_title')],
            [EguidanceSession::textLabelForField('ci_1'), $scoresAr[$ci1]],
            [EguidanceSession::textLabelForField('ci_2'), $scoresAr[$ci2]],
            [EguidanceSession::textLabelForField('ci_3'), $scoresAr[$ci3]],
            [EguidanceSession::textLabelForField('ci_4'), $scoresAr[$ci4]],
            [translateFN('Totale'), $ci_total],
            [EguidanceSession::textLabelForField('ci_comments'), $ci_comments],

            [EguidanceSession::textLabelForField('m_title')],
            [EguidanceSession::textLabelForField('m_1'), $scoresAr[$m1]],
            [EguidanceSession::textLabelForField('m_2'), $scoresAr[$m2]],
            [translateFN('Totale'), $m_total],
            [EguidanceSession::textLabelForField('m_comments'), $m_comments],
            [EguidanceSession::textLabelForField('other_comments'), $oc_comments],
        ];

        $file_content = self::createCSVFileContent($dataAr);

        header("Cache-Control: public");
        header("Content-Description: File Transfer");
        header("Content-Disposition: attachment; filename=outputform.csv");
        header("Content-type: application/csv; charset=UTF-8");

        echo $file_content;
        exit();
    }

    private static function createCSVFileContent($dataAr = [])
    {

        $file_content = "";
        foreach ($dataAr as $row) {
            foreach ($row as $column_in_row) {
                $file_content .= $column_in_row;
                $file_content .= self::ADACSVDELIMITER;
            }
            $file_content .= self::ADACSVNEWLINE;
        }
        return $file_content;
    }
}
