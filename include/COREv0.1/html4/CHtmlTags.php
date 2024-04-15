<?php

use Lynxlab\ADA\CORE\html4\CUl;

use Lynxlab\ADA\CORE\html4\CTr;

use Lynxlab\ADA\CORE\html4\CTObject;

use Lynxlab\ADA\CORE\html4\CTHead;

use Lynxlab\ADA\CORE\html4\CTh;

use Lynxlab\ADA\CORE\html4\CTFoot;

use Lynxlab\ADA\CORE\html4\CTextarea;

use Lynxlab\ADA\CORE\html4\CTd;

use Lynxlab\ADA\CORE\html4\CTBody;

use Lynxlab\ADA\CORE\html4\CTable;

use Lynxlab\ADA\CORE\html4\CSubmitInput;

use Lynxlab\ADA\CORE\html4\CSpan;

use Lynxlab\ADA\CORE\html4\CSelect;

use Lynxlab\ADA\CORE\html4\CResetInput;

use Lynxlab\ADA\CORE\html4\CRadio;

use Lynxlab\ADA\CORE\html4\COption;

use Lynxlab\ADA\CORE\html4\COptgroup;

use Lynxlab\ADA\CORE\html4\COl;

use Lynxlab\ADA\CORE\html4\CMap;

use Lynxlab\ADA\CORE\html4\CLink;

use Lynxlab\ADA\CORE\html4\CLi;

use Lynxlab\ADA\CORE\html4\CLegend;

use Lynxlab\ADA\CORE\html4\CLabel;

use Lynxlab\ADA\CORE\html4\CInputText;

use Lynxlab\ADA\CORE\html4\CInputPassword;

use Lynxlab\ADA\CORE\html4\CImg;

use Lynxlab\ADA\CORE\html4\CIFrame;

use Lynxlab\ADA\CORE\html4\CI;

use Lynxlab\ADA\CORE\html4\CHtmlTags;

use Lynxlab\ADA\CORE\html4\CHiddenInput;

use Lynxlab\ADA\CORE\html4\CH4;

use Lynxlab\ADA\CORE\html4\CH3;

use Lynxlab\ADA\CORE\html4\CH2;

use Lynxlab\ADA\CORE\html4\CH1;

use Lynxlab\ADA\CORE\html4\CForm;

use Lynxlab\ADA\CORE\html4\CFileInput;

use Lynxlab\ADA\CORE\html4\CFieldset;

use Lynxlab\ADA\CORE\html4\CDt;

use Lynxlab\ADA\CORE\html4\CDl;

use Lynxlab\ADA\CORE\html4\CDiv;

use Lynxlab\ADA\CORE\html4\CDd;

use Lynxlab\ADA\CORE\html4\CColgroup;

use Lynxlab\ADA\CORE\html4\CCol;

use Lynxlab\ADA\CORE\html4\CCheckbox;

use Lynxlab\ADA\CORE\html4\CCaption;

use Lynxlab\ADA\CORE\html4\CButtonInput;

use Lynxlab\ADA\CORE\html4\CButton;

use Lynxlab\ADA\CORE\html4\CArea;

use Lynxlab\ADA\CORE\html4\CA;

