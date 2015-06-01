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


require_once('modules/Quotes/Quote.php');
require_once('modules/Quotes/QuoteLine.php');
require_once('modules/Quotes/QuoteAdjustment.php');
require_once('modules/Quotes/QuoteComment.php');

class QuoteLineGroup extends SugarBean {

	// stored fields
	
	var $id;
	var $parent_id;
	var $date_entered;
	var $date_modified;
	
	var $name;
	var $status;
	var $position;
	var $pricing_method;
	var $pricing_percentage;
	
	var $subtotal, $subtotal_usd;
	var $total, $total_usd;
	var $cost;

	var $group_type;
	
	// runtime fields
	
	var $parent_bean;
	var $lines;
	var $line_changes;
	var $adjusts;
	var $adjust_changes;
	
	// static fields
	
	var $module_dir = 'Quotes';
	var $object_name = 'QuoteLineGroup';
	var $table_name = 'quote_line_groups';
	var $new_schema = true;
	//
	var $line_object = 'QuoteLine';
	var $comment_object = 'QuoteComment';
	var $adj_object = 'QuoteAdjustment';
	var $rel_id_field = 'quote_id';

	var $id_map = array();

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
	
	var $line_prices_arr = array(
		'cost_price', 'list_price', 'unit_price', 'std_unit_price',
	);

	
	// methods
	
	function QuoteLineGroup() {
		parent::SugarBean();
		global $locale;
		$this->currency_decimals = $locale->getPrecedentPreference('default_currency_significant_digits');
	}
	
	function &newForParent(&$bean) {
		$qlg = new QuoteLineGroup();
		$qlg->parent_bean =& $bean;
		$qlg->parent_id = $bean->id; // bean must have been saved
		if(! empty($bean->duplicate_of_id))
			$qlg->parent_id = $bean->duplicate_of_id; // load alternate line items
		$qlg->initNew($this);
        if($bean->currency_id) {
			$currency = new Currency();
			$currency->retrieve($bean->currency_id);
			if(isset($currency->decimal_places)) {
				$this->currency_decimals = $currency->decimal_places;
			}
			$currency->cleanup();
		}
		return $qlg;
	}
	
	// Use these methods when creating a Quote/Invoice or adding to an existing one
	// Call update_totals() to add the appropriate taxes and calculate tax and discount values
	// You can also use update_from_array() to fill in all the rows (and groups) at once
	function add_line_item($row) {
		$old_id = $this->_normalize_row($row);
		$changes =& $this->line_changes;
		if(! isset($this->lines))
			$this->lines = array();
		if(! is_array($changes))
			$changes = array('add' => array(), 'upd' => array(), 'del' => array());
		if($old_id != $row['id']) {
			// inserting a row
			$changes['add'][$row['id']] = true;
			if(isset($row['position']) && is_numeric($row['position']))
				$ins_pos = $row['position'];
			else
				$ins_pos = 0;
			$end_pos = 1;
			foreach(array_keys($this->lines) as $idx) {
				$line =& $this->lines[$idx];
				$pos = array_get_default($line, 'position');
				if($pos) {
					if($ins_pos && $pos >= $ins_pos) {
						// shift lines further down
						$pos ++;
						$line['position'] = $pos;
						$changes['upd'][$line['id']] = true;
					}
					if($pos >= $end_pos)
						$end_pos = $pos + 1;
				}
			}
			if(! $ins_pos)
				$row['position'] = $end_pos;
			$this->lines[] = $row;
		}
		else {
			// updating an existing row
			if(isset($row['position']))
				unset($row['position']); // not handling repositioning on update
			foreach(array_keys($this->lines) as $idx) {
				$rid = $this->lines[$idx]['id'];
				if($rid == $row['id']) {
					if($this->_update_row($this->lines[$idx], $row)) {
						$changes['upd'][$row['id']] = true;
					}
					break;
				}
			}
		}
		return $row['id'];
	}
	
	function add_standard_discount($discount_id) {
		$disc = $this->get_discount($discount_id);
		$spec = array(
			'related_type' => 'Discounts',
			'related_id' => $disc->id,
			'name' => $disc->name,
			'type' => ($disc->discount_type == 'percentage') ? 'StdPercentDiscount' : 'StdFixedDiscount',
			'amount' => $disc->fixed_amount,
			'rate' => $disc->rate,
		);
		$this->add_adjustment($spec);
	}
	
	function add_adjustment($row) {
		$this->_normalize_row($row);
		$changes =& $this->adjust_changes;
		$changes['add'][$row['id']] = true;
		$this->adjusts[] = $row;
	}
	
	function initNew(&$parent) {
		if(! is_object($parent) || $parent->object_name != $this->object_name)
			return;
		if($this->parent_id == $parent->parent_id) {
			$this->currency_decimals = $parent->currency_decimals;
		}
	}
	
	function populate_attribute_options(&$groups, &$currency) {
		foreach(array_keys($groups) as $idx) {
			if($idx === 'GRANDTOTAL') continue;
			$group =& $groups[$idx];
			if($group->deleted)
				continue;
			$group->populate_group_attribute_options($currency);
		}
	}
	
	function populate_group_attribute_options(&$currency) {
		if(! $this->lines)
			return;
		require_bean('Product');
		require_bean('ProductAttribute');
		$pids = array();
		foreach($this->lines as $idx => $line) {
			if(empty($line['is_comment']) && $line['related_type'] == 'ProductCatalog' && $line['related_id'])
				$pids[$line['related_id']][] = $idx;
		}
		$seed_attr = new ProductAttribute();
		foreach(array_keys($pids) as $pid) {
			$prod = new Product();
			if($prod->retrieve($pid) && $prod->load_relationship('productattributes')) {
				$attr_beans = $prod->productattributes->getBeans($seed_attr);
				$attrs = array();
				foreach($attr_beans as $attr) {
					$row = $attr->get_JSON_array();
					$row['price'] = $currency->convertFromDollar($row['price_usdollar']);
					$attrs[$row['name']][] = $row;
				}
				foreach($pids[$pid] as $idx)
					$this->lines[$idx]['attributes_meta'] = $attrs;
			}
			$prod->cleanup();
		}
	}
	
