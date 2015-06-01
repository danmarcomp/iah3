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


require_once('include/layout/forms/FormField.php');
require_once('modules/SavedSearch/SavedSearch.php');

class SearchAssignedUser extends FormField {

    function getRequiredFields() {
        $req = parent::getRequiredFields();
        $additional = array('assigned_user', 'assigned_to_team');
        return array_merge($req, $additional);
    }

    function renderListCell(ListFormatter $fmt, ListResult &$list_result, $row_id) {
        $rows = $list_result->getRows();
        $formatted_rows = $list_result->getRows(true);
        $result = $formatted_rows[$row_id]['assigned_user'];

        $assigned_to_team = $rows[$row_id]['assigned_to_team'];
        $assigned_user_id = $rows[$row_id]['assigned_user_id'];

        if ($assigned_to_team) {
            $team_result = ListQuery::quick_fetch_row('SecurityGroup', $assigned_user_id, array('name'));
            if ($team_result && ! empty($team_result['name'])) {
                $result = "<a href=\"index.php?module=SecurityGroups&amp;action=DetailView&amp;record={$assigned_user_id}\" class=\"listViewNameLink\">{$team_result['name']}</a>";
            }
        } else {
            if ($assigned_user_id == SavedSearch::OWNER_ALL_VALUE) {
                global $mod_strings;
                $mod_strings = return_module_language(AppConfig::language(), 'SavedSearch');
                $result = $mod_strings['LBL_OWNER_ALL'];
            }
        }

        return $result;
    }
}
?>