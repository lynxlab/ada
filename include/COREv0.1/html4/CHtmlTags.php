<?php

/**
 *
 * @author vito
 */

namespace Lynxlab\ADA\CORE\html4;

class CHtmlTags
{
    public static function getTagForHtmlElement($element_class)
    {
        $core_attributes  = "%id% %class% %style% %title% %datas% %role%";
        $i18n_attributes  = "%lang% %dir%";
        $event_attributes = "%onclick% %ondblclick% %onmousedown% %onmouseup% %onmouseover% %onmousemove% %onmouseout% %onkeypress% %onkeydown% %onkeyup%";

        $accesskey = "%accesskey%";
        $tabindex  = "%tabindex%";
        $focusable = "%onfocus% %onblur%";
        $focusable_element = "";
        $select_element = "%disabled% %label%";
        $alignable_element = "%align% %char% %charoff% %valign%";
        $tablecell_element = "%abbr% %axis% %header% %scope% %rowspan% %colspan%";

        $table_attributes = "%summary% %width% %border% %frame% %rules% %cellspacing% %cellpadding%";


        switch ($element_class) {
            case COl::class:
                return "<ol %start% $core_attributes $i18n_attributes $event_attributes>\n%children%\n</ol>\n";
            case CUl::class:
                return "<ul $core_attributes $i18n_attributes $event_attributes>\n%children%\n</ul>\n";
            case CLi::class:
                return "<li $core_attributes $i18n_attributes $event_attributes>\n%children%\n</li>\n";
            case CDl::class:
                return "<dl $core_attributes $i18n_attributes $event_attributes>\n%children%\n</dl>\n";
            case CDt::class:
                return "<dt $core_attributes $i18n_attributes $event_attributes>\n%children%\n</dt>\n";
            case CDd::class:
                return "<dd $core_attributes $i18n_attributes $event_attributes>\n%children%\n</dd>\n";
            case CTable::class:
                return "<table $core_attributes $i18n_attributes $event_attributes $table_attributes>\n%children%\n</table>\n";
            case CCaption::class:
                return "<caption $core_attributes $i18n_attributes $event_attributes>\n%children%\n</caption>\n";
            case CFieldset::class:
                return "<fieldset $core_attributes $i18n_attributes $event_attributes>\n%children%\n</fieldset>\n";
            case CSpan::class:
                return "<span $core_attributes $i18n_attributes $event_attributes>\n%children%\n</span>\n";
            case CI::class:
                return "<i $core_attributes $i18n_attributes $event_attributes>\n%children%\n</i>\n";
            case CH1::class:
                return "<h1 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h1>\n";
            case CH2::class:
                return "<h2 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h2>\n";
            case CH3::class:
                return "<h3 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h3>\n";
            case CH4::class:
                return "<h4 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h4>\n";
            case CDiv::class:
                return "<div $core_attributes $i18n_attributes $event_attributes>\n%children%\n</div>\n";
            case COptgroup::class:
                return "<optgroup $core_attributes $i18n_attributes $event_attributes $select_element>\n%children%\n</optgroup>\n";
            case COption::class:
                return "<option %disabled% %selected% %value% $core_attributes $i18n_attributes $event_attributes $select_element>\n%children%\n</option>\n";
            case CTHead::class:
                return "<thead $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</thead>\n";
            case CTFoot::class:
                return "<tfoot $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</tfoot>\n";
            case CTBody::class:
                return "<tbody $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</tbody>\n";
            case CColgroup::class:
                return "<colgroup %span% %width% $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</colgroup>\n";
            case CTr::class:
                return "<tr $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</tr>\n";
            case CTd::class:
                return "<td $core_attributes $i18n_attributes $event_attributes $alignable_element $tablecell_element>\n%children%\n</td>\n";
            case CTh::class:
                return "<th $core_attributes $i18n_attributes $event_attributes $alignable_element $tablecell_element>\n%children%\n</th>\n";
            case CA::class:
                return "<a %charset% %type% %name% %href% %hreflang% %rel% %rev% %shape% %coords% %target% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex>\n%children%\n</a>\n";
            case CTextarea::class:
                return "<textarea %name% %rows% %cols% %disabled% %readonly% %onselect% %onchange% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex>\n%children%\n</textarea>\n";
            case CButton::class:
                return "<button %name% %value% %type% %disabled% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex>\n%children%\n</button>\n";
            case CSelect::class:
                return "<select %name% %size% %multiple% %disabled% %onchange% $core_attributes $i18n_attributes $event_attributes $focusable_element $tabindex>\n%children%\n</select>\n";
            case CLabel::class:
                return "<label %for% $core_attributes $i18n_attributes $event_attributes $focusable_element $accesskey>\n%children%\n</label>\n";
            case CLegend::class:
                return "<legend $core_attributes $i18n_attributes $event_attributes $accesskey>\n%children%\n</legend>\n";
            case CTObject::class:
                return "<object %declare% %classid% %codebase% %data% %type% %codetype% %archive% %standby% %height% %width% %usemap% %name% $core_attributes $i18n_attributes $event_attributes $tabindex>\n%children%\n</object>\n";
            case CMap::class:
                return "<map %name% $core_attributes $i18n_attributes $event_attributes >\n%children%\n</map>\n";
            case CForm::class:
                return "<form %name% %action% %method% %enctype% %accept-charset% %accept% %onsubmit% %onreset% $core_attributes $i18n_attributes $event_attributes>\n%children%\n</form>\n";
            case CCol::class:
                return "<col %span% %width% $core_attributes $i18n_attributes $event_attributes $alignable_element>\n";
            case CLink::class:
                return "<link %charset% %type% %name% %href% %hreflang% %rel% %rev% %media% $core_attributes $i18n_attributes $event_attributes>\n";
            case CImg::class:
                return "<img %src% %alt%  %longdesc% %name% %height% %width% %usemap% %ismap% $core_attributes $i18n_attributes $event_attributes>\n";
            case CArea::class:
                return "<area %shape% %coords% %href% %nohref% %alt% $core_attributes $i18n_attributes $event_attributes>\n";
            case CFileInput::class:
            case CHiddenInput::class:
            case CSubmitInput::class:
            case CResetInput::class:
            case CInputText::class:
            case CInputPassword::class:
            case CButtonInput::class:
            case CCheckbox::class:
            case CRadio::class:
                return "<input %name% %type% %checked% %disabled% %readonly% %onselect% %size% %maxlength% %placeholder% %usemap% %ismap% %src% %alt% %onchange% %value% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex $focusable>\n";
            case CIFrame::class:
                return "<iframe $core_attributes %longdesc% %name% %src% %frameborder% %marginwidth% %marginheight% %noresize% %scrolling% %align% %width% %height%>\n%children%\n</iframe>\n";
            default:
                return "";
        }
    }
}
