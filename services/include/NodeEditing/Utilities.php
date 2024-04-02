<?php

/**
 * Utilities class.
 *
 * @package
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Services\NodeEditing;

class Utilities
{
    public static function getAdaNodeTypeFromString($type)
    {
        switch ($type) {
            case 'LEAF':
                return ADA_LEAF_TYPE;
            case 'GROUP':
                return ADA_GROUP_TYPE;
            case 'WORD':
                return ADA_LEAF_WORD_TYPE;
            case 'GROUP_WORD':
                return ADA_GROUP_WORD_TYPE;
            case 'NOTE':
                return ADA_NOTE_TYPE;
            case 'PRIVATE_NOTE':
                return ADA_PRIVATE_NOTE_TYPE;
            case 'WORD':
                return ADA_LEAF_WORD_TYPE;
        }
    }

    public static function getFileHintFromADAFileType($type)
    {
        $hint = '';
        switch ($type) {
            case _IMAGE:
            case _MONTESSORI:
                $hint = '[' . translateFN("IMMAGINE") . ']';
                break;
            case _SOUND:
            case _PRONOUNCE:
                $hint = '[' . translateFN("AUDIO") . ']';
                break;
            case _VIDEO:
            case _FINGER_SPELLING:
            case _LABIALE:
            case _LIS:
                $hint = '[' . translateFN("VIDEO") . ']';
                break;
            case _LINK:
                $hint = '[' . translateFN("LINK ESTERNO") . ']';
                break;
            case _DOC:
                $hint = '[' . translateFN("DOCUMENTO") . ']';
                break;
        }
        return $hint;
    }

    public static function getIconForNodeType($type)
    {
        switch ($type) {
            case ADA_LEAF_TYPE:
                return 'nodo.png';
            case ADA_LEAF_WORD_TYPE:
                return 'nodo_word.png';
            case ADA_GROUP_WORD_TYPE:
                return 'gruppo_word.png';
            case ADA_GROUP_TYPE:
                return 'gruppo.png';
            case ADA_NOTE_TYPE:
            case ADA_PRIVATE_NOTE_TYPE:
                return 'nota.png';
            case ADA_LEAF_WORD_TYPE:
                return 'nodo_word.png';
            case ADA_GROUP_WORD_TYPE:
                return 'gruppo_word.png';
        }
    }

    public static function getEditingFormTitleForNodeType($type)
    {
        switch ($type) {
            case ADA_LEAF_TYPE:
            case ADA_GROUP_TYPE:
                return translateFN('Aggiunta di un nodo');
            case ADA_LEAF_WORD_TYPE:
            case ADA_GROUP_WORD_TYPE:
                return translateFN('Aggiunta di un termine');
            case ADA_NOTE_TYPE:
                return translateFN('Aggiunta di una nota di classe');
            case ADA_PRIVATE_NOTE_TYPE:
                return translateFN('Aggiunta di una nota privata');
        }
    }
}
