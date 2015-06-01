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


require_once('include/layout/forms/FormTableSection.php');

class RolesWidget extends FormTableSection {
	var $model_name;
	
	static $preferred_order = array('access', 'view', 'list', 'edit', 'delete', 'import', 'export', 'report', 'approve');

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'acl_roles';
		if($model) $this->model_name = $model->name;
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}
		$lstyle = $gen->getLayout()->getType();
		if($lstyle == 'editview') {
			return $this->renderHtmlEdit($gen, $row_result);
		}
		return $this->renderHtmlView($gen, $row_result);
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $role_id = $row_result->getField('id');
        $matrix = $this->getRolesMatrix($role_id, 'view');

		return $matrix;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $layout =& $gen->getLayout();
        $layout->addScriptInclude('modules/ACLRoles/roles.js');

        $role_id = $row_result->getField('id');
        $matrix = $this->getRolesMatrix($role_id, 'edit');    

        return $matrix;
	}
	
    function getRolesMatrix($role_id, $context = 'view') {

        if ($this->model_name == 'User') {
            global $current_user;

            $categories = ACLAction::getUserActions($role_id);

            //clear out any removed tabs from user display
            if(!is_admin($current_user)){
                $user = new User();
                $user->id = $role_id;
                $tabs = $user->getPreference('display_tabs');
                global $modInvisList, $modSemiInvisList;
                if(!empty($tabs)){
                    foreach($categories as $key=>$value){
                        if(!in_array($key, $tabs) &&  !in_array($key, $modInvisList) && !in_array($key, $modSemiInvisList) )
                            unset($categories[$key]);
                    }

                }
            }
        } else {
		    $categories = ACLRole::getRoleActions($role_id, 'module', true);
        }


        if ($context == 'view' || $context == 'edit') {
            $no_edit = AppConfig::setting('acl.default_role.no_edit', array());

            foreach($no_edit as $key=>$value){
                if($value) {
                    unset($categories[$key]);
                }
            }

        }
        
        self::reorder_actions($categories);

        $action_names = ACLAction::setupCategoriesMatrix($categories);
        $groups = ACLAction::groupCategories($categories);
        $colspan = sizeof($action_names) + 1;

		$title = to_html($this->getLabel());
        $body = <<<EOQ
        <table width="100%" border="0" style="margin-top: 0.5em;" cellspacing="0" cellpadding="0" class="tabDetailView">
            <tr>
                <th align="left" class="tabDetailViewDL" colspan="{$colspan}">
                   <h4 class="tabDetailViewDL">{$title}</h4>
                </th>
            </tr>
            <tr>
                <td class="tabDetailViewDF" colspan="9">
EOQ;

        foreach ($groups as $index => $group) {
            $body .= <<<EOQ
        <table width='100%' class='tabDetailView' border='0' cellpadding="0" cellspacing="1" style="margin-top: 0.5em">
        <tr>
        <td class="tabDetailViewDL" colspan="{$colspan}" style="text-align: center; font-size: 8.5pt"><b>{$group['label']}</b></td>
        </tr>
        <tr>
        <td class="tabDetailViewDL" width="15%">&nbsp;</td>
EOQ;

            if (sizeof($action_names) > 0) {
                foreach ($action_names as $action_id => $action_name) {
                    $body .= '<td nowrap class="tabDetailViewDL" style="text-align: center;" width="8%"><b>'.$action_name.'</b></td>';
                }
            } else {
                $body .= '<td colspan="2">&nbsp;</td>';
            }

            $body .= '</tr>';
            $json = getJSONobj();

            foreach ($group['modules'] as $module_id => $module) {
                $body .= '<tr onmouseover="setPointer(this, \''.$module.'\', \'over\');" onmouseout="setPointer(this, \''.$module.'\', \'out\');" onmousedown="setPointer(this, \''.$module.'\', \'click\');">
                    <td nowrap width="1%" class="tabDetailViewDL" ><b>'.$module_id.'</b></td>';

                foreach ($categories[$module] as $type => $actions) {
                    foreach ($actions as $acces_type => $action) {

                        if (!empty($action['inactive']) && $action['inactive']) {
                            $class = "aclInactive";
                        } else {
                            $class = "acl" . $action['cssclass'];
                        }

                        if ($context == 'view') {
                            $body .= '<td  class="tabDetailViewDF" align="center"><div align="center" class="'.$class.'">'.$action['accessName'].'</div></td>';
                        } else {
                            $cursor = '';
                            $onclick = '';
                            $id = $module .'-'. $acces_type;
                            if (!empty($action['accessName'])) {
                                $cursor = 'cursor: pointer';
                                $onclick = 'onclick=\'start_acl_edit("'.$id.'", '.$json->encode($action['accessOptions']).');\'';
                            }

                            $body .= '<td class="tabDetailViewDF" style="text-align: center; '.$cursor.'" '.$onclick.'>
	                                <input type="hidden" name="acl_value['.$id.']" id="acl-'.$id.'" value="'.$action['aclaccess'].'" />
	                                <div class="'.$class.'" style="display: inline;" id="'.$id.'">'.$action['accessName'].'</div>
	                                </td>';
                        }

                    }
                }

                $body .= '</tr>';
            }

            $body .= '</table>';
        }

        $body .= "</td></tr></table>";

        return $body;
    }
    
    
    static function reorder_actions(&$cats) {
        foreach($cats as $mod => $acts) {
        	$new_acts = array();
        	foreach(self::$preferred_order as $act) {
        		if(isset($acts['module'][$act]))
        			$new_acts[$act] = $acts['module'][$act];
        	}
        	array_extend($new_acts, $acts['module']);
        	$cats[$mod]['module'] = $new_acts;
        }
    }
    

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_access = array();
        if(isset($input['acl_value']) && is_array($input['acl_value'])) {
            foreach($input['acl_value'] as $action_id => $value) {
                if(! strpos($action_id, '-'))
                    continue;
                list($mod, $action) = explode('-', $action_id, 2);
                $upd_access[$mod][$action] = array('aclaccess' => $value);
            }

            $update->setRelatedData($this->id.'_rows', $upd_access);            
        }
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
		if(! $row_updates)
			return;

		$reload = false;
        $role_id = null;

        if (! $update->new_record)
            $role_id = $update->getPrimaryKeyValue();

		if($role_id != null) {
			foreach ($row_updates as $module => $actions) {
				ACLManager::make_consistent($actions);
				foreach($actions as $act_id => $row) {
					ACLRole::setAction($role_id, $module, $act_id, $row['aclaccess']);
					$reload = true;
				}
			}
		}

        if($reload)
            AppConfig::invalidate_cache('acl');
	}
}
?>