	// used to upgrade old quotes/invoices in 4.2e+
	// obsolete in 5.0
	function &groupsFromLineItems(&$line_items, $recalc=true) {
		$groups = array();
		$pos = 1;
		
		$groups =& $this->retrieve_all();
		$deleted = array();
		foreach(array_keys($groups) as $k) $deleted[$k] = ($k !== 'GRANDTOTAL');
				
		for(reset($line_items); list($idx,) = each($line_items); ) {
			$info =& $line_items[$idx];
			if(! empty($info['line_group_id']) && isset($groups[$grpid = $info['line_group_id']])) {
				$group =& $groups[$grpid];
				$deleted[$grpid] = false;
			}
			else {
				$group =& $this->newForParent($this->parent_bean);
				if(! empty($info['line_group_id']))
					$group->id = $info['line_group_id'];
			}
			$group->name = $info['group_name'];
			if(isset($info['stage']))
				$group->status = $info['stage'];
			if(isset($info['pricing_method']))
				$group->pricing_method = $info['pricing_method'];
			if(isset($info['pricing_percentage']))
			$group->pricing_percentage = $info['pricing_percentage'];
			$group->subtotal = $info['subtotal'];
			$group->total = $info['total'];
			
			$lines = array();
			if(! is_array($info['rows']))  $info['rows'] = array();
			for(reset($info['rows']); list($rowidx,) = each($info['rows']); ) {
				$row = array();
				$child_rows = array();
				$prod =& $info['rows'][$rowidx];
				if(isset($prod['line_id']))
					$row['id'] = $prod['line_id'];
				if(isset($prod['related_type']))
					$row['related_type'] = $prod['related_type'];
				if(isset($prod['id']))
					$row['related_id'] = $prod['id'];
				if(isset($prod['assembly_name'])) {
					if(empty($row['related_type']))
						$row['related_type'] = empty($row['related_id']) ? 'SupportedAssemblies' : 'Assemblies';
					foreach($this->line_items_map as $f => $g)
						if(isset($prod[$f]))  $row[$g] = $prod[$f];
						else if($f == 'std_selling_price')  $row[$g] = $prod['purchase_price'];
					$row['ext_quantity'] = $row['quantity'];
					$row['ext_price'] = $row['unit_price'] * $row['ext_quantity'];
					$row['name'] = $prod['assembly_name'];
					$row['tax_class_id'] = '';
					$row['sum_of_components'] = '1';
					for(reset($prod['parts']); list($partidx,) = each($prod['parts']); ) {
						$part =& $prod['parts'][$partidx];
						$child = array();
						$child['depth'] = 1;
						if(isset($part['line_id']))
							$child['id'] = $part['line_id'];
						if(isset($part['product_name'])) {
							if(isset($part['related_type']))
								$child['related_type'] = $part['related_type'];
							$child['related_id'] = $part['id'];
							if(empty($to['related_type']))
								$child['related_type'] = empty($child['related_id']) ? 'Assets' : 'ProductCatalog';
							foreach($this->line_items_map as $f => $g)
								if(isset($part[$f]))  $child[$g] = $part[$f];
								else if($f == 'std_selling_price')  $child[$g] = $part['purchase_price'];
							$child['ext_quantity'] = $row['ext_quantity'] * $child['quantity'];
							$child['ext_price'] = $child['unit_price'] * $child['ext_quantity'];
							$child['tax_class_id'] = ($part['is_taxable'] === 'Non-Taxable' || $part['is_taxable'] === 'no') ? '' : '-99';
						}
						else {
							$child['is_comment'] = true;
							if(isset($part['text']))  $child['body'] = $part['text'];
						}
						$child_rows[] = $child;
					}
				}
				else if(isset($prod['product_name'])) {
					if(empty($row['related_type']))
						$row['related_type'] = empty($row['related_id']) ? 'Assets' : 'ProductCatalog';
					foreach($this->line_items_map as $f => $g)
						if(isset($prod[$f]))  $row[$g] = $prod[$f];
						else if($f == 'std_selling_price')  $row[$g] = $prod['purchase_price'];
					$row['ext_quantity'] = $row['quantity'];
					$row['ext_price'] = $row['unit_price'] * $row['ext_quantity'];
					$row['tax_class_id'] = ($prod['is_taxable'] === 'Non-Taxable' || $prod['is_taxable'] === 'no') ? '' : '-99';
				}
				else {
					$row['is_comment'] = true;
					if(isset($prod['text']))  $row['body'] = $prod['text'];
				}
				$lines[] = $row;
				foreach($child_rows as $r)
					$lines[] = $r;
			}
			$group->update_lines($lines, true);
			
			$adjs = array();
			if(! empty($info['discounts']))
				foreach($info['discounts'] as $discount) {
					$dsc = array();
					$dsc['id'] = empty($discount['adj_id']) ? '' : $discount['adj_id'];
					$dsc['related_type'] = 'Discounts';
					$dsc['related_id'] = $discount['id'];
					$dscobj = $this->get_discount($dsc['related_id']);
					$dsc['rate'] = isset($discount['rate']) ? $discount['rate'] : $dscobj->rate;
					$dsc['amount'] = $discount['value'];
					$dsc['type'] = 'StdPercentDiscount';
					$dsc['name'] = $dscobj->name;
					$adjs[] = $dsc;
				}
			if(! empty($info['taxes']))
				foreach($info['taxes'] as $taxline) {
					$tax = array();
					$tax['id'] = empty($taxline['adj_id']) ? '' : $taxline['adj_id'];
					$tax['related_type'] = 'TaxRates';
					$tax['related_id'] = $taxline['id'];
					$taxobj = $this->get_tax($tax['related_id']);
					$tax['rate'] = isset($taxline['rate']) ? $taxline['rate'] : $taxobj->rate;
					$tax['amount'] = $taxline['value'];
					$tax['type'] = $taxobj->compounding ? 'CompoundedTax' : 'StandardTax';
					$tax['name'] = $taxobj->name;
					$adjs[] = $tax;
				}
			else if(isset($info['tax'])) {
				// pre-4.2 data compatibility
				$tax = array('related_type' => 'TaxRates');
				if(! empty($this->parent_bean->fetched_row['taxrate_id'])) {
					$tax['related_id'] = $this->parent_bean->fetched_row['taxrate_id'];
					$taxobj = $this->get_tax($tax['related_id']);
					$tax['type'] = $taxobj->compounding ? 'CompoundedTax' : 'StandardTax';
					$tax['rate'] = $taxobj->rate;
					$tax['name'] = $taxobj->name;
					$tax['amount'] = $info['tax'];
					$adjs[] = $tax;
				}
			}
			
			$ship = null;
			if(! empty($info['shipping'])) {
				$ship = array();
				$ship['related_type'] = 'ShippingProviders';
				$ship['related_id'] = $this->parent_bean->shipping_provider_id;
				$shipper = $this->get_shipper($ship['related_id']);
				$ship['id'] = empty($info['shipping_adj_id']) ? '' : $info['shipping_adj_id'];
				$ship['rate'] = '0';
				$ship['name'] = $shipper->name;
				$ship['amount'] = $info['shipping'];
				$ship['type'] = (empty($info['shipping_taxed']) || $info['shipping_taxed'] == 'false') ? 'UntaxedShipping' : 'TaxedShipping';
				$adjs[] = $ship;
			}
		
			$group->update_adjustments($adjs, true);
			
			if($recalc)
				$group->update_totals();
			
			$group->position = $pos++;
			if(empty($group->id) || ! empty($group->new_with_id))
				$groups[] = $group;
		}
		
		foreach($deleted as $k => $v) {
			if($v) {
				$groups[$k]->deleted = 1;
				$groups[$k]->mark_lines_deleted();
			}
		}
		
		$this->calc_grand_totals($groups);
	
		return $groups;
	}
	
