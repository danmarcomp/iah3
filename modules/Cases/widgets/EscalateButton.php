<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */


require_once('include/layout/forms/FormButton.php');
require_once ('modules/Skills/Skill.php');

class EscalateButton extends FormButton {

    function getAdditionalHtml(FormGenerator &$gen) {
        global $mod_strings, $db;
        //$s = AppConfig::setting('company.company_case_queue_user');

        $record = $gen->form_obj->hidden_fields['record'];
        $module = $gen->form_obj->hidden_fields['module'];
        $html = "";
        $style="display:none; overflow: hidden; height: 1px;";
        $bean_name = AppConfig::setting("modinfo.primary_beans.$module");

        $allSkills = Skill::getAllSkills();
        $skills = Skill::getEntrySkills($record, $allSkills, $bean_name);

        $escalate_form = '<table id="detail_slider_content_escalate" width="100%" border="0" cellspacing="0" cellpadding="0"  class="tabForm"><tr><th align="left" colspan="4" class="dataLabel"><h4 class="dataLabel">' . $mod_strings['LBL_WHICH_SKILLS'] . '</h4></th></tr>';

        if (sizeof($allSkills) > 0) {
            $i = 0;
            foreach($allSkills as $id => $name) {                
                if ($i % 2 == 0) {
                    $escalate_form .= '<tr>';
                }
                $checked = isset($skills[$id]) ? 'checked="checked"' : '';
                $escalate_form .= '<td class="dataLabel"><label><input type="checkbox" ' . $checked . ' name="escalate_skill" class="checkbox" value="' . $id . '"> ' . $name . '</label></td>';
                $i++;
                if ($i % 2 == 0) {
                    $escalate_form .= '</tr>';
                }
            }
            if ($i % 2) {
                $escalate_form .= '</tr>';
            }
        }
        
        $escalate_form .= '<tr><td class="dataLabel" colspan="4"><input type="button" class="button" onclick="doEscalate(this.form)" value="' . $mod_strings['LBL_DO_ESCALATE'] . '"></td></tr>';
        $escalate_form .= '<tr id="escalate_error" style="display:none"><td class="dataLabel" colspan="4"><span class="error">' . $mod_strings['LBL_ESCALATE_SELECT_ONE'] . '</span></td></tr>';
        $escalate_form .= '</table>';

        $html = detail_view_slider('escalate', $escalate_form, $style);

        return $html;
    }
}
?>