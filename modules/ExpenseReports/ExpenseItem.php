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

/*********************************************************************************

 * Description:  Defines the Account SugarBean Account entity with the necessary
 * methods and variables.
 ********************************************************************************/

require_once('data/SugarBean.php');

// Account is used to store account information.
class ExpenseItem extends SugarBean {
	
	// Stored fields
	var $id;
	var $report_id;
	var $item_number;
	var $description;
	var $date;
	var $assigned_user_id;
	var $assigned_user_name;
	var $amount;
	var $tax_class_id;
	var $total;
	var $split;
	var $quantity;
	var $category;
	var $line_order;
	var $parent_id;
	var $note_id;
	var $paid_rate;
	var $paid_rate_usd;

	var $table_name = "expense_items";
	var $module_dir = "ExpenseReports";

	var $object_name = "ExpenseItem";

	var $new_schema = true;

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array('assigned_user_name', 'assigned_user_id');

	var $relationship_fields = Array();

	function ExpenseItem() {
        parent::SugarBean();
	}
}