	function calc_grand_totals(&$groups) {
		if(! isset($groups['GRANDTOTAL']))
			$groups['GRANDTOTAL'] =& $this->newForParent($this->parent_bean);
		$gtotal =& $groups['GRANDTOTAL'];
		$gtotal->id = 'GRANDTOTAL';
		$gtotal->subtotal = 0.0;
		$gtotal->total = 0.0;
		$gtotal->cost = 0.0;
		$gtotal->tax = 0.0;
		$gtotal->discount = 0.0;
		$gtotal->shipping = 0.0;
		$gtotal->shipping_taxed = true;
		
		foreach(array_keys($groups) as $idx) {
			if($idx === 'GRANDTOTAL') continue;
			$group =& $groups[$idx];
			if($group->deleted)
				continue;
			$gtotal->subtotal += $group->subtotal;
			$gtotal->total += $group->total;
			$gtotal->cost += $group->cost;
			if($group->adjusts)
			foreach($group->adjusts as $adj) {
				if($adj['type'] == 'TaxedShipping' || $adj['type'] == 'UntaxedShipping') {
					$gtotal->shipping += $adj['amount'];
					if($adj['type'] == 'UntaxedShipping')
						$gtotal->shipping_taxed = false;
				}
				else if($adj['type'] == 'StandardTax' || $adj['type'] == 'CompoundedTax')
					$gtotal->tax += $adj['amount'];
				else if($adj['type'] == 'StandardDiscount' || $adj['type'] == 'StdPercentDiscount' || $adj['type'] == 'StdFixedDiscount')
					$gtotal->discount += $adj['amount'];
			}
		}
		//exit;
	}
	
	// convert from new storage format to old, for compatibility with javascript
	// obsolete in 5.0
	function &lineItemsFromGroups(&$groups) {
		$line_items = array();
		$grppos = 0;
		foreach(array_keys($groups) as $idx) {
			if($idx === 'GRANDTOTAL')
				continue;
			$grp =& $groups[$idx];
			if($grp->deleted)
				continue;
			$line_items[$grppos] = array();
			$li =& $line_items[$grppos];
			$li['line_group_id'] = $grp->id;
			$li['group_name'] = $grp->name;
			$li['stage'] = $grp->status;
			$li['pricing_method'] = $grp->pricing_method;
			$li['pricing_percentage'] = $grp->pricing_percentage;
			$li['subtotal'] = $grp->subtotal;
			$li['total'] = $grp->total;
			$last = array();
			$li['rows'] = array();
			$rows =& $li['rows'];
			if($grp->lines)
			foreach($grp->lines as $line) {
				unset($parent);
				if($line['depth'] && isset($last[$line['depth']-1]))
					$parent =& $last[$line['depth']-1];
				$line['line_id'] = $line['id'];
				if(! empty($line['is_comment'])) {
					$line['text'] = $line['body'];
					unset($line['body']);
					unset($line['id']);
				}
				else {
					$line['id'] = $line['related_id'];
					$line['is_taxable'] = $line['tax_class_id'] ? 'Taxable' : 'Non-Taxable';
					$line['parts'] = array();
					$line['purchase_price'] = $line['unit_price'];
					$line['std_selling_price'] = $line['std_unit_price'];
					$line['mft_part_num'] = $line['mfr_part_no'];
					if($line['related_type'] == 'ProductCatalog' || $line['related_type'] == 'Assets') {
						$line['product_name'] = $line['name'];
						unset($line['name']);
					}
					else if($line['related_type'] == 'Assemblies' || $line['related_type'] == 'SupportedAssemblies') {
						$line['assembly_name'] = $line['name'];
						unset($line['name']);
					}
				}
				if(isset($parent)) {
					$parent['parts'][] = $line;
					$last[$line['depth']] =& $parent['parts'][count($parent['parts'])-1];
				}
				else {
					$rows[] = $line;
					$last[$line['depth']] =& $rows[count($rows)-1];
				}
			}
			$li['taxes'] = array();
			$li['discounts'] = array();
			$li['shipping'] = '';
			$li['shipping_adj_id'] = '';
			if($grp->adjusts)
			foreach($grp->adjusts as $adj) {
				if($adj['related_type'] == 'TaxRates' || $adj['related_type'] == 'Discounts') {
					$row = array();
					$row['adj_id'] = $adj['id'];
					$row['id'] = $adj['related_id'];
					$row['rate'] = $adj['rate'];
					$row['value'] = $adj['amount'];
					if($adj['related_type'] == 'TaxRates')
						$li['taxes'][] = $row;
					else
						$li['discounts'][] = $row;
				}
				else if($adj['related_type'] == 'ShippingProviders') {
					$li['shipping'] = $adj['amount'];
					$li['shipping_taxed'] = $adj['type'] == 'TaxedShipping' ? 'true' : 'false';
					$li['shipping_adj_id'] = $adj['id'];
				}
			}
			$grppos ++;
		}
		return $line_items;
	}
	
	// convert groups to JSON notation - all groups must have IDs
	function convert_to_array(&$groups, $strip_ids=false) {
		$grp_fields = null;
		$data = array();
		$id_count = 0;
		$line_id_map = array();
		foreach(array_keys($groups) as $idx) {
			if($idx == 'GRANDTOTAL')
				continue;
			if($strip_ids) {
				$gid = '_newgroup~'.($id_count++);
			} else {
				$gid = $idx;
			}
			$data[$gid] = array();
			$grp_data =& $data[$gid];
			if(! isset($grp_fields))
				$grp_fields = $groups[$idx]->column_fields;
			foreach($grp_fields as $f) {
				if($f == 'date_entered' || $f == 'date_modified')
					continue;
				$grp_data[$f] = from_html($groups[$idx]->$f);
			}
			$grp_data['id'] = $gid;
			if ($strip_ids) {
				unset($grp_data['parent_id']);
			}

			$grp_data['lines'] = array();
			if(is_array($groups[$idx]->lines)) {
				foreach($groups[$idx]->lines as $line) {
					if($strip_ids) {
						$line['original_id'] = $line['id'];
						$line['id'] = $this->mapTempId($line['id'], '_newline');
						$line['line_group_id'] = $grp_data['id'];
						if (!empty($line['parent_id'])) {
							$line['parent_id'] = $this->mapTempId($line['parent_id'], '_newline');
						}
						if (!empty($line['pricing_adjust_id'])) {
							$line['pricing_adjust_id'] = $this->mapTempId($line['pricing_adjust_id'], '_newadj');
						}
					}
					if(! empty($line['is_comment']))
						$line['related_type'] = 'Notes';
					if(isset($line['date_entered']))
						unset($line['date_entered']);
					if(isset($line['date_modified']))
						unset($line['date_modified']);
					$grp_data['lines'][$line['id']] = $line;
				}
			}
			$grp_data['lines_order'] = array_keys($grp_data['lines']);
			
			$grp_data['adjusts'] = array();
			if(is_array($groups[$idx]->adjusts)) {
				foreach($groups[$idx]->adjusts as $adj) {
					if($strip_ids) {
						$adj['id'] = $this->mapTempId($adj['id'], '_newadj');
						$adj['line_group_id'] = $grp_data['id'];
						if (!empty($adj['line_id'])) {
							$adj['line_id'] = $this->mapTempId($adj['line_id'], '_newline');
						}
						if(isset($adj['date_entered']))
							unset($adj['date_entered']);
						if(isset($adj['date_modified']))
							unset($adj['date_modified']);
					}
					$grp_data['adjusts'][$adj['id']] = $adj;
				}
			}
			$grp_data['adjusts_order'] = array_keys($grp_data['adjusts']);
		}
		return $data;
	}
	