// Trigger: ClassWithNameSpace. The class CHtmlTags was declared with namespace Lynxlab\ADA\CORE\html4. //

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
            case 'COl':
                return "<ol %start% $core_attributes $i18n_attributes $event_attributes>\n%children%\n</ol>\n";
            case 'CUl':
                return "<ul $core_attributes $i18n_attributes $event_attributes>\n%children%\n</ul>\n";
            case 'CLi':
                return "<li $core_attributes $i18n_attributes $event_attributes>\n%children%\n</li>\n";
            case 'CDl':
                return "<dl $core_attributes $i18n_attributes $event_attributes>\n%children%\n</dl>\n";
            case 'CDt':
                return "<dt $core_attributes $i18n_attributes $event_attributes>\n%children%\n</dt>\n";
            case 'CDd':
                return "<dd $core_attributes $i18n_attributes $event_attributes>\n%children%\n</dd>\n";
            case 'CTable':
                return "<table $core_attributes $i18n_attributes $event_attributes $table_attributes>\n%children%\n</table>\n";
            case 'CCaption':
                return "<caption $core_attributes $i18n_attributes $event_attributes>\n%children%\n</caption>\n";
            case 'CFieldset':
                return "<fieldset $core_attributes $i18n_attributes $event_attributes>\n%children%\n</fieldset>\n";
            case 'CSpan':
                return "<span $core_attributes $i18n_attributes $event_attributes>\n%children%\n</span>\n";
            case 'CI':
                return "<i $core_attributes $i18n_attributes $event_attributes>\n%children%\n</i>\n";
            case 'CH1':
                return "<h1 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h1>\n";
            case 'CH2':
                return "<h2 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h2>\n";
            case 'CH3':
                return "<h3 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h3>\n";
            case 'CH4':
                return "<h4 $core_attributes $i18n_attributes $event_attributes>\n%children%\n</h4>\n";
            case 'CDiv':
                return "<div $core_attributes $i18n_attributes $event_attributes>\n%children%\n</div>\n";
            case 'COptgroup':
                return "<optgroup $core_attributes $i18n_attributes $event_attributes $select_element>\n%children%\n</optgroup>\n";
            case 'COption':
                return "<option %disabled% %selected% %value% $core_attributes $i18n_attributes $event_attributes $select_element>\n%children%\n</option>\n";
            case 'CTHead':
                return "<thead $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</thead>\n";
            case 'CTFoot':
                return "<tfoot $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</tfoot>\n";
            case 'CTBody':
                return "<tbody $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</tbody>\n";
            case 'CColgroup':
                return "<colgroup %span% %width% $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</colgroup>\n";
            case 'CTr':
                return "<tr $core_attributes $i18n_attributes $event_attributes $alignable_element>\n%children%\n</tr>\n";
            case 'CTd':
                return "<td $core_attributes $i18n_attributes $event_attributes $alignable_element $tablecell_element>\n%children%\n</td>\n";
            case 'CTh':
                return "<th $core_attributes $i18n_attributes $event_attributes $alignable_element $tablecell_element>\n%children%\n</th>\n";
            case 'CA':
                return "<a %charset% %type% %name% %href% %hreflang% %rel% %rev% %shape% %coords% %target% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex>\n%children%\n</a>\n";
            case 'CTextarea':
                return "<textarea %name% %rows% %cols% %disabled% %readonly% %onselect% %onchange% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex>\n%children%\n</textarea>\n";
            case 'CButton':
                return "<button %name% %value% %type% %disabled% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex>\n%children%\n</button>\n";
            case 'CSelect':
                return "<select %name% %size% %multiple% %disabled% %onchange% $core_attributes $i18n_attributes $event_attributes $focusable_element $tabindex>\n%children%\n</select>\n";
            case 'CLabel':
                return "<label %for% $core_attributes $i18n_attributes $event_attributes $focusable_element $accesskey>\n%children%\n</label>\n";
            case 'CLegend':
                return "<legend $core_attributes $i18n_attributes $event_attributes $accesskey>\n%children%\n</legend>\n";
            case 'CTObject':
                return "<object %declare% %classid% %codebase% %data% %type% %codetype% %archive% %standby% %height% %width% %usemap% %name% $core_attributes $i18n_attributes $event_attributes $tabindex>\n%children%\n</object>\n";
            case 'CMap':
                return "<map %name% $core_attributes $i18n_attributes $event_attributes >\n%children%\n</map>\n";
            case 'CForm':
                return "<form %name% %action% %method% %enctype% %accept-charset% %accept% %onsubmit% %onreset% $core_attributes $i18n_attributes $event_attributes>\n%children%\n</form>\n";
            case 'CCol':
                return "<col %span% %width% $core_attributes $i18n_attributes $event_attributes $alignable_element>\n";
            case 'CLink':
                return "<link %charset% %type% %name% %href% %hreflang% %rel% %rev% %media% $core_attributes $i18n_attributes $event_attributes>\n";
            case 'CImg':
                return "<img %src% %alt%  %longdesc% %name% %height% %width% %usemap% %ismap% $core_attributes $i18n_attributes $event_attributes>\n";
            case 'CArea':
                return "<area %shape% %coords% %href% %nohref% %alt% $core_attributes $i18n_attributes $event_attributes>\n";
            case 'CFileInput':
            case 'CHiddenInput':
            case 'CSubmitInput':
            case 'CResetInput':
            case 'CInputText':
            case 'CInputPassword':
            case 'CButtonInput':
            case 'CCheckbox':
            case 'CRadio':
                return "<input %name% %type% %checked% %disabled% %readonly% %onselect% %size% %maxlength% %placeholder% %usemap% %ismap% %src% %alt% %onchange% %value% $core_attributes $i18n_attributes $event_attributes $accesskey $tabindex $focusable>\n";
            case 'CIFrame':
                return "<iframe $core_attributes %longdesc% %name% %src% %frameborder% %marginwidth% %marginheight% %noresize% %scrolling% %align% %width% %height%>\n%children%\n</iframe>\n";
            default:
                return "";
        }
    }
}
