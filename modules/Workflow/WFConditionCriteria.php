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


require_once('data/SugarBean.php');

class WFConditionCriteria extends SugarBean {

	// persistent fields
	var $id;
	var $field_name;
	var $field_value;
	var $operator;
	var $time_interval;
	var $idx;
	var $level;
	var $glue;
	var $workflow_id;
	var $assigned_user_id;

	var $module_dir = 'Workflow';
	var $object_name = 'WFConditionCriteria';
	var $table_name = 'workflow_criteria';
	
	function validate() {
		if (empty($this->field_name)) {
			throw new WFException(WFException::ERR_EMPTY_FIELD);
		}
		if (empty($this->operator)) {
			throw new WFException(WFException::ERR_EMPTY_VALUE);
		}
		return true;
	}

}

?>