	// update line groups from submitted form data
	function update_from_array(&$groups, &$newdata, $recalc=true, $date = null, $update_taxes = true) {
		global $app_list_strings;
		$grp_fields = null;
		$id_map = array();
		$deleted = array();
		$lastpos = 0;
		foreach(array_keys($groups) as $k)
			$deleted[$k] = ($k !== 'GRANDTOTAL');
		
		foreach($newdata as $id => $newg) {
			if(isset($newg['id'])) {
				$id = $newg['id'];
				unset($newg['id']);
			}
			if(is_numeric($id) || strpos($id, '~') !== false || ! isset($groups[$id])) {
				$old_id = $id;
				$id = $this->map_id($old_id, create_guid());
				$id_map[$old_id] = $id;
				$groups[$id] =& $this->newForParent($this->parent_bean);
				$groups[$id]->id = $id;
				$groups[$id]->new_with_id = true;
			}
			else {
				$deleted[$id] = false;
			}
			$grp =& $groups[$id];
			if(! isset($grp_fields))
				$grp_fields = $grp->column_fields;
			foreach($grp_fields as $f) {
				if($f == 'date_entered' || $f == 'date_modified')
					continue;
				if(isset($newg[$f]))
					$grp->$f = $newg[$f];
			}
			$grp->parent_id = $this->parent_id;
			
			foreach(array('lines', 'adjusts', 'lines_order', 'adjusts_order') as $f)
				if(! isset($newg[$f]) || ! is_array($newg[$f]))
					$newg[$f] = array();
			$lines = $adjusts = array();
			foreach($newg['lines_order'] as $lid)
				$lines[$lid] = $newg['lines'][$lid];
			foreach($newg['adjusts_order'] as $aid)
				$adjusts[$aid] = $newg['adjusts'][$aid];
			$grp->update_lines($lines, true);
			$grp->update_adjustments($adjusts, true);
			if($recalc)
				$grp->update_totals($date, $update_taxes);
			if(empty($deleted[$id])) {
				$pos = $lastpos + 1;
				if(empty($grp->position) || $grp->position < $pos)
					$grp->position = $pos;
				$lastpos = $grp->position;
			}
		}
		
		foreach($deleted as $id => $v) {
			if($v) {
				$groups[$id]->deleted = 1;
				$groups[$id]->mark_lines_deleted();
			}
		}
		
		$this->calc_grand_totals($groups);
	}

	// used when duplicating line items	(in old format - now obsolete)
	function stripLineIdentifiers(&$line_items) {
		if(! is_array($line_items)) return false;
		foreach($line_items as $idx => $grp) {
			unset($line_items[$idx]['line_group_id']);
			foreach(array_keys($grp['rows']) as $jdx) {
				unset($line_items[$idx]['rows'][$jdx]['line_group_id']);
				unset($line_items[$idx]['rows'][$jdx]['line_id']);
				if(! empty($grp['rows'][$jdx]['parts'])) {
					foreach(array_keys($grp['rows'][$jdx]['parts']) as $kdx) {
						unset($line_items[$idx]['rows'][$jdx]['parts'][$kdx]['line_group_id']);
						unset($line_items[$idx]['rows'][$jdx]['parts'][$kdx]['line_id']);
					}
				}
			}
			foreach(array_keys($grp['taxes']) as $jdx)
				unset($line_items[$idx]['taxes'][$jdx]['adj_id']);
			foreach(array_keys($grp['discounts']) as $jdx)
				unset($line_items[$idx]['discounts'][$jdx]['adj_id']);
			unset($line_items[$idx]['shipping_adj_id']);
		}
		return true;
	}
	
