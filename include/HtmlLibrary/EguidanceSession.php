<?php

use Lynxlab\ADA\Main\Output\Output;

use Lynxlab\ADA\Main\HtmlLibrary\EguidanceSession;

use function \translateFN;

// Trigger: ClassWithNameSpace. The class EguidanceSession was declared with namespace Lynxlab\ADA\Main\HtmlLibrary. //

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

use function Lynxlab\ADA\Main\Output\Functions\translateFN;

class EguidanceSession
{
    private static $labels = [
    'area_pc'     => "Sfera delle condizioni personali dell'utente",

    'ud_title'    => 'Criticità dal punto di vista socio-anagrafico verso una situazione lavorativa e/o formativa',
    'ud_1'        => 'Data di Nascita',
    'ud_2'        => 'Sesso',
    'ud_3'        => 'Cultura straniera',
    'ud_4'        => 'Comune o stato estero di nascita',
    'ud_5'        => 'Provincia di nascita',
    'ud_comments' => "I vostri commenti sulle caratteristiche critiche dell'utente dal punto di vista socio-anagrafico",

      'sl_1'        => 'Colloquio informativo - utente nazionale',
    'sl_2'        => 'Colloquio informativo - utente straniero',
    'sl_3'        => 'Consulenza orientativa individuale - scolastico/formativa',
    'sl_4'        => 'Consulenza orientativa individuale - professionale',
    'sl_5'        => 'Laboratorio di ricerca attiva del lavoro',
    'sl_6'        => 'Bilancio di competenze',
    'sl_7'        => 'Tutorato e accompagnamento al lavoro',

    'toe_title'   => 'Tipologia di intervento di orientamento a distanza',

    'pc_title'    => 'Criticità della sfera personale',
    'pc_1'        => 'Problemi fisici',
    'pc_2'        => 'Mancanza di una rete familiare',
    'pc_3'        => 'Scarsa autonomia',
    'pc_4'        => 'Scarsa cura di sé',
    'pc_5'        => 'Poca capacità di comunicare/interagire con gli altri',
    'pc_6'        => 'Storia personale problematica',
    'pc_comments' => "I vostri commenti sulle caratteristiche critiche personali dell'utente",

    'area_pp'     => "Sfera del progetto professionale e/o formativo/educativo dell'utente",

    'ba_title'    => 'Vincoli/mancanza di disponibilità',
    'ba_1'        => 'Obblighi derivanti da legami familiari/assistenza',
    'ba_2'        => 'Problemi economici urgenti/necessità immediata di lavorare',
    'ba_3'        => 'Vincoli nella gestione del tempo',
    'ba_4'        => 'Vincoli in termini di mobilità',
    'ba_comments' => "I vostri commenti sui punti critici riferiti ai vincoli/mancanza di disponibilità dell'utente",

    't_title'     => 'Criticità in ambito scolastico/formativo',
    't_1'         => 'Poca conoscenza della lingua del paese',
    't_2'         => 'Basso livello scolastico',
    't_3'         => "Scarsa conoscenza dell'inglese o di un'altra seconda lingua",
    't_4'         => 'Scarse conoscenze informatiche',
    't_comments'  => "I vostri commenti sugli aspetti critici dell'istruzione e formazione dell'utente",

    'pe_title'    => 'Criticità in ambito professionale',
    'pe_1'        => 'Difficoltà a mantenere un posto di lavoro',
    'pe_2'        => 'Lunghi periodi di inattività',
    'pe_3'        => 'Esperienze professionali non documentate',
    'pe_comments' => "I vostri commenti sulle esperienze professionali dell'utente",

    'ci_title'    => 'Criticità relative alla capacità di realizzare progetti educativi/formativi o professionali',
    'ci_1'        => 'Poca chiarezza sugli obiettivi professionali ed educativi',
    'ci_2'        => 'Poca consapevolezza dei propri limiti e risorse personali',
    'ci_3'        => 'Poca conoscenza del mercato del lavoro e delle tecniche per una ricerca attiva del lavoro (ossia CV, metodi di ricerca del lavoro, ecc.)',
    'ci_4'        => 'Eccessiva selettività nella ricerca del lavoro',
    'ci_comments' => "I vostri commenti sulle problematicità dell'utente relative alla messa a punto di un progetto scolastico/formativo e/o professionale",

    'm_title'     => 'Motivazione personale',
    'm_1'         => 'Poca "attivazione" (comportamento passivo/scetticismo)',
    'm_2'         => 'Poca disponibilità (resistenza ad accettare proposte)',
    'm_comments'  => "I vostri commenti sulle caratteristiche critiche dell'utente riferite alla sua motivazione",

    'oc_title'    => '',
    'other_comments' => 'Altri particolari commenti',
    ];

    private static $scores = [
    0 => 'Problema non rilevato',
    1 => 'Problema assente',
    2 => 'Problema presente',
    3 => 'Problema chiaramente presente',
    ];

    public static function textLabelForField($field_name)
    {
        if (isset(self::$labels[$field_name])) {
            return translateFN(self::$labels[$field_name]);
        }

        return '';
    }

    public static function textForScore($score)
    {
        if (isset(self::$scores[$score])) {
            return translateFN(self::$scores[$score]);
        }

        return '';
    }

    public static function textForEguidanceType($type)
    {
        $key = 'sl_' . $type;
        if (isset(self::$labels[$key])) {
            return translateFN(self::$labels[$key]);
        }
        return '';
    }

    public static function scoresArray()
    {
        $scoresAr = [];

        foreach (self::$scores as $key => $text) {
            $scoresAr[$key] = translateFN($text);
        }

        return $scoresAr;
    }
}
