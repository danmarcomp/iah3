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

class WorkflowWidget extends FormTableSection {

	var $reload = false;
	var $editable = false;

	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'workflow_items';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}
		$lstyle = $gen->getLayout()->getType();
		$this->editable = $lstyle == 'editview';
		global $mod_strings;
		$module = $row_result->getField('trigger_module');
		$js = $this->addJS($row_result, $gen->getFormName(), $module);
		$plus = get_image_info('plus_inline');
		// FIXME 
		if (!$module) return $js;
		$html = <<<EOH
<table id="workflow_conditions_block" style="width:100%; margin-top:0.5em;" class="tabForm">
<tr>
	<th class="dataLabel" align="left">
		<h4 class="dataLabel">{$mod_strings['LBL_SECTION_CONDITION']}</h4>
	</th>
</tr>
<tr><td>
	<table style="width:100%" id="workflow_conditions_header"></table></td>
</tr>
<tr><td>
<table style="width:100%" id="workflow_conditions"></table>
EOH;
		if ($this->editable) $html .= <<<EOH
	<a href="#" onclick="WorkflowEditor.insertCriteria(); return false;" class="listViewTdToolsS1">
		<img width="12" height="12" border="0" align="absmiddle" src="{$plus['src']}" alt="" title="{APP.LNK_INS}" />&nbsp;{$mod_strings['LNK_ADD_CONDITION']}
	</a>
EOH;
		$html .= <<<EOH
</td></tr>
</table>

<table id="workflow_operations_block" style="width:100%; margin-top:0.5em;" class="tabForm">
<tr>
	<th class="dataLabel" align="left">
		<h4 class="dataLabel">{$mod_strings['LBL_SECTION_OPERATIONS']}</h4>
	</th>
</tr>
<tr><td>
	<table style="width:100%" id="workflow_operations_header"></table></td>
</tr>
<tr><td>
<table style="width:100%" id="workflow_operations"></table>
EOH;
		if ($this->editable) $html .= <<<EOH
	<a href="#" onclick="WorkflowEditor.insertOperation(); return false;" class="listViewTdToolsS1">
		<img width="12" height="12" border="0" align="absmiddle" src="{$plus['src']}" alt="" title="{APP.LNK_INS}" />&nbsp;{$mod_strings['LNK_ADD_ACTION']}
	</a>
EOH;
		$html .= <<<EOH