	function update_totals($date = null, $update_taxes = true) {
		static $amounts = array('cost_price', 'list_price', 'unit_price', 'std_unit_price');
		$update_sums = array();
		$subtotal = 0.0;
		$cost = 0.0;
		$tally = array();
		$all_taxcodes = array();
		$discount_taxes = array();
		$old_taxes = $taxes = array();
		$rates = array();


		require_once('modules/TaxCodes/TaxCode.php');
		$seedcode = new TaxCode();
		require_once('modules/TaxRates/TaxRate.php');
		$seedrate = new TaxRate;
		
		$allRates = array();
		foreach ((array)$seedrate->get_full_list() as $r) {
			$rate = array('id' => $r->id, 'name' => $r->name, 'rate' => $r->rate, 'compounding' => $r->compounding);
			$allRates[$r->id] = $rate;
		}

		for(reset($this->lines); list($idx,) = each($this->lines); ) {
			$line =& $this->lines[$idx];
			if(! empty($line['is_comment']) || ! empty($line['deleted']))
				continue;
			if(! empty($line['sum_of_components'])) {
				$update_sums[$line['id']] = array('idx' => $idx);
				foreach($amounts as $f)
					$update_sums[$line['id']][$f] = 0.0;
				$line['ext_quantity'] = $line['quantity'];
				continue;
			}
			if(! empty($line['parent_id']) && ! empty($update_sums[$pid = $line['parent_id']])) {
				foreach($amounts as $f)
					$update_sums[$pid][$f] += $line[$f] * $line['quantity'];
				$line['ext_quantity'] = sprintf('%0.2f', $line['quantity'] * $this->lines[$update_sums[$pid]['idx']]['ext_quantity']);
			}
			else
				$line['ext_quantity'] = $line['quantity'];

			//if (empty($line['ext_price'])) {
				$line['ext_price'] = currency_round($line['unit_price'] * $line['ext_quantity'], $this->currency_decimals);
			//}
			if(empty($line['sum_of_components'])) {
				$tally[] = array(
					'unadj_amount' => $line['ext_price'],
					'amount' => $line['ext_price'],
					'taxcode' => array_get_default($line, 'tax_class_id'),
				);
				$subtotal += $line['ext_price'];
				if(! empty($line['tax_class_id']) && $line['tax_class_id'] != '-99')
					$all_taxcodes[$line['tax_class_id']] = 1;
				if(isset($line['cost_price']))
					$cost += currency_round($line['ext_quantity'] * $line['cost_price'], $this->currency_decimals);
			}
			foreach ($this->adjusts as $adj) {
				if (array_get_default($adj, 'line_id') != $line['id']) continue;
				if($adj['type'] == 'StandardTax' || $adj['type'] == 'CompoundedTax') {
					if (isset($allRates[$adj['related_id']])) {
						$r = $allRates[$adj['related_id']];
						$type = $r['compounding'] != 'compounded' ? 'StandardTax' : 'CompoundedTax';
						$taxuid = $type  . '~' .  $r['id'] . '~' . $r['rate'];
						$r['editable'] = 1;
						$r['line_id'] = $line['id'];
						$rates[] = $r;
					}
				}
			}
		}
		foreach($update_sums as $upd) {
			$line =& $this->lines[$upd['idx']];
			foreach($amounts as $f)
				$line[$f] = currency_round($upd[$f], $this->currency_decimals);
			if (empty($line['ext_price'])) {
				$line['ext_price'] = currency_round($line['ext_quantity'] * $line['unit_price'], $this->currency_decimals);
			}
		}
		$this->subtotal = $subtotal = $total = currency_round($subtotal, $this->currency_decimals);

		if(! is_array($this->adjusts))
			$this->adjusts = array();
		
		foreach($this->adjusts as $idx => $adj) {
			if(! empty($adj['deleted'])) continue;
			if(! empty($adj['line_id'])) {
				if ($adj['related_type'] == 'TaxRates') {
					$total += $adj['amount'];
				}
				continue;
			}
			if ($adj['type'] == 'TaxedShipping') {
				if (empty($adj['tax_class_id'])) {
					$adj['tax_class_id'] = 'all';
				}
				if ($adj['tax_class_id'] != 'all' && $adj['tax_class_id'] != '-99') {
					$all_taxcodes[$adj['tax_class_id']] = 1;
				}
			}
			if($adj['type'] == 'StandardDiscount' || $adj['type'] == 'StdPercentDiscount') {
				$adj['amount'] = 0.0;
				foreach($tally as $tdx => $tally_row) {
					$disc = currency_round($tally_row['unadj_amount'] * $adj['rate'] / 100, $this->currency_decimals);
					$tally[$tdx]['amount'] -= $disc;
					$adj['amount'] -= $disc;
				}
				$this->adjusts[$idx]['amount'] = -$adj['amount'];
				if (!empty($adj['tax_class_id']) && $adj['tax_class_id'] != '-99') {
					$r = $seedcode->get_tax_rates($adj['tax_class_id'], false, $date);
					foreach ($r as $rate) {
						$discount_taxes[$rate['id']] = 
							array_get_default($discount_taxes, $rate['id'], 0.0) + $adj['amount'] * $rate['rate'] / 100.0;
					}
				}
			}
			else if($adj['type'] == 'StdFixedDiscount') {
				$disc = $adj['amount'];
				if (count($tally) > 0) $tally[0]['amount'] -= $disc;//because we need subtract fixed discount one time
				$adj['amount'] = -$adj['amount'];
			}
			else if($adj['type'] == 'TaxedShipping') {
				$tally[] = array('amount' => $adj['amount'], 'unadj_amount' =>$adj['amount'],  'taxcode' => $adj['tax_class_id']);
			}
			else if($adj['type'] == 'StandardTax' || $adj['type'] == 'CompoundedTax') {
				if ($update_taxes) {
					$taxuid = $adj['type'] .'~'. $adj['related_id'] .'~'. $adj['rate'];
					if (empty($adj['editable'])) {
						$old_taxes[$taxuid] = $adj;
					} else {
						$adj['amount'] = 0.0;
						$taxes[$taxuid] = $adj;
						if (isset($allRates[$adj['related_id']])) {
							$r = $allRates[$adj['related_id']];
							$r['editable'] = 1;
							$rates[] = $r;
						}
					}
					unset($this->adjusts[$idx]);
					continue; // do not add yet
				}
			}
			$total += $adj['amount'];
		}

		if ($update_taxes) {
			foreach($all_taxcodes as $code_id => $_z) {
				$r = $seedcode->get_tax_rates($code_id, false, $date);
				foreach($r as $rr) {
					$rr['code_id'] = $code_id;
					$rates[] = $rr;
				}
			}
			
			foreach($rates as $rate) {
				$type = $rate['compounding'] ? 'CompoundedTax' : 'StandardTax';
				$taxuid = $type .'~'. $rate['id'] .'~'. $rate['rate'];
				if(! isset($taxes[$taxuid])) {
					$adj = array(
						'type' => $type,
						'name' => $rate['name'],
						'related_type' => 'TaxRates',
						'related_id' => $rate['id'],
						'rate' => $rate['rate'],
						'amount' => array_get_default($discount_taxes, $rate['id'], 0.0),
					);
					$adj[$this->rel_id_field] = $this->parent_id;
					if(isset($old_taxes[$taxuid])) {
						$adj['id'] = $old_taxes[$taxuid]['id'];
						$adj['position'] = $old_taxes[$taxuid]['position'];
						unset($old_taxes[$taxuid]);
					}
					else {
						$adj['id'] = create_guid();
						$this->adjust_changes['add'][$adj['id']] = true;
					}
					$taxes[$taxuid] = $adj;
				}
				$adjamt = $taxes[$taxuid]['amount'];
				foreach($tally as $tdx => $tally_row) {
					$code = $seedcode->retrieve($tally_row['taxcode']);
					if($code->taxation_scheme == 'list' && empty($rate['editable']) && ($tally_row['taxcode'] == $rate['code_id'] || $tally_row['taxcode'] == 'all')) {
						if (empty($rate['line_id']) || @$tally_row['id'] == @$rate['line_id']) {
							$prevtax = array_get_default($tally_row, 'tax', 0.0);
							if (empty($this->parent_bean->discount_before_taxes)) {
								$base = $tally_row['amount'];
							} else {
								$base = $tally_row['unadj_amount'];
							}
							if($rate['compounding'])
								$base += $prevtax;
							$amt = $base * $rate['rate'] / 100;
							$tally[$tdx]['tax'] = $prevtax + $amt;
							$adjamt += $amt;
						}
					}
					if ($code->id && $code->taxation_scheme == 'tax' && !empty($rate['editable'])) {
						$prevtax = array_get_default($tally_row, 'tax', 0.0);
						if (empty($this->parent_bean->discount_before_taxes)) {
							$base = $tally_row['amount'];
						} else {
							$base = $tally_row['unadj_amount'];
						}
						if($rate['compounding'])
							$base += $prevtax;
						$amt = $base * $rate['rate'] / 100;
						$tally[$tdx]['tax'] = $prevtax + $amt;
						$adjamt += $amt;
					}
				}
				$taxes[$taxuid]['amount'] = currency_round($adjamt, $this->currency_decimals);
			}

			$seedcode->cleanup();
			foreach($taxes as $taxuid => $adj) {
				$this->adjusts[] = $adj;
				$total += $adj['amount'];
			}
			foreach($old_taxes as $taxuid => $adj) {
				$this->adjust_changes['del'][$adj['id']] = true;
			}
		}
		
		$this->total = currency_round($total, $this->currency_decimals);
		$this->cost = currency_round($cost, $this->currency_decimals);
	}
	
