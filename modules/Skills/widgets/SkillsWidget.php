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
require_once('modules/Skills/Skill.php');

class SkillsWidget extends FormTableSection {
	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'required_skills';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
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
		return array('id');
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $record = $row_result->getField('id');
        $body = "";

        if ($record) {
            $skillsJs = $this->addSkillsJs($gen, $record, true);
            //if ($skillsJs)
                $body = $this->getTable();
        }

        return $body;
	}

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $record = $row_result->getField('id');

        $body = $this->getTable(true);
        $this->addSkillsJs($gen, $record);

        return $body;
	}

    function getTable($edit = false) {
        global $app_strings;

		$title = $this->getLabel();
		if($edit) {
			$button = '<button id="add_skill" type="button" class="input-button input-outer" style="margin: -2px 2em;"><div class="input-icon icon-add left"></div><span class="input-label">'.$app_strings['LBL_ADD_BUTTON'].'</span></button>';
			$tcls = 'tabForm';
			$lcls = 'dataLabel';
			$fcls = 'dataField';
		} else {
			$button = '';
			$tcls = 'tabDetailView';
			$lcls = 'tabDetailViewDL';
			$fcls = 'tabDetailViewDF';
		}
        $table = <<<EOQ
            <table id="{$this->id}-outer" width="100%" border="0" cellspacing="0" cellpadding="0" class="{$tcls}" style="margin-top: 0.5em">
            <tr><th align="left" class="{$lcls}" colspan="5"><h4 class="{$lcls}">{$title}{$button}</h4></th></tr>
            <tr><td>
            <table id="{$this->id}" width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr><td class="{$fcls}">{$app_strings['LBL_NONE']}</td>
EOQ;

        $table .= '</tr></table></td></tr></table>';

        return $table;
    }

    function addSkillsJs(HtmlFormGenerator $gen, $record, $readonly = false) {
        $allSkills = Skill::getAllSkills();
        $skills = Skill::getEntrySkills($record, $allSkills, $this->model->name);
        $layout = $gen->getLayout();

        if (sizeof($skills) > 0 || !$readonly) {
            $json = getJSONobj();
            $readonly = $readonly ? 'true' : 'false';
            $buttonId = 'add_skill';
            $inputId = $this->name;
            $js = 'SkillsEditor.readonly = ' . $readonly . ';SkillsEditor.init("' . $this->id . '", "' . $buttonId . '", "' . $inputId . '", ' . $json->encode($skills) . ', ' . $json->encode($allSkills) . ');';
            $form_name = $gen->getFormName();
            $js .= "SUGAR.ui.registerInput('$form_name', SkillsEditor);";
            
            $layout->addScriptInclude('modules/Skills/skills.js', LOAD_PRIORITY_BODY);
            $layout->addScriptLiteral($js, LOAD_PRIORITY_FOOT);

            return true;
        } else {
            return false;
        }
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_skills = array();
        if (!empty($input[$this->name])) {
        	$json = getJSONobj();
        	$js = $json->decode($input[$this->name]);
        	if(is_array($js))
        		$upd_skills['skills_info'] = $js;
        }
        $update->setRelatedData($this->id.'_rows', $upd_skills);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
		if(! $row_updates)
			return;

        if (!$update->new_record) {
            $update->removeAllLinks('skills');
        }

        if(isset($row_updates['skills_info'])) {
			$ids = array();
			$info = array();
			foreach($row_updates['skills_info'] as $skill) {
				if(empty($skill['id'])) continue;
				$ids[] = $skill['id'];
				$info[$skill['id']] = array(
					'name' => 'rating',
					'value' => array_get_default($skill, 'rating', 0),
				);
			}
            $update->addUpdateLinks('skills', $ids, $info);
        }
	}
}
?>