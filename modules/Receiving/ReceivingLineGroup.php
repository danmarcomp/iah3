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


require_once('modules/Receiving/Receiving.php');
require_once('modules/Receiving/ReceivingLine.php');
require_once('modules/Receiving/ReceivingAdjustment.php');
require_once('modules/Receiving/ReceivingComment.php');

require_once('modules/Quotes/QuoteLineGroup.php');

class ReceivingLineGroup extends QuoteLineGroup {

	// stored fields
	
	var $id;
	var $invoice_id;
	
	var $name;
	var $status;
	var $pricing_method;
	var $pricing_percentage;
	
	var $subtotal, $subtotal_usd;
	var $total, $total_usd;

	var $group_type;
	
	// runtime fields
	
	var $parent_bean;
	var $lines;
	var $line_changes;
	var $adjusts;
	var $adjust_changes;
	
	// static fields
	
	var $module_dir = 'Receiving';
	var $object_name = 'ReceivingLineGroup';
	var $table_name = 'receiving_line_groups';
	var $new_schema = true;
	//
	var $line_object = 'ReceivingLine';
	var $comment_object = 'ReceivingComment';
	var $adj_object = 'ReceivingAdjustment';
	var $rel_id_field = 'receiving_id';
	
	var $currency_fields = array(
		'subtotal' => 'subtotal_usd', 'total' => 'total_usd',
	);
	
	var $line_items_map = array(
		'quantity' => 'quantity',
		'product_name' => 'name',
		'cost_price' => 'cost_price',
		'list_price' => 'list_price',
		'purchase_price' => 'unit_price',
		'std_selling_price' => 'std_unit_price',
		'mft_part_num' => 'mfr_part_no',
	);
	
	// methods
	
	function ReceivingLineGroup() {
		parent::QuoteLineGroup();
	}
	
	function &newForParent(&$bean) {
		$ilg = new ReceivingLineGroup();
		$ilg->parent_bean =& $bean;
		$ilg->parent_id = $bean->id; // bean must have been saved
		if(! empty($bean->duplicate_of_id))
			$ilg->parent_id = $bean->duplicate_of_id; // load alternate line items
		$ilg->initNew($this);
		return $ilg;
	}

}

?>