	function retrieve($id, $encode=true, $fetch_related=false) {
		$ret = parent::retrieve($id, $encode);
		if($fetch_related && $ret && $this->parent_bean) {
			$this->lines =& $this->retrieve_lines($this->parent_id, $this->id);
			$this->adjusts =& $this->retrieve_adjustments($this->parent_id, $this->id);
		}
		return $ret;
	}
	
	function &retrieve_all($encode=true) {
		$groups = array();
		$gtotal =& $this->newForParent($this->parent_bean);
		$gtotal->id = 'GRANDTOTAL';
		$groups['GRANDTOTAL'] =& $gtotal;
		if($this->parent_bean && $this->parent_id) {
			$all_lines = $this->retrieve_lines($this->parent_id);
			$all_adjs = $this->retrieve_adjustments($this->parent_id);
			$query = sprintf(
					"SELECT * FROM `$this->table_name` groups ".
					"WHERE groups.parent_id='%s' AND NOT groups.deleted",
					$this->db->quote($this->parent_id)
			);
			$result = $this->db->query($query, true, "Error retrieving related groups");
			while($row = $this->db->fetchByAssoc($result,-1,false)) {
				$bean =& $this->newForParent($this->parent_bean);
				if($bean->retrieve($row['id'], $encode)) {
					if(! empty($all_lines[$row['id']]))
						$bean->lines = $all_lines[$row['id']];
					if(! empty($all_adjs[$row['id']]))
						$bean->adjusts = $all_adjs[$row['id']];
				}
				$groups[$bean->id] = $bean;
			}
		}
		$this->calc_grand_totals($groups);
		return $groups;
	}
	
	function &retrieve_lines($parent_id, $group_id=null, $fetch_comments=true) {
		$child = new $this->line_object();
		$cmt_child = new $this->comment_object();
		$lines = array();
		$comments = array();
		$grp_filter = $group_id ? "AND lines.line_group_id='".$this->db->quote($group_id)."'" : '';
		$query = sprintf(
				"SELECT * FROM `$child->table_name` lns ".
				"WHERE lns.{$this->rel_id_field}='%s' $grp_filter AND NOT lns.deleted ".
				"ORDER BY lns.position",
				$this->db->quote($parent_id)
		);
		$result = $this->db->query($query, true, "Error retrieving group lines");
		while($row = $this->db->fetchByAssoc($result,-1,false))
			$lines[] = $row;
		if($fetch_comments) {
			$query = sprintf(
					"SELECT * FROM `$cmt_child->table_name` cmts ".
					"WHERE cmts.{$this->rel_id_field}='%s' $grp_filter AND NOT cmts.deleted ".
					"ORDER BY cmts.position",
					$this->db->quote($parent_id)
			);
			$result = $this->db->query($query, true, "Error retrieving group comment lines");
			while($row = $this->db->fetchByAssoc($result,-1,false)) {
				$row['is_comment'] = 1;
				$comments[] = $row;
			}
		}
		$j = 0;
		$c_lines = count($lines); $c_comments = count($comments);
		$all = array();
		$depths = array();
		$nest_under = array();
		for($i = 0; $i < $c_lines || $j < $c_comments;) {
			unset($row);
			if($i >= $c_lines || ($j < $c_comments && $comments[$j]['position'] < $lines[$i]['position']))
				$row =& $comments[$j++];
			else
				$row =& $lines[$i++];
			if($row['parent_id'] && isset($depths[$row['parent_id']]) && $nest_under[$row['parent_id']])
				$row['depth'] = $depths[$row['parent_id']] + 1;
			else
				$row['depth'] = 0;
			$depths[$row['id']] = $row['depth'];
			$nest_under[$row['id']] = ! empty($row['sum_of_components']);
			if(empty($group_id))
				$all[$row['line_group_id']][$row['id']] = $row;
			else
				$all[$row['id']] = $row;
		}
		$child->cleanup();
		$cmt_child->cleanup();
		return $all;
	}

	// group_id may be empty to retrieve quote-level adjustments
	function &retrieve_adjustments($parent_id, $group_id=null) {	
		$child = new $this->adj_object();
		$adjusts = array();
		$grp_filter = $group_id ? "AND adjs.line_group_id='".$this->db->quote($group_id)."'" : '';
		$query = sprintf(
				"SELECT * FROM `$child->table_name` adjs ".
				"WHERE adjs.{$this->rel_id_field}='%s' $grp_filter AND NOT adjs.deleted ".
				"ORDER BY adjs.position",
				$this->db->quote($parent_id)
		);
		$result = $this->db->query($query, true, "Error retrieving group adjustments");
		while($row = $this->db->fetchByAssoc($result,-1,false)) {
			if(empty($group_id)) {
				$gid = empty($row['line_group_id']) ? 'GRANDTOTAL' : $row['line_group_id'];
				$adjusts[$gid][$row['id']] = $row;
			}
			else
				$adjusts[$row['id']] = $row;
		}
		$child->cleanup();
		return $adjusts;
	}
	
	function _normalize_row(&$new_row) {
		if (isset($new_row['line_id']) && strpos($new_row['line_id'], '~') !== false) {
			$new_row['line_id'] = $this->map_id($new_row['line_id']);
		}
		if (isset($new_row['pricing_adjust_id']) && strpos($new_row['pricing_adjust_id'], '~') !== false) {
			$padjid = $new_row['pricing_adjust_id'];
			$new_row['pricing_adjust_id'] = $this->map_id($new_row['pricing_adjust_id'], create_guid());
			if (isset($new_row['adjusts'][$padjid])) {
				$adj = $new_row['adjusts'][$padjid];
				unset($new_row['adjusts'][$padjid]);
				$adj['id'] = $new_row['pricing_adjust_id'];
				$new_row['adjusts'][$adj['id']] = $adj;
			}
		}
		$new_row[$this->rel_id_field] = $this->parent_id;
		if(empty($new_row['depth']))
			$new_row['depth'] = 0;
		$old_id = '';
		if(empty($new_row['id']))
			$rid = $new_row['id'] = create_guid();
		else if(strpos($new_row['id'], '~') !== false) {
			$old_id = $new_row['id'];
			$rid = $this->map_id($old_id, create_guid());
			$new_row['id'] = $rid;
		}
		else
			$old_id = $new_row['id'];
		return $old_id;
	}
	
	function _update_row(&$old_row, &$new_row) {
		$changed = false;
		foreach($new_row as $k => $v) {
			if($k == 'date_entered' || $k == 'date_modified') {
				unset($new_row[$k]);
				continue;
			}
			if(is_array($v) || $k == 'depth' || ($k == 'related_type' && ! empty($new_row['is_comment']))) {
				continue;
			}
			if(! isset($old_row[$k]) || $old_row[$k] != $v) {
				if(isset($v) || isset($old_row[$k])) {
					$changed = true;
				}
			}
			$old_row[$k] = $v;
		}
		return $changed;
	}
	
