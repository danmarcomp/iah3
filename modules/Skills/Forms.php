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


function initSkillsEditor($nodeId, $buttonId, $inputId, $table, $field, $id, $readonly = false)
{
	global $db;
	$allSkills = $skills = array();
	$query = 'SELECT id, name FROM skills WHERE deleted=0 ORDER BY name';
	$res = $db->query($query, true);
	while ($row = $db->fetchByAssoc($res)) {
		$allSkills[$row['id']] = $row['name'];
	}

	$query = "SELECT $table.*, skills.name FROM $table LEFT JOIN skills ON skills.id=$table.skill_id WHERE $table.$field='$id' AND skills.deleted = 0 AND $table.deleted=0";

	$res = $db->query($query, true);
	while ($row = $db->fetchByAssoc($res)) {
		$skills[$row['skill_id']] = $row;
	}
    
	$json = getJSONObj();
	$readonly = $readonly ? 'true' : 'false';

	return '<script type="text/javascript" src="modules/Skills/skills.js"></script><script type="text/javascript">SkillsEditor.readonly = ' . $readonly . ';SkillsEditor.init("' . $nodeId . '", "' . $buttonId . '", "' . $inputId . '", ' . $json->encode($skills) . ', ' . $json->encode($allSkills) . ');</script>';
}
