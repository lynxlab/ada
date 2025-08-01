<?php

/**
 * NodeEditingViewer class.
 *
 * @package
 * @author      Vito Modena <vito@lynxlab.com>
 * @copyright   Copyright (c) 2009, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @link
 * @version     0.1
 */

namespace Lynxlab\ADA\Services\NodeEditing;

use Lynxlab\ADA\Browsing\CourseViewer;
use Lynxlab\ADA\CORE\html4\CDOMElement;
use Lynxlab\ADA\CORE\html4\CText;
use Lynxlab\ADA\Main\Helper\ModuleLoaderHelper;
use Lynxlab\ADA\Main\Translator;
use Lynxlab\ADA\Services\NodeEditing\Utilities;

use function Lynxlab\ADA\Main\Output\Functions\translateFN;
use function Lynxlab\ADA\Services\Functions\getNodeDataFromPost;

/**
 * class NodeEditingViewer, provides all the methods used to generate
 * node editing views such as node editing, preview,...
 */
class NodeEditingViewer
{
    /**
     * function getEditingForm, used to display a text editing form for current node's content.
     *
     * @param string $form_action
     * @param int    $id_course
     * @param int    $id_course_instance
     * @param int    $id_user
     * @param array  $node_to_edit
     * @param int    $flags
     * @return string
     */
    public static function getEditingForm($form_action, $id_course, $id_course_instance, $id_user, $node_to_edit = [], $flags = null)
    {
        // vito, 1 ottobre 2008
        if (isset($node_to_edit['text'])) {
            $node_to_edit_text = $node_to_edit['text'];
        } else {
            $node_to_edit_text = '';
        }

        if ($node_to_edit['type'] == ADA_LEAF_WORD_TYPE or $node_to_edit['type'] == ADA_GROUP_WORD_TYPE) {
            $node_to_edit_hyphenation = $node_to_edit['hyphenation'] ?? null;
            $node_to_edit_semantic = $node_to_edit['semantic'] ?? null;
            $node_to_edit_grammar = $node_to_edit['grammar'] ?? null;
            $node_to_edit_notes = $node_to_edit['notes'] ?? null;
            $node_to_edit_examples = $node_to_edit['examples'] ?? null;
        }

        $editing_form = CDOMElement::create('form', "id:jseditor_form, action:$form_action.php?op=preview, method:post");
        $editing_form->setAttribute('onsubmit', 'updateADACode();');

        //    $editing_form->addChild(self::getNodeDataDiv($flags, $node_to_edit, $id_course));

        /*
         * DIV containing a textarea displaying ADA code for the current node.
        */

        $textarea_div = CDOMElement::create('div', 'id:jstextarea_div');
        if ($flags & EDITOR_ALLOW_SWITCHING_BETWEEN_EDITING_MODES) {
            $switch_to_fckeditor = CDOMElement::create('div', 'id:span_switch_to_fckeditor_button, class:editor_input');
            $input_button = CDOMElement::create('input_button', 'class: ui tiny button');
            $input_button->setAttribute('value', translateFN('Passa ad FCKeditor'));
            $input_button->setAttribute('onclick', 'switchToFCKeditor();');
            $switch_to_fckeditor->addChild($input_button);

            $textarea_div->addChild($switch_to_fckeditor);
        }
        $span_textarea = CDOMElement::create('div', 'id:span_ada_code_textarea,class:editor_input');
        $textarea = CDOMElement::create('textarea', 'id:jsdata_textarea, name:ADACode');
        $textarea->addChild(new CText($node_to_edit_text));
        $span_textarea->addChild($textarea);

        $hidden_input = CDOMElement::create('hidden', 'name:ada_code');
        $textarea_div->addChild($span_textarea);
        $textarea_div->addChild($hidden_input);

        if ($node_to_edit['type'] == ADA_LEAF_WORD_TYPE or $node_to_edit['type'] == ADA_GROUP_WORD_TYPE) {
            // hyphenation
            $hyphenation_label = CDOMElement::create('DIV');
            $hyphenation_label->setAttribute('class', 'label_extended');
            $hyphenation_label->addChild(new CText(translateFN('hyphenation')));

            $span_hyphen_area = CDOMElement::create('div', 'id:span_ada_code_hyphen_area,class:editor_input');
            $hyphenarea = CDOMElement::create('textarea', 'id:jsdata_hyphenarea, name:ADACodeHyphen');
            $hyphenarea->addChild(new CText($node_to_edit_hyphenation));
            $span_hyphen_area->addChild($hyphenarea);

            $hidden_input_hyphen = CDOMElement::create('hidden', 'name:ada_code_hyphen');
            $textarea_div->addChild($hyphenation_label);
            $textarea_div->addChild($span_hyphen_area);
            $textarea_div->addChild($hidden_input_hyphen);

            // semantic
            $semantic_label = CDOMElement::create('DIV');
            $semantic_label->setAttribute('class', 'label_extended');
            $semantic_label->addChild(new CText(translateFN('semantic')));

            $span_semantic_area = CDOMElement::create('div', 'id:span_ada_code_semantic_area,class:editor_input');
            $semanticarea = CDOMElement::create('textarea', 'id:jsdata_semanticarea, name:ADACodeSemantic');
            $semanticarea->addChild(new CText($node_to_edit_semantic));
            $span_semantic_area->addChild($semanticarea);

            $hidden_input_semantic = CDOMElement::create('hidden', 'name:ada_code_semantic');
            $textarea_div->addChild($semantic_label);
            $textarea_div->addChild($span_semantic_area);
            $textarea_div->addChild($hidden_input_semantic);

            // grammar
            $grammar_label = CDOMElement::create('DIV');
            $grammar_label->setAttribute('class', 'label_extended');
            $grammar_label->addChild(new CText(translateFN('grammar')));

            $span_grammar_area = CDOMElement::create('div', 'id:span_ada_code_grammar_area,class:editor_input');
            $grammararea = CDOMElement::create('textarea', 'id:jsdata_grammararea, name:ADACodeGrammar');
            $grammararea->addChild(new CText($node_to_edit_grammar));
            $span_grammar_area->addChild($grammararea);

            $hidden_input_grammar = CDOMElement::create('hidden', 'name:ada_code_grammar');
            $textarea_div->addChild($grammar_label);
            $textarea_div->addChild($span_grammar_area);
            $textarea_div->addChild($hidden_input_grammar);

            // notes
            $notes_label = CDOMElement::create('DIV');
            $notes_label->setAttribute('class', 'label_extended');
            $notes_label->addChild(new CText(translateFN('notes')));

            $span_notes_area = CDOMElement::create('div', 'id:span_ada_code_notes_area,class:editor_input');
            $notesarea = CDOMElement::create('textarea', 'id:jsdata_notesarea, name:ADACodeNotes');
            $notesarea->addChild(new CText($node_to_edit_notes));
            $span_notes_area->addChild($notesarea);

            $hidden_input_notes = CDOMElement::create('hidden', 'name:ada_code_notes');
            $textarea_div->addChild($notes_label);
            $textarea_div->addChild($span_notes_area);
            $textarea_div->addChild($hidden_input_notes);

            // examples
            $examples_label = CDOMElement::create('DIV');
            $examples_label->setAttribute('class', 'label_extended');
            $examples_label->addChild(new CText(translateFN('examples')));

            $span_examples_area = CDOMElement::create('div', 'id:span_ada_code_examples_area,class:editor_input');
            $examplesarea = CDOMElement::create('textarea', 'id:jsdata_examplesarea, name:ADACodeExamples');
            $examplesarea->addChild(new CText($node_to_edit_examples));
            $span_examples_area->addChild($examplesarea);

            $hidden_input_examples = CDOMElement::create('hidden', 'name:ada_code_examples');
            $textarea_div->addChild($examples_label);
            $textarea_div->addChild($span_examples_area);
            $textarea_div->addChild($hidden_input_examples);
        }

        $editing_form->addChild($textarea_div);


        /*
     * DIV containing the FCKeditor integrated with ADA media management
        */
        $fckeditor_div = CDOMElement::create('div', 'id:jsfckeditor_div');
        $fckeditor_div->setAttribute('style', 'display: none');
        if ($flags & EDITOR_ALLOW_SWITCHING_BETWEEN_EDITING_MODES) {
            $switch_to_adacode = CDOMElement::create('div', 'id:span_switch_to_adacode_button, class:editor_input');
            $input_button = CDOMElement::create('input_button', 'id:switch_to_adacode, disabled:disabled, class:ui tiny button');
            $input_button->setAttribute('value', translateFN('Passa al codice del nodo'));
            $input_button->setAttribute('onclick', 'switchToADACode();');
            $switch_to_adacode->addChild($input_button);

            $fckeditor_div->addChild($switch_to_adacode);
        }

        $span_textarea = CDOMElement::create('div', 'id:ada_fckeditor_textarea,class:editor_input');
        $textarea = CDOMElement::create('textarea', 'id:jsdata_fckeditor, name:DataFCKeditor');
        $textarea->addChild(new CText($node_to_edit_text));
        $span_textarea->addChild($textarea);

        $hidden_input = CDOMElement::create('hidden', 'name:fckeditor_code');
        $fckeditor_div->addChild($span_textarea);
        $fckeditor_div->addChild($hidden_input);

        if ($node_to_edit['type'] == ADA_LEAF_WORD_TYPE or $node_to_edit['type'] == ADA_GROUP_WORD_TYPE) {
            // hyphenation
            $hyphenation_label = CDOMElement::create('DIV');
            $hyphenation_label->setAttribute('class', 'label_extended');
            $hyphenation_label->addChild(new CText(translateFN('hyphenation')));

            $span_hyphen_area = CDOMElement::create('div', 'id:ada_fckeditor_hyphen_area,class:editor_input');
            $hyphenarea = CDOMElement::create('textarea', 'id:jsdata_fckeditor_hyphenarea, name:DataFCK_hyphen');
            $hyphenarea->addChild(new CText($node_to_edit_hyphenation));
            $span_hyphen_area->addChild($hyphenarea);

            $hidden_input_hyphen = CDOMElement::create('hidden', 'name:ada_fckeditor_hyphen');
            $fckeditor_div->addChild($hyphenation_label);
            $fckeditor_div->addChild($span_hyphen_area);
            $fckeditor_div->addChild($hidden_input_hyphen);

            // semantic
            $semantic_label = CDOMElement::create('DIV');
            $semantic_label->setAttribute('class', 'label_extended');
            $semantic_label->addChild(new CText(translateFN('semantic')));

            $span_semantic_area = CDOMElement::create('div', 'id:ada_fckeditor_semantic_area,class:editor_input');
            $semanticarea = CDOMElement::create('textarea', 'id:jsdata_fckeditor_semanticarea, name:DataFCK_semantic');
            $semanticarea->addChild(new CText($node_to_edit_semantic));
            $span_semantic_area->addChild($semanticarea);

            $hidden_input_semantic = CDOMElement::create('hidden', 'name:ada_fckeditor_semantic');
            $fckeditor_div->addChild($semantic_label);
            $fckeditor_div->addChild($span_semantic_area);
            $fckeditor_div->addChild($hidden_input_semantic);

            // grammar
            $grammar_label = CDOMElement::create('DIV');
            $grammar_label->setAttribute('class', 'label_extended');
            $grammar_label->addChild(new CText(translateFN('grammar')));

            $span_grammar_area = CDOMElement::create('div', 'id:ada_fckeditor_grammar_area,class:editor_input');
            $grammararea = CDOMElement::create('textarea', 'id:jsdata_fckeditor_grammararea, name:DataFCK_grammar');
            $grammararea->addChild(new CText($node_to_edit_grammar));
            $span_grammar_area->addChild($grammararea);

            $hidden_input_grammar = CDOMElement::create('hidden', 'name:ada_fckeditor_grammar');
            $fckeditor_div->addChild($grammar_label);
            $fckeditor_div->addChild($span_grammar_area);
            $fckeditor_div->addChild($hidden_input_grammar);

            // notes
            $notes_label = CDOMElement::create('DIV');
            $notes_label->setAttribute('class', 'label_extended');
            $notes_label->addChild(new CText(translateFN('notes')));

            $span_notes_area = CDOMElement::create('div', 'id:ada_fckeditor_notes_area,class:editor_input');
            $notesarea = CDOMElement::create('textarea', 'id:jsdata_fckeditor_notesarea, name:DataFCK_notes');
            $notesarea->addChild(new CText($node_to_edit_notes));
            $span_notes_area->addChild($notesarea);

            $hidden_input_notes = CDOMElement::create('hidden', 'name:ada_fckeditor_notes');
            $fckeditor_div->addChild($notes_label);
            $fckeditor_div->addChild($span_notes_area);
            $fckeditor_div->addChild($hidden_input_notes);

            // examples
            $examples_label = CDOMElement::create('DIV');
            $examples_label->setAttribute('class', 'label_extended');
            $examples_label->addChild(new CText(translateFN('examples')));

            $span_examples_area = CDOMElement::create('div', 'id:ada_fckeditor_examples_area,class:editor_input');
            $examplesarea = CDOMElement::create('textarea', 'id:jsdata_fckeditor_examplesarea, name:DataFCK_examples');
            $examplesarea->addChild(new CText($node_to_edit_examples));
            $span_examples_area->addChild($examplesarea);

            $hidden_input_examples = CDOMElement::create('hidden', 'name:ada_fckeditor_examples');
            $fckeditor_div->addChild($examples_label);
            $fckeditor_div->addChild($span_examples_area);
            $fckeditor_div->addChild($hidden_input_examples);
        }


        $editing_form->addChild($fckeditor_div);

        /*
     * Preview button
        */

        $span_preview = CDOMElement::create('div', 'id:span_preview_button,class:editor_input');
        $button       = CDOMElement::create('submit', 'id:preview, name:anteprima,class:ui tiny button');
        $button->setAttribute('value', translateFN('Mostra anteprima'));
        $span_preview->addChild($button);

        //    $editing_form->addChild($span_preview);
        $fckeditor_div->addChild($span_preview);

        $editor_form_div = CDOMElement::create('div', 'id:editor_form_div');
        $editor_form_div->addChild($editing_form);
        //vito, 18 feb 2009
        //    $editor->addChild($editor_form_div);
        //    $editor->addChild(self::getButtons($flags));
        //    $editor->addChild(self::getAddOns($flags, $id_course, $id_course_instance, $id_user, $node_to_edit['id']));

        $edit_panel = CDOMElement::create('div', 'id:editPanel');
        $edit_panel_content = CDOMElement::create('div', 'class:editPanelContent');
        $edit_panel->addChild($edit_panel_content);
        $edit_panel_content->addChild(self::getButtons($flags));
        $edit_panel_content->addChild(self::getAddOns($flags, $id_course, $id_course_instance, $id_user, $node_to_edit['id']));
        $edit_panel_content->addChild(self::getNodeDataDiv($flags, $node_to_edit, $id_course));

        $editing_form->addChild($edit_panel);
        //vito, 18 feb 2009
        //    return $editor;
        return $editor_form_div;
    }