	function _merge_rows(&$old_rows, &$new_rows, &$changes, $delete_rest=false, $reposition=true) {
		if(! is_array($old_rows))  $old_rows = array();
		if(! is_array($changes))  $changes = array('add' => array(), 'upd' => array(), 'del' => array());
		if(! is_array($new_rows))  return;
		$row_idx = array();
		$last_idx = 0;
		foreach(array_keys($old_rows) as $idx) {
			$rid = $old_rows[$idx]['id'];
			$row_idx[$rid] = $idx;
			$last_idx ++;
		}
		$new_ids = array();
		$pend_add = array();
		$in_new_rows = array();
		$depth_last_id = array();
		$last_row_id = '';
		$lastpos = -1;
		foreach(array_keys($new_rows) as $idx) {
			$new_row =& $new_rows[$idx];
			$old_id = $this->_normalize_row($new_row);
			$rid = $new_row['id'];
			if($old_id != $rid) {
				$is_new = true;
				if($old_id)
					$new_ids[$old_id] = $rid;
			}
			else if(isset($row_idx[$rid])) {
				$is_new = false;
				$old_idx = $row_idx[$rid];
				if($this->_update_row($old_rows[$old_idx], $new_row))
					$changes['upd'][$rid] = true;
				if($reposition){
					if($old_rows[$old_idx]['position'] <= $lastpos) {
						$lastpos ++;
						$old_rows[$old_idx]['position'] = $lastpos;
						$changes['upd'][$rid] = true;
					}
					else
						$lastpos = $old_rows[$old_idx]['position'];
				}
			}
			else {
				// row has a valid (non-temporary) ID, but does not exist already - this should not happen
				continue;
			}
			$in_new_rows[$rid] = true;
			$depth = $new_row['depth'];
			if($is_new) {
				$changes['add'][$rid] = true;
				if(empty($new_row['parent_id']))
					$new_row['parent_id'] = empty($depth_last_id[$depth-1]) ? '' : $depth_last_id[$depth-1];
				else if(isset($new_ids[$new_row['parent_id']]))
					$new_row['parent_id'] = $new_ids[$new_row['parent_id']];
				$pend_add[$last_row_id][] = $new_row;
			}
			$last_row_id = $depth_last_id[$depth] = $rid;
		}
		
		// rebuild old_rows if necessary
		if(count($pend_add) || ($delete_rest && count($old_rows) != count($in_new_rows))) {
			$lastpos = -1;
			$rows = $old_rows;
			$old_rows = array();
			$delete_pids = array();
			$last_row_id = '';
			foreach($rows as $row) {
				$rid = $row['id'];
				while(! empty($pend_add[$last_row_id])) {
					$ins_id = $last_row_id;
					foreach($pend_add[$last_row_id] as $ins_row) {
						$lastpos ++;
						$ins_row['position'] = $lastpos;
						$old_rows[] = $ins_row;
						$ins_id = $ins_row['id'];
					}
					unset($pend_add[$last_row_id]);
					$last_row_id = $ins_id;
				}
				if(empty($in_new_rows[$rid]) ||
					(! empty($row['parent_id']) && ! empty($delete_pids[$row['parent_id']]))) {
						$delete_pids[$rid] = true;
						$changes['del'][$rid] = true;
						$row['deleted'] = 1;
						$old_rows[] = $row;
				}
				else {
					if($reposition && $row['position'] <= $lastpos) {
						$lastpos ++;
						$row['position'] = $lastpos;
						$changes['upd'][$rid] = true;
					}
					else
						$lastpos = $row['position'];
					$old_rows[] = $row;
				}
				$last_row_id = $rid;
			}
			// add any rows missed earlier
			foreach($pend_add as $ins_rows) {
				foreach($ins_rows as $ins_row) {
					$lastpos += 5;
					$ins_row['position'] = $lastpos;
					$old_rows[] = $ins_row;
				}
			}
		}
	}
	
	function update_lines(&$upd_lines, $delete_rest=false) {
		$this->_merge_rows($this->lines, $upd_lines, $this->line_changes, $delete_rest);
	}
		
	function _adj_class(&$adj) {
		switch($adj['type']) {
			case 'StandardDiscount':
			case 'StdPercentDiscount':
			case 'StdFixedDiscount':
				return 5;
			case 'TaxedShipping':
				return 10;
			case 'CompoundedTax':
			case 'StandardTax':
				return 15;
			case 'UntaxedShipping':
				return 20;
			default:
				return 25;
		}
	}
	
	function update_adjustments(&$upd_adjusts, $delete_rest=false) {
		$this->_merge_rows($this->adjusts, $upd_adjusts, $this->adjust_changes, $delete_rest, false);
		// enforce ordering
		$clscount = array();
		$lastpos = 0;
		$idxs = array_keys($this->adjusts);
		if(! count($idxs))
			return;
		$line_ids = array();
		foreach($this->lines as $idx => $line)
			if(! empty($line['id']) && empty($line['deleted']) && empty($line['is_comment'])
			&& empty($this->line_changes['del'][$line['id']]))
				$line_ids[$line['id']] = $idx;
		foreach($idxs as $idx) {
			$adj =& $this->adjusts[$idx];
			if(! empty($adj['line_id'])) {
				if(! isset($line_ids[$adj['line_id']])) {
					$adj['deleted'] = '1';
					$this->adjust_changes['del'][$adj['id']] = 1;
				}
			}
			if(! empty($adj['deleted'])) continue;
			$cls = $this->_adj_class($adj);
			@$clscount[$cls] ++;
		}
		if(! count($clscount))
			return;
		$maxcls = max(array_keys($clscount));
		$clspos = array();
		$pos = 0;
		for($cls = 0; $cls <= $maxcls; $cls++) {
			$clspos[$cls] = $pos;
			$pos += @$clscount[$cls];
		}
		foreach($idxs as $idx) {
			$adj =& $this->adjusts[$idx];
			if(! empty($adj['deleted'])) continue;
			$cls = $this->_adj_class($adj);
			$pos = $clspos[$cls];
			$clspos[$cls] ++;
			if($pos != $adj['position'])
				$this->adjust_changes['upd'][$adj['id']] = true;
			$adj['position'] = $pos;
		}
	}
	
	function &get_currency($id=null, $reload=false) {
		if($id == null) {
			$set_exch = true;
			$id = $this->parent_bean->currency_id;
		}
		$currency = $this->get_cache_object($id, 'Currencies', '', $reload);
		if(empty($currency->id))
			$currency->retrieve('-99');
		if(isset($set_exch)) {
			$rate = $this->get_exchange_rate();
			if($rate) $currency->conversion_rate = $rate;
		}
		return $currency;
	}
	
	function get_exchange_rate() {
		if(empty($this->parent_bean->exchange_rate))
			return '';
		$rate = $this->parent_bean->exchange_rate;
		if(! empty($this->parent_bean->number_formatting_done))
			$rate = unformat_number($rate);
		return $rate;
	}
	
