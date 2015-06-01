<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/
require_once('include/utils/file_utils.php');

class Relationship {
	var $name; 
	var $lhs_module;
	var $lhs_bean;
	var $lhs_table;
	var $lhs_key;
	var $rhs_module;
	var $rhs_bean;
	var $rhs_table;
	var $rhs_key;
	var $join_table;
	var $join_key_lhs;
	var $join_key_rhs;
	var $relationship_type;
	var $relationship_role_column;
	var $relationship_role_column_value;
	var $reverse;

	var $_self_referencing;


	/*returns true if the relationship is self referencing. equality check is performed for both table and
	 * key names.
	 */
	function is_self_referencing() {
		return $this->_self_referencing;
	}
	
	function init_from_def($name, $source_model, $defn) {
		$this->name = $name;
		
		if(isset($defn['join'])) {
			$this->join_table = AppConfig::setting("model.detail.$source_model.table_name");
			$this->join_key_lhs = $defn['left']['join_key'];
			$this->join_key_rhs = $defn['right']['join_key'];
			$this->relationship_role_column = array_get_default($defn['join'], 'role_field');
			$this->relationship_role_column_value = array_get_default($defn['join'], 'role_value');
		}
		
		$source_model = $defn['left']['model'];
		$this->lhs_key = $defn['left']['key'];
		$target = $defn['right']['model'];
		$this->rhs_key = $defn['right']['key'];		

		$this->lhs_bean = $source_model;
		$this->lhs_table = AppConfig::setting("model.detail.$source_model.table_name");
		$this->lhs_module = AppConfig::setting("model.detail.$source_model.module_dir");
		$this->rhs_bean = $target;
		$this->rhs_table = AppConfig::setting("model.detail.$target.table_name");
		$this->rhs_module = AppConfig::setting("model.detail.$target.module_dir");
		$this->relationship_type = $defn['left']['count'].'-to-'.$defn['right']['count'];

		$this->_self_referencing = ($this->lhs_table == $this->rhs_table && $this->lhs_key == $this->rhs_key);
	}
	
	function init_from_ref($source_model, $spec) {
		$this->lhs_key = $spec['id_name'];
		$this->lhs_bean = $source_model;
		$this->lhs_table = AppConfig::setting("model.detail.$source_model.table_name");
		$this->lhs_module = AppConfig::setting("model.detail.$source_model.module_dir");
		if(isset($spec['bean_name'])) {
			$target = $spec['bean_name'];
			$this->rhs_bean = $target;
			$this->rhs_key = AppConfig::setting("model.detail.$target.primary_key");
			$this->rhs_table = AppConfig::setting("model.detail.$target.table_name");
			$this->rhs_module = AppConfig::setting("model.detail.$target.module_dir");
			$this->relationship_type = 'one-to-one';
		} else {
			if(! isset($spec['dynamic_module']))
				throw new IAHError("expected bean_name or dynamic_module for 'ref' type field");
			$this->relationship_role_column = $spec['dynamic_module'];
			$this->relationship_type = 'many-to-one';
		}
		$this->_self_referencing = ($this->lhs_table == $this->rhs_table && $this->lhs_key == $this->rhs_key);		
	}

	/*returns true if a relationship with provided name exists*/
	function exists($relationship_name) {
		return !! AppConfig::setting("model.index.relationships.$relationship_name");
	}
	

	function get_other_module($relationship_name, $base_module) {
	//give it the relationship_name and base module
	//it will return the module name on the other side of the relationship

		$query = "SELECT relationship_name, rhs_module, lhs_module FROM relationships WHERE deleted=0 AND relationship_name = '".$relationship_name."'";
		$result = $db->query($query,true," Error searching relationships table..");
		$row  =  $db->fetchByAssoc($result);
		if ($row != null) {
			
			if($row['rhs_module']==$base_module){
				return $row['lhs_module'];
			}	
			if($row['lhs_module']==$base_module){
				return $row['rhs_module'];
			}				
		}

		return false;
		
		
	//end function get_other_module
	}
	
	
	function retrieve_by_name($relationship_name) {
		$model_name = AppConfig::setting("model.index.relationships.$relationship_name");
		if(! $model_name) {
			$GLOBALS['log']->fatal('Error fetching relationship: '.$relationship_name);
			return false;
		}
		$defn = AppConfig::setting("model.relationships.$model_name.$relationship_name");
		return $this->init_from_def($relationship_name, $model_name, $defn);
	}

}
?>
