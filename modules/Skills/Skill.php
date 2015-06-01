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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');


class Skill extends SugarBean {

	var $object_name='Skill';
	var $module_dir = 'Skills';
	var $new_schema = true;
	var $table_name = 'skills';
	
	var $id;
	var $name;
	var $deleted;

	function Skill() {
		parent::SugarBean();
	}
	
	function get_summary_text() {
		return $this->name;
	}
	
	function track_view($user_id, $current_module) {
	}
	
	function updateSkills($input, $table, $field, $id)
	{
		global $db;
		$ids = array();
		if (empty($_POST[$input]) || $_POST[$input] == '{}') {
			$db->query("UPDATE $table SET deleted = 1 WHERE $field = '$id'");
		} else {
			$json = getJSONObj();
			$skills = $json->decode(from_html($_POST[$input]));
			if (!empty($skills)) foreach ($skills as $sid => $rating) {
				$rating = (int)$rating;
				$res = $db->query("SELECT * FROM $table WHERE deleted = 0 AND skill_id='$sid' AND $field = '$id'", true);
				$row = $db->fetchByAssoc($res);
				if (!empty($row)) {
					$db->query("UPDATE  $table SET rating = '$rating' WHERE skill_id='$sid' AND $field = '$id' AND deleted=0", true);
				} else {
					$db->query("INSERT INTO $table SET id='" . create_guid() . "', deleted = 0, rating = '$rating', skill_id='$sid', $field = '$id'", true);
				}
				$ids[] = $sid;
			}
			if (empty($ids)) {
				$db->query("UPDATE $table SET deleted = 1 WHERE $field = '$id'");
			} else {
				$db->query("UPDATE $table SET deleted = 1 WHERE $field = '$id' AND skill_id NOT IN ('" .join("','", $ids) . "')" , true);
			}
		}
	}

    static function getAllSkills() {
        $allSkills = array();
        $lq = new ListQuery('Skill', array('id', 'name'), array('plain' => true));
        $lq->order_by = 'name';
        $result = $lq->fetchAll();

        if ($result) {
            $rows = $result->rows;

            foreach ($rows as $row) {
                $allSkills[$row['id']] = $row['name'];
            }
        }

        return $allSkills;
    }

    static function getEntrySkills($id, $allSkills, $model) {
        $skills = array();
        if (sizeof($allSkills) > 0) {
            $model = new ModelDef($model);
            $link = $model->getLinkTargetModel('skills');
            $join = $link->getJoinModel();

            $lspec = $join->getLinkSpec();
            $lkey = $lspec['relationship_spec']['left']['join_key'];

            $lq = new ListQuery($join);
            $lq->addSimpleFilter($lkey, $id);
            $result = $lq->fetchAll();

            foreach($result->rows as $row) {
                $row['name'] = $allSkills[$row['skill_id']];
                $skills[$row['skill_id']] = $row;
            }
        }

        return $skills;
    }

}
?>