</td></tr>
</table>
EOH;
		// FIXME 
		$html .= $js;
		return $html;
	}

	function addJS(RowResult $row_result, $form_name, $module)
	{
		global $pageInstance;
		global $app_list_strings;
		$wfId = $row_result->getField('id');
		$action = $row_result->getField('trigger_action');
		$data = array(
			'trigger_module' => $module,
			'trigger_action' => $action,
			'fields' => $this->generateFields($row_result, $module),
			'groupBoxes' => $this->generateMailboxes(),
			'templates' => $this->generateTemplates(),
			'template_fields' => $this->generateTemplateFields($row_result, $module),
			'editable' => $this->editable,
			'occurs_when' =>  $row_result->getField('occurs_when'),
		);

		if ($this->reload) {
			$data['conditions'] = '';
			$data['operations'] = '';
		} else {
			$data['conditions'] = $this->generateConditions($wfId);
			$data['operations'] = $this->generateOperations($wfId);
		}

		$json = getJSONObj();

		$modules = array();
		$beans = AppConfig::setting("modinfo.primary_beans");
		foreach ($beans as $module => $bean) {
			$modules[$module] = array_get_default($app_list_strings['moduleList'], $module, $module);
		}
		asort($modules);

		$options = array(
			'keys' => array_keys($modules),
			'values' => array_values($modules),
		);

		$js = "\nSUGAR.ui.registerSelectOptions('workflow_trigger_module', " .  $json->encode($options) . ");\n";
		$js .= "\nSUGAR.ui.registerInput('$form_name', WorkflowEditor);\n";
		$js .= 'WorkflowEditor.init(' . $json->encode($data) . ');';
        $images_meta = $this->getImagesMeta();
		$pageInstance->add_js_literal("ImageMeta = $images_meta;", null, LOAD_PRIORITY_FOOT);
		$pageInstance->add_js_literal($js, null, LOAD_PRIORITY_FOOT);
		foreach ($data['fields'] as $module => $unused)  {
			$pageInstance->add_js_language($module, null, LOAD_PRIORITY_BODY);
		}
		
		require_once('include/CKeditor_IAH/CKeditor_IAH.php');
		$ed = new CKeditor_IAH();

		// this is ugly - exportIncludes should be fixed (see CKeditor_IAH source)
		$ret = $ed->exportIncludes();
		return $ret;
	}
	
	function generateMailboxes()
	{
		global $app_strings;
		$groupBoxes = array('' => $app_strings['LBL_NONE']);
		require_bean('EmailPOP3');
		$mailbox = new EmailPOP3;
		$boxes = $mailbox->get_list('name', 'deleted = 0 AND user_id=-1');
		foreach($boxes['list'] as $box) {
			$groupBoxes[$box->id] = $box->name;
		}
		return $groupBoxes;
	}

	function generateTemplates()
	{
		global $app_strings;
		$fields = array('id', 'name', 'subject');
		$templates = array(
			'options' => array(''=> $app_strings['LBL_NONE']),
			'values' => array(),
		);
		$lq = new ListQuery('EmailTemplate');
		$lr = $lq->runQuery();
		foreach ($lr->getRowIndexes() as $idx) {
			$rr = $lr->getRowResult($idx);
			$id = $rr->getField('id');
			$templates['options'][$id] = $rr->getField('name');
			foreach ($fields as $f) {
				$templates['values'][$id][$f] = $rr->getField($f);
			}
		}
		return $templates;
	}

	function generateTemplateFields(RowResult $row_result, $module)
	{
		if (!$module) return array();
		$model = new ModelDef('Contact');
		$fields = $model->getFieldDefinitions();
		$model = new ModelDef('User');
		$u_fields = $model->getFieldDefinitions();
		$ret = array();
		$ret['Contacts']['contact_iah_url'] =  translate('LBL_IAH_URL', 'Workflow');
		foreach ($fields as $f => $def) {
			if (!isset($u_fields[$f])) continue;
			$ret['Contacts']['contact_' . $f] = translate($def['vname'], 'Contacts');
		}
		foreach($fields as $f => $def) {
			if($def['type'] == 'ref' && ! empty($def['id_name']) && isset($ret['Contacts']['contact_' . $f])) {
				if($ret['Contacts']['contact_' . $def['id_name']] == $ret['Contacts']['contact_' . $f]) {
					if(! preg_match('~\bid\b~i', $ret['Contacts']['contact_' . $f]))
						$ret['Contacts']['contact_' . $def['id_name']] .= ' (ID)';
				}
			}
		}
		
		$primaryBean = AppConfig::module_primary_bean($module);
		$model = new ModelDef($primaryBean);
		$prefix = strtolower($primaryBean) . '_';
		if ($prefix == 'acase_')
			$prefix = 'case_';
		$fields = $model->getFieldDefinitions();
		$ret[$module][$prefix . 'iah_url'] =  translate('LBL_IAH_URL', 'Workflow');
		foreach ($fields as $f => $def) {
			if ($def['type'] == 'ref' || empty($def['vname'])) continue;
			$ret[$module][$prefix . $f] = translate($def['vname'], $module);
		}
		return $ret;
	}

	function generateConditions($wfId)
	{
		require_bean('Workflow');
		$wf = new Workflow;
		$wf->retrieve($wfId);
		$conditions = $wf->get_conditions();
		return $conditions;
	}

	function generateOperations($wfId)
	{
		require_bean('Workflow');
		$wf = new Workflow;
		$wf->retrieve($wfId);
		$operations = $wf->get_operations();
		return $operations;
	}

	function generateFields(RowResult $row_result, $module)
	{
		if (!$module) return array();
		$fields = array(
			$module => $this->generateModuleFields($module),
		);
		foreach ($fields[$module] as $f) {
			if ($f['type'] == 'ref' && ! empty($f['ref_module_dir'])) {
			   	if (!isset($fields[$f['ref_module_dir']])) {
					$fields[$f['ref_module_dir']] =  $this->generateModuleFields($f['ref_module_dir']);
				}
			}
		}
		return $fields;
	}

	function generateModuleFields($module)
	{
		if (!$module) return array();
		$primaryBean = AppConfig::setting("modinfo.by_name.$module.detail.primary_bean", false);
		$model = new ModelDef($primaryBean);
		$fields = $model->getFieldDefinitions();
		foreach ($fields as $f => $def) {
			if ($def['type'] == 'id' || $def['name'] == 'deleted') {
				unset($fields[$f]);
				continue;
			}
			if ($def['type'] == 'ref') {
				if(isset($def['bean_name']))
					$fields[$f]['ref_module_dir'] = AppConfig::module_for_model($def['bean_name']);
			}
		}
		return $fields;
	}

	function getImagesMeta() {
        global $app_strings, $mod_strings;

        $images = array(
            'insert' => array('name' => 'plus_inline', 'title' => $app_strings['LNK_INS']),
            'remove' => array('name' => 'delete_inline', 'title' => $app_strings['LNK_REMOVE']),
            'plus' => array('name' => 'plus_inline'),
            'tree_dots' => array('name' => 'treeDots'),
            'tree_branch' => array('name' => 'treeDotsBranch'),
            'tree_end' => array('name' => 'treeDotsEnd'),
            'blank' => array('name' => 'blank', 'width' => 12, 'height' => 12),
            'Cases' => array(),
            'Project' => array(),
            'pdf' => array('name' => 'pdf_image_inline',),
			'left' => array('name' => 'leftarrow_big', 'title' => $mod_strings['LNK_LEFT']),
			'right' => array('name' => 'rightarrow_big', 'title' => $mod_strings['LNK_RIGHT']),
			'up' => array('name' => 'uparrow_big', 'title' => $mod_strings['LNK_UP']),
			'down' => array('name' => 'downarrow_big', 'title' => $mod_strings['LNK_DOWN']),
        );

        foreach($images as $idx => $img) {
            $src = isset($img['name']) ? $img['name'] : $idx;
            unset($img['name']);
            $images[$idx] = array_merge(get_image_info($src), $img);
        }

        $json = getJSONObj();
        $images_meta = $json->encode($images);

        return $images_meta;
    }
	
	function loadUpdateRequest(RowUpdate &$update, array $input)
	{
		$this->reload = array_get_default($input, 'reload');
		$json = getJSONObj();
		$items = $json->decode(array_get_default($input, 'workflow_items'));
		$update->setRelatedData($this->id.'_items', $items);
	}
	
	function validateInput(RowUpdate &$update)
	{
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
        $wf_id = $update->getPrimaryKeyValue();

		if (!$update->new_record) {
			global $db;
		
			$model = new ModelDef('WFConditionCriteria');
			$sql = "DELETE FROM " . $model->getTableName() . " WHERE workflow_id = '" . $db->quote($wf_id) . "'";
			$db->deleteSQL($sql);

			$model = new ModelDef('WFOperation');
			$sql = "DELETE FROM " . $model->getTableName() . " WHERE workflow_id = '" . $db->quote($wf_id) . "'";
			$db->deleteSQL($sql);

		}



		$items = $update->getRelatedData($this->id.'_items');
		if (!is_array($items)) return;

		$conditions = array_get_default($items, 'conditions', array());
		foreach ($conditions as $condition) {
			$ru = RowUpdate::blank_for_model('WFConditionCriteria');
			$condition['workflow_id'] = $wf_id;
			$ru->request = $condition;
			$ru->loadRequest();
			$ru->insertRow();
		}
		$operations = array_get_default($items, 'operations', array());
		$order = 0;
		foreach ($operations as $operation) {
			$ru = RowUpdate::blank_for_model('WFOperation');
			$operation['workflow_id'] = $wf_id;
			$ru->request = $operation;
			$ru->loadRequest();
			$ru->set('display_order', $order);
			$ru->insertRow();
			$order++;
		}
	}

}
?>