	function &get_timedate() {
		global $timedate;
		return $timedate;
	}
	
	function &get_complete_list($cls) {
		global $beanFiles;
		require_once($beanFiles[$cls]);
		$seed = new $cls();
		$query = "SELECT * FROM `{$seed->table_name}`";
		$result = $this->db->query($query, true, "Error retrieving $cls list");
		$ret = array();
		while($row = $this->db->fetchByAssoc($result, -1, false)) {
			$obj = new $cls();
			$obj->populateFromRow($row);
			$ret[$obj->id] = $obj;
		}
		$seed->cleanup();
		return $ret;
	}
	
	function get_cache_object($id, $module, $cls='', $reload=false) {
		if(! isset($this->_object_cache)) $this->_object_cache = array();
		$cache =& $this->_object_cache;
		global $beanList;
		if(! $cls) $cls = $beanList[$module];
		if(! isset($cache[$cls]) || $reload)
			$cache[$cls] = $this->get_complete_list($cls);
		return isset($cache[$cls][$id]) ? $cache[$cls][$id] : new $cls();
	}
	
	function get_tax($id, $reload=false) {
		return $this->get_cache_object($id, 'TaxRates');
	}
	
	function get_discount($id, $reload=false) {
		return $this->get_cache_object($id, 'Discounts');
	}
	
	function get_shipper($id, $reload=false) {
		return $this->get_cache_object($id, 'ShippingProviders');
	}
	
	function _save_rows(&$rows, &$changes, &$bean, $set_grp_id=false, $do_comments=false) {
		if(! is_array($changes))
			return;
		$queries = array();
		$timedate = $this->get_timedate();
		$dt_now = gmdate($timedate->get_db_date_time_format());

		if(! isset($rows) || ! is_array($rows))
			$rows = array();
		foreach(array_keys($rows) as $idx) {
			$row =& $rows[$idx];
		
			if(empty($row['id']) || empty($row[$this->rel_id_field]))
				continue; // bad data
			if($do_comments) {
				if(empty($row['is_comment']))
					continue;
			}
			else if(! empty($row['is_comment']))
				continue;
			if(!isset($row['depth']))
				$row['depth'] = 0;
			
			foreach($this->line_prices_arr as $f) {
				if(isset($row[$f]))
					// enforce maximum decimal places
					$row[$f] = round($row[$f], 5);
			}
			
			if($set_grp_id)
				$row['line_group_id'] = $set_grp_id;
			$rid = $this->db->quote($row['id']);
			if(! empty($changes['del'][$rid]) || $this->deleted) {
				$queries[] = "UPDATE `$bean->table_name` SET deleted=1, date_modified='$dt_now' WHERE id='$rid'";
				// $queries[] = "DELETE FROM `$bean->table_name` WHERE id='$rid'";
			}
			else if(! empty($changes['add'][$rid])) {
				$row['date_entered'] = $row['date_modified'] = $dt_now;
				if(! $bean->pre_insert_row($this, $row))
					continue;
				$q = array();
				foreach($rows[$idx] as $k => $v) {
					if(is_null($v))
						$q[] = "`$k`=null";
					else {
						$v = $this->db->quote($v);
						$q[] = "`$k`='$v'";
					}
				}
				$queries[] = "INSERT INTO `$bean->table_name` SET ".implode(', ', $q);
			}
			else if(! empty($changes['upd'][$rid])) {
				$row['date_modified'] = $dt_now;
				if(! $bean->pre_update_row($this, $row))
					continue;
				unset($row['id']);
				$q = array();
				foreach($row as $k => $v) {
					if(is_null($v))
						$q[] = "`$k`=null";
					else {
						$v = $this->db->quote($v);
						$q[] = "`$k`='$v'";
					}
				}
				$queries[] = "UPDATE `$bean->table_name` SET ".implode(', ', $q)." WHERE id='$rid'";
			}
		}
		foreach($queries as $query) {
			$this->db->query($query, true, "Error updating {$bean->object_name} lines");
		}
	}
	
	function mark_lines_deleted() {
		if($this->lines)
			foreach(array_keys($this->lines) as $idx) {
				$line =& $this->lines[$idx];
				$line['deleted'] = 1;
				$this->line_changes['del'][$line['id']] = true;
			}
		if($this->adjusts)
			foreach(array_keys($this->adjusts) as $idx) {
				$adj =& $this->adjusts[$idx];
				$adj['deleted'] = 1;
				$this->adjust_changes['del'][$adj['id']] = true;
			}
	}

	function save($check_notify=false) {
		if(empty($this->id)) {
			$this->id = create_guid();
			$this->new_with_id = true;
		}

		$this->unformat_all_fields();
		$currency =& $this->get_currency();
		foreach($this->currency_fields as $f => $f_u) {
			$raw = isset($this->$f) ? $this->$f : '';
			$this->$f_u = $currency->convertToDollar($raw);
		}
		
		$ret = '';
		

		// prevent saving of 'grand total' pseudo group
		if($this->id === 'GRANDTOTAL') {
			//$this->_save_rows($this->adjusts, $this->adjust_changes, $adj, '');
			return $this->id;
		}
		else {
			$ret = parent::save($check_notify);
			if(! $ret)
				return $ret;
		}
		
		$line = new $this->line_object();
		$comment = new $this->comment_object();
		$adj = new $this->adj_object();
		$this->_save_rows($this->lines, $this->line_changes, $line, $this->id);
		$this->_save_rows($this->lines, $this->line_changes, $comment, $this->id, true);
		$this->line_changes = array();
		$this->_save_rows($this->adjusts, $this->adjust_changes, $adj, $this->id);
		$this->adjust_changes = array();
		$line->cleanup();
		$comment->cleanup();
		$adj->cleanup();
		return $ret;
	}

	function map_id($old, $new = null)
	{
		if (isset($this->id_map[$old])) return $this->id_map[$old];
		if (is_null($new)) {
			return $old;
		}
		$this->id_map[$old] = $new;
		return $new;
	}

	function mapTempId($id, $prefix)
	{
		static $counter = 0;
		if (empty($this->tempIdMap)) $this->tempIdMap = array();
		if (!isset($this->tempIdMap[$id])) {
			$this->tempIdMap[$id] = $prefix . '~' . ($counter++);
		}
	   	return $this->tempIdMap[$id];
	}
	
	function cleanup() {
		unset($this->parent_bean);
		if(isset($this->lines)) {
			$this->cleanup_list($this->lines);
			unset($this->lines);
		}
		if(isset($this->adjusts)) {
			$this->cleanup_list($this->adjusts);
			unset($this->adjusts);
		}
		if(isset($this->_object_cache)) {
			foreach(array_keys($this->_object_cache) as $k)
				$this->cleanup_list($this->_object_cache[$k]);
			unset($this->_object_cache);
		}
		parent::cleanup();
	}
}

?>