    /**
     * function getPreviewForm, used to display a preview of current node's text content
     *
     * @param  string $form_action
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getPreviewForm($action_return_todoEdit_node, $action_save_edited_node)
    {
        $node_data = getNodeDataFromPost($_POST);
        $_SESSION['sess_node_editing']['node_data'] = serialize($node_data);

        $node_text = $node_data['DataFCKeditor'];

        if ($node_data['type'] == ADA_LEAF_WORD_TYPE or $node_data['type'] == ADA_GROUP_WORD_TYPE) {
            // hyphenation
            $hyphenation_label = CDOMElement::create('DIV');
            $hyphenation_label->setAttribute('class', 'label_extended');
            $hyphenation_label->addChild(new CText(translateFN('hyphenation')));
            $node_hyphenation = $node_data['DataFCK_hyphen'];
            // semantic
            $semantic_label = CDOMElement::create('DIV');
            $semantic_label->setAttribute('class', 'label_extended');
            $semantic_label->addChild(new CText(translateFN('semantic')));
            $node_semantic = $node_data['DataFCK_semantic'];
            // grammar
            $grammar_label = CDOMElement::create('DIV');
            $grammar_label->setAttribute('class', 'label_extended');
            $grammar_label->addChild(new CText(translateFN('grammar')));
            $node_grammar = $node_data['DataFCK_grammar'];
            // notes
            $notes_label = CDOMElement::create('DIV');
            $notes_label->setAttribute('class', 'label_extended');
            $notes_label->addChild(new CText(translateFN('notes')));
            $node_notes = $node_data['DataFCK_notes'];
            // examples
            $examples_label = CDOMElement::create('DIV');
            $examples_label->setAttribute('class', 'label_extended');
            $examples_label->addChild(new CText(translateFN('examples')));
            $node_examples = $node_data['DataFCK_examples'];
        }


        $node_text_div  = CDOMElement::create('div', 'id:node_text, class:editor_text');
        $node_text_div->addChild(new CText($node_text));

        if ($node_data['type'] == ADA_LEAF_WORD_TYPE or $node_data['type'] == ADA_GROUP_WORD_TYPE) {
            $node_text_div->addChild($hyphenation_label);
            $node_text_div->addChild(new CText($node_hyphenation));
            $node_text_div->addChild($semantic_label);
            $node_text_div->addChild(new CText($node_semantic));
            $node_text_div->addChild($grammar_label);
            $node_text_div->addChild(new CText($node_grammar));
            $node_text_div->addChild($notes_label);
            $node_text_div->addChild(new CText($node_notes));
            $node_text_div->addChild($examples_label);
            $node_text_div->addChild(new CText($node_examples));
        }
        /*
    $div_buttons = CDOMElement::create('div','id:buttons');
    $edit_button = CDOMElement::create('span','id:node_preview_edit_button');
    $edit_link   = CDOMElement::create('a',"href:$action_return_todoEdit_node");
    $edit_link->addChild(new CText(translateFN('Modifica')));
    $edit_button->addChild($edit_link);
    $div_buttons->addChild($edit_button);

    $save_button = CDOMElement::create('span','id:node_preview_save_button');
    $save_link   = CDOMElement::create('a',"href:$action_save_edited_node");
    $save_link->addChild(new CText(translateFN('Salva')));
    $save_button->addChild($save_link);
    $div_buttons->addChild($save_button);
    $node_text_div->addChild($div_buttons);
        */
        return $node_text_div;
    }

    public function getEditLink($edit_link)
    {
        $link = CDOMElement::create('a', "href:$edit_link");
        $link->addChild(new CText(translateFN('Modifica')));
        return $link->getHtml();
    }

    public function getSaveLink($save_link)
    {
        $link = CDOMElement::create('a', "href:$save_link");
        $link->addChild(new CText(translateFN('Salva')));
        return $link->getHtml();
    }

    public static function getHeadForm($id_user, $user_level, $user_type, $parentNodeObj, $new_node_id, $new_node_type)
    {
        // NOTE or PRIVATE_NOTE
        $replied_node_data = CDOMElement::create('div', 'id:replied_node_data');
        if ($new_node_type == ADA_NOTE_TYPE || $new_node_type == ADA_PRIVATE_NOTE_TYPE) {
            $author_name = CDOMElement::create('div', 'id:author_name');

            if ($parentNodeObj->author['id'] == $id_user) {
                $author_name->addChild(new CText(translateFN('Tu hai scritto:')));
            } else {
                //  $author_name = $parentNodeObj->author['username']." ".translateFN("ha scritto:");
                $author_name->addChild(new CText(sprintf(translateFN("%s ha scritto: "), $parentNodeObj->author['username'])));
            }

            $replied_node_data->addChild($author_name);

            //  $head_form .= "<b>$parentNodeObj->creation_date {$parentNodeObj->author['username']} </b><br><cite>". strip_tags($parentNodeObj->text,"<br>")."</cite><br><hr>";
            $replied_text = CDOMElement::create('div', 'id:replied_text');
            //$replied_text->addChild(new CText($parentNodeObj->getTextFN($user_level,'')));
            $node_data = $parentNodeObj->filterNodeFN($user_level, null, $user_type, '');
            $replied_text->addChild(new CText($parentNodeObj->text));
            //            $replied_text->addChild(new CText($node_data['text']));
            $replied_node_data->addChild($replied_text);
        }
        //LEAF && *NOTE
        //$head_form .= translateFN('Id del nuovo nodo') .":<B> $new_node_id</B>";
        //return $head_form;
        return $replied_node_data->getHtml();
    }
    /*
   * PRIVATE METHODS
    */
    /**
     * function getNodeDataDiv,
     *
     * @param int   $flags     - a bitmap used to store preferences based on user type, node type, operation on node.
     * @param array $node_data - an associative array containing node data
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getNodeDataDiv($flags, $node_data = [], $id_course = null)
    {
        $php_file_uploader = 'upload.php?caller=editor';
        $node_data_div = CDOMElement::create('div', 'id:jsnode_data_div');

        if (is_null($id_course)) {
            return $node_data_div;
        }

        $node_title = CDOMElement::create('div', 'id:show_node_title, class:editor_input');
        $label      = CDOMElement::create('label', 'for:name');
        $label->addChild(new CText(translateFN('Titolo')));
        $input_text = CDOMElement::create('text', "id:name, name:name");
        $input_text->setAttribute('value', $node_data['name']);
        $node_title->addChild($label);
        $node_title->addChild($input_text);

        $node_data_div->addChild($node_title);

        $node_keywords = CDOMElement::create('div', 'id:show_node_keywords, class:editor_input');
        $label      = CDOMElement::create('label', 'for:title');
        $label->addChild(new CText(translateFN('Keywords')));
        $input_text = CDOMElement::create('text', 'id:title, name:title');
        $input_text->setAttribute('value', $node_data['title']);
        $node_keywords->addChild($label);
        $node_keywords->addChild($input_text);

        $node_data_div->addChild($node_keywords);

        if ($flags & EDITOR_SHOW_NODE_LEVEL) {
            $node_level = CDOMElement::create('div', 'id:show_node_level, class:editor_input');
            $label      = CDOMElement::create('label', 'for:level');
            $label->addChild(new CText(translateFN('Livello')));
            $input_text = CDOMElement::create('text', 'id:level, name:level');
            $input_text->setAttribute('value', $node_data['level']);
            $node_level->addChild($label);
            $node_level->addChild($input_text);

            $node_data_div->addChild($node_level);
        } else {
            $hidden_level = CDOMElement::create('hidden', 'id:level, name:level');
            $hidden_level->setAttribute('value', $node_data['level']);
            $node_data_div->addChild($hidden_level);
        }

        if ($flags & EDITOR_SHOW_NODE_ICON) {
            $node_icon = CDOMElement::create('div', 'id:show_node_icon, class:editor_input');
            $label      = CDOMElement::create('label', 'for:icon');
            $label->addChild(new CText(translateFN('Icona')));
            /*
            $input_text = CDOMElement::create('text','id:icon, name:icon');
            $input_text->setAttribute('value', $node_data['icon']);
             *
             */
            $node_icon->addChild($label);
            //            $node_icon->addChild($input_text);

            $media_type = [_IMAGE, _MONTESSORI];
            $select_icon = self::getAuthorMediaOnlySelector($id_course, $media_type, 'icon', $node_data);
            //            $select_icon->addChild($options);

            $node_icon->addChild($select_icon);


            /*
             *      VERSIONE CHE CONSENTE L'UPLOAD DELL'ICON
                        if ( $flags & EDITOR_UPLOAD_FILE ) {
                            $div_error_icon_upload = CDOMElement::create('div', 'id:jserror_icon_upload');
                            $form_icon = CDOMElement::create('form',"id:uploadfileicon, name:uploadfileformicon, enctype:multipart/form-data,
                      action:$php_file_uploader, method:post");
                            $form_icon->setAttribute('onclick','enterUploadFileState();');

                            $span_icon = CDOMElement::create('div','id:span_upload_icon_input, class:editor_input');
                            $input_icon = CDOMElement::create('file','id:id_icon, name:icon_up');
                            $span_icon->addChild($input_icon);
                            $form_icon->addChild($span_icon);

                            $input_hidden_course_id = CDOMElement::create('hidden',"name:course_id, value:$id_course");
                            $input_hidden_id_course_instance = CDOMElement::create('hidden',"name:course_instance_id, value:$id_course_instance");
                            $input_hidden_user_id = CDOMElement::create('hidden',"name:user_id, value:$id_user");
                            $input_hidden_node_id = CDOMElement::create('hidden',"name:node_id, value:$id_node");

                            $span_button_icon = CDOMElement::create('div','id:span_upload_icon_button, class:editor_input');
                            $input_button_icon = CDOMElement::create('submit');
                            $input_button_icon->setAttribute('value', translateFN("Invia questo file"));
                            $span_button_icon->addChild($input_button_icon);

                            $form_icon->addChild($input_hidden_course_id);
                            $form_icon->addChild($input_hidden_id_course_instance);
                            $form_icon->addChild($input_hidden_node_id);
                            $form_icon->addChild($input_hidden_user_id);
                            $form_icon->addChild($span_button_icon);
                            //$form_icon->addChild($iframe);

                            //$div_fu->addChild($div_error_file_upload);
                            //$div_fu->addChild($form);
                            $node_icon->addChild($form_icon);

                            }
             *
             */
            $node_data_div->addChild($node_icon);
        } else {
            $hidden_icon = CDOMElement::create('hidden', 'id:icon, name:icon');
            $hidden_icon->setAttribute('value', $node_data['icon']);
            $node_data_div->addChild($hidden_icon);
        }

        if ($flags & EDITOR_SHOW_NODE_BGCOLOR) {
            $node_bgcolor = CDOMElement::create('div', 'id:show_node_icon, class:editor_input');
            $label      = CDOMElement::create('label', 'for:bg_color');
            $label->addChild(new CText(translateFN('Colore sfondo')));
            $input_text = CDOMElement::create('text', 'id:bg_color, name:bg_color');
            $input_text->setAttribute('value', $node_data['bg_color'] ?? null);
            $node_bgcolor->addChild($label);
            $node_bgcolor->addChild($input_text);

            $node_data_div->addChild($node_bgcolor);
        } else {
            $hidden_bgcolor = CDOMElement::create('hidden', 'id:bg_color, name:bg_color');
            $hidden_bgcolor->setAttribute('value', $node_data['bg_color'] ?? null);
            $node_data_div->addChild($hidden_bgcolor);
        }

        if ($flags & EDITOR_SHOW_NODE_TYPE) {
            $span_select = CDOMElement::create('div', 'id:show_node_type, class:editor_input');
            $label_type      = CDOMElement::create('label', 'for:type');
            $label_type->addChild(new CText(translateFN('Tipo nodo')));
            /*
            $input_text = CDOMElement::create('text','id:icon, name:icon');
            $input_text->setAttribute('value', $node_data['icon']);
             *
             */
            $span_select->addChild($label_type);

            $select = CDOMElement::create('select', 'id:type, name:type');

            $option_group = CDOMElement::create('option');
            $option_group->setAttribute('value', ADA_GROUP_TYPE);
            $option_group->addChild(new CText(translateFN("Gruppo")));

            $option_leaf = CDOMElement::create('option');
            $option_leaf->setAttribute('value', ADA_LEAF_TYPE);
            $option_leaf->addChild(new CText(translateFN("Foglia")));

            $option_group_word = CDOMElement::create('option');
            $option_group_word->setAttribute('value', ADA_GROUP_WORD_TYPE);
            $option_group_word->addChild(new CText(translateFN("Gruppo di termini")));

            $option_leaf_word = CDOMElement::create('option');
            $option_leaf_word->setAttribute('value', ADA_LEAF_WORD_TYPE);
            $option_leaf_word->addChild(new CText(translateFN("Termine")));

            if (isset($node_data['type']) && $node_data['type'] == ADA_GROUP_TYPE) {
                $option_group->setAttribute('selected', 'selected');
            } elseif (isset($node_data['type']) && $node_data['type'] == ADA_GROUP_WORD_TYPE) {
                $option_group_word->setAttribute('selected', 'selected');
            } elseif (isset($node_data['type']) && $node_data['type'] == ADA_LEAF_WORD_TYPE) {
                $option_leaf_word->setAttribute('selected', 'selected');
            } else {
                $option_leaf->setAttribute('selected', 'selected');
            }

            $select->addChild($option_group);
            $select->addChild($option_leaf);
            $select->addChild($option_group_word);
            $select->addChild($option_leaf_word);
            $span_select->addChild($select);

            $node_data_div->addChild($span_select);
        } else {
            $hidden_type = CDOMElement::create('hidden', "id:type, name:type");
            $hidden_type->setAttribute('value', $node_data['type']);
            $node_data_div->addChild($hidden_type);
        }

        if ($flags & EDITOR_SHOW_NODE_POSITION) {
            $node_position = CDOMElement::create('div', 'id:show_node_position, class:editor_input');
            $label      = CDOMElement::create('label', 'for:position');
            $label->addChild(new CText(translateFN('Posizione')));
            $input_text = CDOMElement::create('text', 'id:position, name:position');
            // vito 26 jan 2009
            if (is_array($node_data['position'])) {
                // vito 23 jan 2009
                $position = "{$node_data['position'][0]},{$node_data['position'][1]},{$node_data['position'][2]},{$node_data['position'][3]}";
            } else {
                $position = $node_data['position'];
            }
            $input_text->setAttribute('value', $position);
            $node_position->addChild($label);
            $node_position->addChild($input_text);

            $node_data_div->addChild($node_position);
        } else {
            $hidden_position = CDOMElement::create('hidden', 'id:position, name:position');
            $hidden_position->setAttribute('value', $node_data['position']);

            $node_data_div->addChild($hidden_position);
        }

        if ($flags & EDITOR_SHOW_NODE_ORDER) {
            $node_order = CDOMElement::create('div', 'id:show_node_icon, class:editor_input');
            $label      = CDOMElement::create('label', 'for:order');
            $label->addChild(new CText(translateFN('Ordine')));
            $input_text = CDOMElement::create('text', 'id:order, name:order');
            $input_text->setAttribute('value', $node_data['order']);
            $node_order->addChild($label);
            $node_order->addChild($input_text);

            $node_data_div->addChild($node_order);
        } else {
            $hidden_order = CDOMElement::create('hidden', 'id:order, name:order');
            $hidden_order->setAttribute('value', $node_data['order']);
            $node_data_div->addChild($hidden_order);
        }
        // vito, 22 apr 2009, do not allow parent node change for the root node.
        if (($flags & EDITOR_SHOW_PARENT_NODE_SELECTOR) && ($node_data['id'] != $id_course . '_' . ADA_DEFAULT_NODE)) {
            $parent_node_selector = CDOMElement::create('div', 'id:parent_node');

            $span_label = CDOMElement::create('div', 'id:parent_node_label,class:editor_text');
            $span_label->addChild(new CText(translateFN("Nodo parent attuale")));

            $span_text  = CDOMElement::create('div', 'id:jsparent_node_text, class:editor_text');

            if ($node_data['parent_id'] == 'NULL') {
                $parent_id = translateFN('Questo è il nodo principale del corso');
            } else {
                $parent_id = $node_data['parent_id'];
            }
            $span_text->addChild(new CText($parent_id));

            $span_selector = CDOMElement::create('div', 'id:parent_node_selector, class:editor_input');
            $input_button = CDOMElement::create('input_button', 'class:ui tiny button');
            $input_button->setAttribute('value', translateFN('Modifica'));
            $input_button->setAttribute('onclick', "toggleVisibility('jsparent_node_selector');");
            $span_selector->addChild($input_button);

            $div_parent_node_selector = CDOMElement::create('div', 'id:jsparent_node_selector');
            // vito, 22 apr 2009
            if (isset($node_data['id']) && !empty($node_data['id'])) {
                $id_node = $node_data['id'];
            } else {
                $id_node = null;
            }
            $div_parent_node_selector->addChild(self::getInternalLinkSelector($id_course, $id_node, 'jsparent_node_selector', 1));

            $parent_node_selector->addChild($span_label);
            $parent_node_selector->addChild($span_text);
            $parent_node_selector->addChild($span_selector);
            $parent_node_selector->addChild($div_parent_node_selector);

            $node_data_div->addChild($parent_node_selector);
        }

        /**
         * NOTE: EDITOR_UPLOAD_FILE flag is used to check if user is an author or a tutor
         */
        if ($flags & EDITOR_UPLOAD_FILE) {
            // @author giorgio 26/apr/2013
            // checkbox to force node modification to appear in whats new page
            $node_forcecreationupdate  = CDOMElement::create('div', 'id:show_node_forcecreationupdate, class:editor_input');
            $label          = CDOMElement::create('label', 'for:forcecreationupdate');
            $label->addChild(new CText(translateFN('Appare nelle novit&agrave;')));
            $input_forcecreationupdate = CDOMElement::create('checkbox', 'id:forcecreationupdate, name:forcecreationupdate');
            $input_forcecreationupdate->setAttribute('value', '1');
            if (isset($node_data['forcecreationupdate'])) {
                $input_forcecreationupdate->setAttribute('checked', 'checked');
            }
            $node_forcecreationupdate->addChild($input_forcecreationupdate);
            $node_forcecreationupdate->addChild($label);
            $node_data_div->addChild($node_forcecreationupdate);
            // @author giorgio 26/apr/2013 end checkbox
        }

        /**
         * NOTE: EDITOR_SHOW_NODE_LEVEL flag is used to check if user is an author
         */
        if (ModuleLoaderHelper::isLoaded('FORKEDPATHS') && ($flags & EDITOR_SHOW_NODE_LEVEL)) {
            // @author giorgio 14/jun/2019
            // checkbox to set ForkedPaths node flag
            $node_isforkedpaths  = CDOMElement::create('div', 'id:show_node_isforkedpaths, class:editor_input');
            $label          = CDOMElement::create('label', 'for:isforkedpaths');
            $label->addChild(new CText(translateFN('Percorso a bivi')));
            $input_isforkedpaths = CDOMElement::create('checkbox', 'id:isforkedpaths, name:is_forkedpaths');
            $input_isforkedpaths->setAttribute('value', '1');
            if (isset($node_data['is_forkedpaths']) && $node_data['is_forkedpaths'] == 1) {
                $input_isforkedpaths->setAttribute('checked', 'checked');
            }
            $node_isforkedpaths->addChild($input_isforkedpaths);
            $node_isforkedpaths->addChild($label);
            $node_data_div->addChild($node_isforkedpaths);
            // @author giorgio 14/jun/2019 end checkbox
        }

        $saveBtn = CDOMElement::create('input_button', 'id:saveAttributesBtn, class: ui tiny button');
        $saveBtn->setAttribute('value', translateFN('Salva'));
        $saveBtn->setAttribute('onclick', 'saveNodeAttributes();');
        $node_data_div->addChild($saveBtn);

        $node_data_div->addChild(CDOMElement::create('hidden', "id:id,name:id,value:{$node_data['id']}"));
        $node_data_div->addChild(CDOMElement::create('hidden', "id:jsparent_id,name:parent_id,value:{$node_data['parent_id']}"));
        $node_data_div->addChild(CDOMElement::create('hidden', "id:id_node_author,name:id_node_author,value:{$node_data['id_node_author']}"));
        $node_data_div->addChild(CDOMElement::create('hidden', "id:version,name:version,value:{$node_data['version']}"));
        $hidden_creation_date = CDOMElement::create('hidden', 'id:creation_date,name:creation_date');
        $hidden_creation_date->setAttribute('value', $node_data['creation_date']);
        $node_data_div->addChild($hidden_creation_date);
        $node_data_div->addChild(CDOMElement::create('hidden', "id:color,name:color,value:{$node_data['color']}"));
        $node_data_div->addChild(CDOMElement::create('hidden', "id:correctness,name:correctness,value:{$node_data['correctness']}"));
        $hidden_copyright = CDOMElement::create('hidden', 'id:copyright,name:copyright');
        $hidden_copyright->setAttribute('value', $node_data['copyright']);
        $node_data_div->addChild($hidden_copyright);
        //$node_data_div->addChild(CDOMElement::create('hidden',"id:copyright,name:copyright,value:{$node_data['copyright']}"));

        return $node_data_div;
    }

    /**
     * function getButtons, based on $flags bitmask, shows additional elements on the editing form.
     *
     * @param int $flags
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getButtons($flags)
    {
        $div_buttons = CDOMElement::create('div', 'id:jsbuttons');

        //    if ($flags & EDITOR_INSERT_NODE_DATA)
        //    {
        $span_node_data = CDOMElement::create('div', 'id:jsbutton_forjsnode_data_div,class:ui tiny active button');
        //         $span_node_data->setAttribute('class', 'editor_input selected');
        $span_node_data->setAttribute('onclick', "showMeHideOthers('jsbutton_forjsnode_data_div','jsnode_data_div');");
        $span_node_data->addChild(new CText(translateFN('Attributi nodo')));

        $div_buttons->addChild($span_node_data);
        //    }

        if ($flags & EDITOR_INSERT_EXTERNAL_LINK) {
            $span_external_link = CDOMElement::create('div', 'id:jsbutton_forjsid_divle,class:ui tiny inactive button');
            //             $span_external_link->setAttribute('class', 'editor_input unselected');
            $span_external_link->setAttribute('onclick', "showMeHideOthers('jsbutton_forjsid_divle','jsid_divle');");
            $span_external_link->addChild(new CText(translateFN('Aggiungi link esterno')));

            $div_buttons->addChild($span_external_link);
        }

        if ($flags & EDITOR_INSERT_INTERNAL_LINK) {
            $span_internal_link = CDOMElement::create('div', 'id:jsbutton_forjsid_divli,class:ui tiny inactive button');
            //             $span_internal_link->setAttribute('class', 'editor_input unselected');
            $span_internal_link->setAttribute('onclick', "showMeHideOthers('jsbutton_forjsid_divli','jsid_divli');");
            $span_internal_link->addChild(new CText(translateFN('Aggiungi link interno')));

            $div_buttons->addChild($span_internal_link);
        }

        if (($flags & EDITOR_UPLOAD_FILE) || ($flags & EDITOR_SELECT_FILE)) {
            $span_upload_file = CDOMElement::create('div', 'id:jsbutton_forjsid_divfu,class:ui tiny inactive button');
            //             $span_upload_file->setAttribute('class', 'editor_input unselected');
            $span_upload_file->setAttribute('onclick', "showMeHideOthers('jsbutton_forjsid_divfu','jsid_divfu');");
            $span_upload_file->addChild(new CText(translateFN('Aggiungi multimedia')));

            $div_buttons->addChild($span_upload_file);
        }

        return $div_buttons;
    }

    /**
     * function getAddOns, based on $flags bitmask, shows additional elements on the editing form.
     *
     * @param int    $flags
     * @param int    $id_course
     * @param int    $id_course_instance
     * @param int    $id_user
     * @param string $id_node
     * @param string $fckeditor_instance
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getAddOns($flags, $id_course, $id_course_instance, $id_user, $id_node)
    {
        $php_file_uploader = 'upload.php?caller=editor';

        $div_addons = CDOMElement::create('div', 'id:jsaddons');
        /*
     * External links
        */
        $div_le = null;
        if (($flags & EDITOR_INSERT_EXTERNAL_LINK) || ($flags & EDITOR_SELECT_EXTERNAL_LINK)) {
            $div_le = CDOMElement::create('div', 'id:jsid_divle');
        }

        if ($flags & EDITOR_INSERT_EXTERNAL_LINK) {
            $text_for_add_external_link_button = translateFN("Aggiungi questo link");

            $insert_external_link = CDOMElement::create('div', 'id:insert_external_link');

            $span_text = CDOMElement::create('div', 'id:span_insert_external_link_text, class:editor_label');
            $span_text->addChild(new CText(translateFN("Inserisci qui il link esterno")));

            $span_input = CDOMElement::create('div', 'id:span_insert_external_link_input, class:editor_input');
            $input      = CDOMElement::create('text', 'id:jsid_textle, name:name_textle');
            $span_input->addChild($input);

            $span_button = CDOMElement::create('div', 'id:span_insert_external_link_button, class:editor_button');
            $input_button = CDOMElement::create('input_button', 'class:ui tiny button');
            $input_button->setAttribute('onclick', 'addExternalLink();');
            $input_button->setAttribute('value', translateFN("Aggiungi questo link"));
            $span_button->addChild($input_button);

            $insert_external_link->addChild($span_text);
            $insert_external_link->addChild($span_input);
            $insert_external_link->addChild($span_button);

            $div_le->addChild($insert_external_link);
        }

        if ($flags & EDITOR_SELECT_EXTERNAL_LINK) {
            $div_le->addChild(self::getAuthorExternalLinkSelector($id_course));
        }

        if ($div_le !== null) {
            $div_addons->addChild($div_le);
        }
        /*
     * Internal links
        */
        if ($flags & EDITOR_INSERT_INTERNAL_LINK) {
            $div_li = CDOMElement::create('div', 'id:jsid_divli');
            $span   = CDOMElement::create('div', 'id:span_insert_internal_link_text, class:editor_label');
            $span->addChild(new CText(translateFN('Seleziona un nodo del corso da linkare')));
            $div_li->addChild($span);
            $div_li->addChild(self::getInternalLinkSelector($id_course, $id_node, 'jsid_divli', 0));
            $div_addons->addChild($div_li);
        }

        /*
     * Media
        */
        $div_fu = null;
        if (($flags & EDITOR_UPLOAD_FILE) || ($flags & EDITOR_SELECT_FILE)) {
            $div_fu = CDOMElement::create('div', 'id:jsid_divfu');
            $div_media_properties = CDOMElement::create('div', 'id:jsid_div_media_properties');
        }
        if ($flags & EDITOR_UPLOAD_FILE) {
            $div_error_file_upload = CDOMElement::create('div', 'id:jserror_file_upload,class:ui error message');

            $form = CDOMElement::create('form', "id:uploadfile, name:uploadfileform, enctype:multipart/form-data,
      action:$php_file_uploader, method:post");
            $form->setAttribute('onclick', 'enterUploadFileState();');

            $span_file = CDOMElement::create('div', 'id:span_upload_file_input, class:editor_input');
            $linput_file = CDOMElement::create('label', 'for:id_multimedia,class:ui tiny button');
            $linput_file->addChild(new CText('Scegli file'));
            $span_file->addChild($linput_file);
            $input_file = CDOMElement::create('file', 'id:id_multimedia, name:file_up, class:donotDisable');
            $span_file->addChild($input_file);

            $input_hidden_1 = CDOMElement::create('hidden', "class:donotDisable,name:course_id, value:$id_course");
            $input_hidden_2 = CDOMElement::create('hidden', "class:donotDisable,name:course_instance_id, value:$id_course_instance");
            $input_hidden_3 = CDOMElement::create('hidden', "class:donotDisable,name:user_id, value:$id_user");
            $input_hidden_4 = CDOMElement::create('hidden', "class:donotDisable,name:node_id, value:$id_node");

            $span_button = CDOMElement::create('div', 'id:span_upload_file_button, class:editor_input');
            $input_button = CDOMElement::create('submit', 'class:donotDisable ui tiny button');
            $input_button->setAttribute('value', translateFN("Invia questo file"));
            $span_button->addChild($input_button);

            $iframe = CDOMElement::create('iframe', 'id:upload_results, name:upload_results');
            $iframe->setAttribute('src', '');
            $iframe->setAttribute('style', 'display: none');


            $form->addChild($span_file);
            $form->addChild($input_hidden_1);
            $form->addChild($input_hidden_2);
            $form->addChild($input_hidden_3);
            $form->addChild($input_hidden_4);
            $form->addChild($span_button);
            $form->addChild($iframe);

            $div_fu->addChild($div_error_file_upload);
            $div_fu->addChild($form);
        }

        if ($flags & EDITOR_SELECT_FILE) {
            $div_fu->addChild(self::getAuthorMediaSelector($id_course));
            $div_media_properties->addChild(self::getAuthorMediaManager());
            $div_media_properties->setAttribute('style', 'display: none');
        }

        if ($div_fu !== null) {
            $div_addons->addChild($div_fu);
        }
        if (isset($div_media_properties) && $div_media_properties !== null) {
            $div_addons->addChild($div_media_properties);
        }

        return $div_addons;
    }

    /**
     * function getInternalLinkSelector, used to display a tree for the course the edited node belongs to.
     * this tree allows user to select a group of nodes to link to or a single node.
     *
     * @param  int    $id_course
     * @param  string $fckeditorInstance
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getInternalLinkSelector($id_course, $id_node, $container_div, $action)
    {
        // vito, 22 apr 2009, added $id_node and 'id_edited_node'
        return CourseViewer::displayInternalLinkSelector($id_course, ['action' => $action, 'container_div' => $container_div, 'id_edited_node' => $id_node]);
    }

    /**
     * function getAuthorMediaOnlySelector, used to create a list of author's media already uploaded.
     *
     * @param int    $id_course
     * @param string $media_type
     * @param string $select_name
     * @param array  $node_data
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getAuthorMediaOnlySelector($id_course, $media_type = null, $select_name = 'authormediaselector', $node_data = [])
    {
        if ($media_type == null) {
            $media_type = [_SOUND, _VIDEO, _IMAGE, _DOC, _PRONOUNCE, _FINGER_SPELLING, _LABIALE, _LIS, _MONTESSORI];
        }
        $author_media = NodeEditing::getAuthorMedia($id_course, $media_type);
        $author_id = $_SESSION['sess_id_user'];
        $select = CDOMElement::create('select', 'id:' . $select_name . ', name:' . $select_name);

        $notice = translateFN('Selezione l\'icona del nodo');
        $value = "";
        $option = CDOMElement::create('option');
        $option->setAttribute('value', $value);
        $option->addChild(new CText("$notice"));
        $select->addChild($option);
        foreach ($author_media as $media) {
            //            $ada_filetype = Utilities::getFileHintFromADAFileType($media['tipo']);
            //            $value = "{$media['tipo']}|{$media['nome_file']}";
            $value = ROOT_DIR . '/services/media/' . $author_id . '/' . $media['nome_file'];
            $option = CDOMElement::create('option');
            $option->setAttribute('value', $value);
            if (isset($node_data['icon']) && $value == $node_data['icon']) {
                $option->setAttribute('selected', 'selected');
            }
            $option->addChild(new CText("{$media['nome_file']}"));
            $select->addChild($option);
        }

        return $select;
    }

    /**
     * function getAuthorMediaSelector, used to display a list of author's media already uploaded.
     * It is possible for the user to select a media and add it to current node content or to manage the media properties.
     *
     * @param int    $id_course
     * @param string $fckeditorInstance
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getAuthorMediaSelector($id_course, $media_type = null)
    {
        if ($media_type == null) {
            $media_type = [_SOUND, _VIDEO, _IMAGE, _DOC, _PRONOUNCE, _FINGER_SPELLING, _LABIALE, _LIS, _MONTESSORI];
        }
        $author_media = NodeEditing::getAuthorMedia($id_course, $media_type);

        $form = CDOMElement::create('form', 'id:select_files, class:editor_form');

        $span_select = CDOMElement::create('div', 'id:span_select_media, class:editor_input');
        $select = CDOMElement::create('select', 'id:jsid_select_files, size:10');
        $select->setAttribute('onchange', "updateMediaManager();");
        foreach ($author_media as $media) {
            $ada_filetype = Utilities::getFileHintFromADAFileType($media['tipo']);
            $value = "{$media['tipo']}|{$media['nome_file']}";
            $option = CDOMElement::create('option');
            $option->setAttribute('value', $value);
            $option->addChild(new CText("$ada_filetype {$media['nome_file']}"));
            $select->addChild($option);
        }
        $span_select->addChild($select);

        //        $span_button = CDOMElement::create('div','id:span_select_media_button, class:editor_input');
        $input_button = CDOMElement::create('input_button', 'class:ui tiny button');
        $author_id = $_SESSION['sess_id_user'];
        $input_button->setAttribute('onclick', "manageMultimediaProperties(getFileDataFromSelect('jsid_select_files'),$author_id);");
        $input_button->setAttribute('value', translateFN("Gestisci proprietà media"));

        //        $span_button->addChild($input_button);

        $span_button_media_properties = CDOMElement::create('div', 'id:span_properties_media_button, class:editor_input');
        $input_button_media_properties = CDOMElement::create('input_button', 'class:ui tiny button');
        $input_button_media_properties->setAttribute('onclick', "addMultimedia(getFileDataFromSelect('jsid_select_files'));");
        $input_button_media_properties->setAttribute('value', translateFN("Aggiungi questo media"));
        $span_button_media_properties->addChild($input_button);
        $span_button_media_properties->addChild($input_button_media_properties);

        $form->addChild($span_select);
        //        $form->addChild($span_button);
        $form->addChild($span_button_media_properties);

        return $form;
    }

    /**
     * function getAuthorMediaManager, used to manage the properties of each author's media already uploaded.
     * It is possible for the user to set some properties and save them on DB.
     *
     * @param int    $id_course
     * @param string $fckeditorInstance
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getAuthorMediaManager()
    {
        $form = CDOMElement::create('form', 'id:properties_media, class:editor_form');
        /*
            $form = CDOMElement::create('form',"id:uploadfile, name:uploadfileform, enctype:multipart/form-data,
      action:$php_file_uploader, method:post");
            $form->setAttribute('onclick','enterUploadFileState();');
         *
         */

        $textarea_div = CDOMElement::create('div', 'id:jstextarea_media_div');

        // Title
        $title_label = CDOMElement::create('DIV');
        $title_label->setAttribute('class', 'label_extended');
        $title_label->addChild(new CText(translateFN('title')));

        $span_title_area = CDOMElement::create('div', 'id:title_media,class:editor_input');
        $title_area = CDOMElement::create('text', 'id:jsdata_titlesarea, name:titolo');
        $title_area->setAttribute('value', translateFN('Scrivi il titolo'));
        $span_title_area->addChild($title_area);

        // preview
        //        $title_label = CDOMElement::create('DIV');
        //        $title_label->setAttribute('class', 'label_extended');
        //        $title_label->addChild(new CText(translateFN('title')));

        $span_preview_area = CDOMElement::create('div', 'id:preview_media,class:media_preview');

        // Description
        $description_label = CDOMElement::create('DIV');
        $description_label->setAttribute('class', 'label_extended');
        $description_label->addChild(new CText(translateFN('description')));

        $span_description_area = CDOMElement::create('div', 'id:description_media,class:editor_input');
        $description_area = CDOMElement::create('textarea', 'id:jsdata_descriptionarea, name:descrizione');
        $description_area->addChild(new CText(translateFN('Scrivi la descrizione')));
        //        $description_area->setAttribute('value', translateFN('Scrivi la descrizione'));
        $span_description_area->addChild($description_area);

        // keywords
        $keywords_label = CDOMElement::create('DIV');
        $keywords_label->setAttribute('class', 'label_extended');
        $keywords_label->addChild(new CText(translateFN('keywords')));

        $span_keywords_area = CDOMElement::create('div', 'id:keywords,class:editor_input');
        $keywords_area = CDOMElement::create('text', 'id:jsdata_keywordsarea, name:keywords');
        $keywords_area->setAttribute('value', translateFN('Scrivi le keywords'));
        $span_keywords_area->addChild($keywords_area);

        // pubblicato
        //        $keywords_label = CDOMElement::create('DIV');
        //        $keywords_label->setAttribute('class', 'label_extended');
        //        $keywords_label->addChild(new CText(translateFN('keywords')));

        $span_published_area = CDOMElement::create('div', 'id:published,class:editor_input');
        $span_published_area->addChild(new CText(translateFN('published')));
        $published_check = CDOMElement::create('checkbox', 'id:jsdata_published, name:published');
        $published_check->setAttribute('checked', 'true');
        $span_published_area->addChild($published_check);

        // language
        Translator::loadSupportedLanguagesInSession();
        $supported_languages = Translator::getSupportedLanguages();
        $login_page_language_code = $_SESSION['sess_user_language'] ?? Translator::negotiateLoginPageLanguage();

        $select = CDOMElement::create('select', 'id:p_selected_language, name:lingua');
        foreach ($supported_languages as $language) {
            $option = CDOMElement::create('option', "value:{$language['id_lingua']}");
            if (isset($login_page_language_code) && $language['codice_lingua'] == $login_page_language_code) {
                $option->setAttribute('selected', 'selected');
            }
            $option->addChild(new CText($language['nome_lingua']));
            $select->addChild($option);
        }

        // extended media type

        $select_extended_media_type = CDOMElement::create('select', 'id:p_selected_media_extended_type, name:tipo');
        $option_media_type = CDOMElement::create('option', "value:'0'");
        $option_media_type->addChild(new CText(translateFN('inserisci tipo media')));
        $select_extended_media_type->addChild($option_media_type);

        $hidden_input_id_risorsa_ext = CDOMElement::create('hidden', 'name:id_risorsa_ext', 'value:0');
        $hidden_copy = CDOMElement::create('hidden', 'name:copyright', 'value:1');
        $textarea_div->addChild($title_label);
        $textarea_div->addChild($span_title_area);
        $textarea_div->addChild($span_preview_area);
        $textarea_div->addChild($keywords_label);
        $textarea_div->addChild($span_keywords_area);
        $textarea_div->addChild($description_label);
        $textarea_div->addChild($span_description_area);
        $textarea_div->addChild($span_published_area);
        $textarea_div->addChild($select);
        $textarea_div->addChild($select_extended_media_type);
        $textarea_div->addChild($hidden_input_id_risorsa_ext);
        $textarea_div->addChild($hidden_copy);

        /*

                $span_select = CDOMElement::create('div','id:span_properties_media, class:editor_input');
                $key_label =
                $keywords = CDOMElement::create('select','id:jsid_properties_files, size:10');
                foreach ( $author_media as $media ) {
                    $ada_filetype = Utilities::getFileHintFromADAFileType($media['tipo']);
                    $value = "{$media['tipo']}|{$media['nome_file']}";
                    $option = CDOMElement::create('option');
                    $option->setAttribute('value', $value);
                    $option->addChild(new CText("$ada_filetype {$media['nome_file']}"));
                    $select->addChild($option);
                }
                $span_select->addChild($select);
        */
        $author_id = $_SESSION['sess_id_user'];
        $span_button_media_properties = CDOMElement::create('div', 'id:span_properties_media_button, class:editor_input');
        $input_button = CDOMElement::create('input_button', 'class:ui tiny button');
        $input_button->setAttribute('onclick', "saveMultimediaProperties(getFileDataFromSelect('jsid_select_files'),$author_id);");
        $input_button->setAttribute('value', translateFN("Salva proprietà media"));
        $span_button_media_properties->addChild($input_button);

        $input_button_media_properties = CDOMElement::create('input_button', 'class:ui tiny button');
        $input_button_media_properties->setAttribute('onclick', "toggleVisibility('jsid_div_media_properties');");
        $input_button_media_properties->setAttribute('value', translateFN("Chiudi"));
        $span_button_media_properties->addChild($input_button_media_properties);

        $form->addChild($textarea_div);
        //        $form->addChild($span_button);
        $form->addChild($span_button_media_properties);

        return $form;
    }

    /**
     * function getAuthorExternalLinkSelector, used to display a list of external links already added by author
     *
     * @param int $id_course
     * @return \Lynxlab\ADA\CORE\html4\CBase
     */
    public static function getAuthorExternalLinkSelector($id_course)
    {

        $media_type = [_LINK];
        $author_media = NodeEditing::getAuthorMedia($id_course, $media_type);

        $form = CDOMElement::create('form', 'id:select_external_link, class:editor_form');

        $span_select = CDOMElement::create('div', 'id:span_select_external_link, class:editor_input');
        $select = CDOMElement::create('select', 'id:jsid_select_external_links, size:10');
        foreach ($author_media as $media) {
            $ada_filetype = Utilities::getFileHintFromADAFileType($media['tipo']);
            $value = "{$media['tipo']}|{$media['nome_file']}";
            $option = CDOMElement::create('option');
            $option->setAttribute('value', $value);
            $option->addChild(new CText("$ada_filetype {$media['nome_file']}"));
            $select->addChild($option);
        }
        $span_select->addChild($select);

        $span_input = CDOMElement::create('div', 'id:span_select_external_link_button, class:editor_input');
        $input_button = CDOMElement::create('input_button', 'class:ui tiny button');
        $input_button->setAttribute('onclick', "addMultimedia(getFileDataFromSelect('jsid_select_external_links'));");
        $input_button->setAttribute('value', translateFN("Aggiungi questo link"));
        $span_input->addChild($input_button);

        $form->addChild($span_select);
        $form->addChild($span_input);

        return $form;
    }
}
