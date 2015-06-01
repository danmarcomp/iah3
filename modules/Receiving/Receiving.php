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
require_once('modules/Receiving/ReceivingFormBase.php');
require_once('modules/Receiving/ReceivingLineGroup.php');
require_once('modules/Accounts/Account.php');

class Receiving extends SugarBean {
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id, $modified_by_name;
	var $created_by, $created_by_name;
	var $assigned_user_id, $assigned_user_name;
	//
	var $name;
	var $description;
	var $due_date;	
	var $terms;
	var $prefix;
	var $receiving_number;
	var $receiving_stage;
	var $po_id;
	var $opportunity_id, $opportunity_name;

	var $warehouse_id;
	var $warehouse_name;

	var $billing_account_name, $billing_account_id;
	var $billing_address_street, $billing_address_city, $billing_address_state, $billing_address_postalcode, $billing_address_country;
	// Used by quick-creation form
	var $account_id;
	//
	//
	var $currency_id, $exchange_rate;
	var $shipping_provider_id;
	var $tax_information;

	var $amount, $amount_usdollar;
	var $cancelled;
	var $show_components;
	var $tax_exempt;
	var $receiving_cost;

	//
	var $allocated; // for subpanel on payments
	
	// Static members
	var $table_name = "receiving";
	var $object_name = "Receiving";
	var $group_object_name = "ReceivingLineGroup";
	var $module_dir = "Receiving";
	var $new_schema = true;
	//
	var $account_table = "accounts";
	var $contact_table = "contacts";
	
	var $additional_column_fields = Array(
		'assigned_user_name',
	   	'line_items',
		'account_id',
		'highlight',
		'related_quote_name',
		'modified_by_name',
		'created_by_name',
		'opportunity_name',
		'po_name',
		'cm_name',
		'po_number',
		'warehouse_name',
		'supplier_name',
	);


	static $inherit_po_fields = array(
		'assigned_user_id',
		'assigned_user_name',
		'name',
		'currency_id',
		'exchange_rate',
		'shipping_provider_id',
		'description',
		'show_components',
		'id' => 'po_id',
		'supplier_id',
		'supplier_name',
	);
	


	function getCurrentPrefix()
	{
		return AppConfig::get_sequence_prefix('receiving_prefix');
	}
	

	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('receiving_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('receiving_sequence');
	}
	

	// obsolete
	function fetch_line_items($reload=false) {
		$this->get_line_groups(true, $reload);
	}
	
	function &get_line_groups($set_old_items=false, $reload=false) {
		if($reload || ! isset($this->line_groups) || ! is_array($this->line_groups)) {
			$lgm =& $this->get_line_group_manager();
			$encode = empty($this->pdf_output_mode);
			$this->line_groups =& $lgm->retrieve_all($encode);
			if($set_old_items) {
				$items =& $lgm->lineItemsFromGroups($this->line_groups);
				$this->line_items = $items;
			}
			$lgm->cleanup();
		}
		return $this->line_groups;
	}
	
	function cleanup_line_groups(&$groups) {
		$this->cleanup_list($groups);
	}
	
	function &get_line_group_manager() {
		$ret = ReceivingLineGroup::newForParent($this);
		return $ret;
	}
	
	function line_items_editable() {
		return true;
	}
	
	
	function save($check_notify = FALSE)
	{
		// need an ID to save line items
		if(empty($this->id)) {
			$this->id = create_guid();
			$this->new_with_id = true;
		}
		
		// must do this before manipulating numeric fields
		$this->unformat_all_fields();

		$this->save_line_groups();

		//$this->update_shipping_stage();

		$ret = parent::save($check_notify);

		return $ret;
	}

	
	function save_line_groups() {
		if(isset($this->line_groups) && is_array($this->line_groups)) {
			foreach(array_keys($this->line_groups) as $k)
				$this->line_groups[$k]->save();
			$total = $this->line_groups['GRANDTOTAL']->total;
			$this->amount = $this->number_formatting_done ? format_number($total) : sprintf('%0.2f', $total);
		}
	}

	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? "receiving.receiving_stage != 'Received'" : '1';
	}
	
	function getDefaultListWhereClause()
	{
		return "receiving.receiving_stage != 'Received'";
	}

	function is_drop_ship() {
		if(! $this->po_id)
			return false;
		require_bean('PurchaseOrder');
		$po = new PurchaseOrder();
		if(! $po->retrieve($this->po_id))
			return false;
		return $po->drop_ship;
	}

	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(receiving.receiving_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(receiving.prefix, receiving.receiving_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
		}
	}
	
	function cleanup() {
		if(isset($this->line_groups)) {
			$this->cleanup_line_groups($this->line_groups);
			unset($this->line_groups);
		}
		parent::cleanup();
	}

	function getTaxDate() {
		return null;
	}

    /**
     * @return bool
     */
    function updatePOStatus() {
        $po_received = true;

        if (! empty($this->po_id)) {
            $po_result = ListQuery::quick_fetch('PurchaseOrder', $this->po_id);

            if ($po_result) {
                $po_lines = $this->getPOLines();
                $received_lines = $this->getReceivedLines();

                $diff = $this->getItemsDiff($po_lines, $received_lines);
                $po_received = empty($diff);

                $po_update = RowUpdate::for_result($po_result);
                $po_update->set('shipping_stage', $po_received ? 'Received' : 'Partially Received');
                $po_update->save();
            }
        }

        return $po_received;
    }

    /**
     * @return array
     */
    function getPOLines() {
        $query = "SELECT purchase_order_lines.related_id, purchase_order_lines.ext_quantity, purchase_order_lines.name
          FROM purchase_order_lines
          LEFT JOIN purchase_orders ON purchase_orders.id = purchase_order_lines.purchase_orders_id
          WHERE purchase_orders.id = '{$this->po_id}' AND purchase_orders.deleted = 0
          AND purchase_order_lines.deleted = 0 AND purchase_order_lines.related_type = 'ProductCatalog'";

        $res = $this->db->query($query, true);
        $lines = array();

        while ($row = $this->db->fetchByAssoc($res)) {
            $key = empty($row['related_id']) ? $row['name'] : $row['related_id'];

            if (!isset($lines[$key]))
                $lines[$key] = 0;

            $lines[$key] += $row['ext_quantity'];
        }

        return $lines;
    }

    /**
     * @return array
     */
    function getReceivedLines() {
        $query = " SELECT receiving_lines.related_id, receiving_lines.serial_numbers,
          receiving_lines.ext_quantity, receiving_lines.name
          FROM receiving_lines
          LEFT JOIN receiving ON receiving.id = receiving_lines.receiving_id
          WHERE (receiving_lines.receiving_id = '{$this->id}' OR (receiving.po_id = '{$this->po_id}' AND receiving.deleted = 0))
          AND receiving_lines.deleted = 0 AND receiving_lines.related_type = 'ProductCatalog'";

        $res = $this->db->query($query, true);
        $lines = array();

        while ($row = $this->db->fetchByAssoc($res)) {
            $key = empty($row['related_id']) ? $row['name'] : $row['related_id'];

            if (! isset($lines[$key]))
                $lines[$key] = 0;

            $lines[$key] += $row['ext_quantity'];
        }

        return $lines;
    }

    /**
     * Get difference between two line_items
     *
     * @param array $lines1
     * @param array $lines2
     * @return array
     */
    function getItemsDiff($lines1, $lines2) {
        $diff = array();

        foreach ($lines1 as $k => $v) {
            $item1= array_get_default($lines1, $k, null);
            $item2 = array_get_default($lines2, $k, 0);

            if ($item1) {
                if ($item1 > $item2) {
                    $diff[$k] = $item1 - $item2;
                }
            }
        }

        return $diff;
    }

    static function init_from_tally(RowUpdate &$self, RowUpdate &$tally) {
   		$map = self::$inherit_po_fields;
   		foreach ($map as $key => $value) {
   			if(is_int($key)) $key = $value;
   			$fv = $tally->getField($key);
   			if(isset($fv))
   				$self->set($value, $fv);
   		}

   		$existing = array();

   		$type = $tally->getModelName();
   		if ($type == 'Invoice') {
   			// credit memo
   			$lines_table = 'invoice_lines';
   			$order_table = 'invoice';
   			$order_id = 'invoice_id';
   		} else {
   			// purchase order
   			$lines_table = 'purchase_order_lines';
   			$order_table = 'purchase_orders';
   			$order_id = 'po_id';
   		}
   		$tally_id = $tally->getPrimaryKeyValue();

		if($type == 'PurchaseOrder') {
			$query = "
				SELECT
					receiving_lines.*
				FROM receiving_lines
				LEFT JOIN receiving
					ON receiving_lines.receiving_id=receiving.id AND receiving.deleted=0
				WHERE receiving_lines.deleted = 0 AND receiving.$order_id = '{$tally_id}'
				";
			$res = $GLOBALS['db']->query($query, true);
			while ($row = $GLOBALS['db']->fetchByAssoc($res)) {
				$prodid = $row['related_id'] ? 'id~'.$row['related_id'] : $row['name'];
				if(isset($existing[$prodid]))
					$existing[$prodid] += $row['ext_quantity'];
				else
					$existing[$prodid] = $row['ext_quantity'];
			}
		}

   		$groups = $tally->getGroups(true);
   		foreach($groups as $idx => &$g) {
   			if(@$g['group_type'] == 'service') {
   				unset($groups[$idx]);
   				continue;
   			}
   			$g['group_type'] = 'products';
   			$nlines = array();
   			$seen = array();
   			if (is_array($g['lines'])) {
   				foreach ($g['lines'] as $lid => &$line) {
   					if (! empty($line['is_comment'])) {
   						continue;
   					}
   					if ($line['related_type'] != 'ProductCatalog') {
						unset($g['lines'][$lid]);
   					} else {
						$prodid = $line['related_id'] ? 'id~'.$line['related_id'] : $line['name'];
						if (isset($existing[$prodid])) {
							$diff = min($existing[$prodid], $line['ext_quantity']);
							$line['ext_quantity'] -= $diff;
							$existing[$prodid] -= $diff;
						}
						$qty = $line['quantity'] = $line['ext_quantity'];
						$line['parent_id'] = null;
						$line['depth'] = 0;
						if ($qty) {
							if(! empty($seen[$prodid])) {
								$nlines[$seen[$prodid]]['quantity'] += $qty;
								$nlines[$seen[$prodid]]['ext_quantity'] += $qty;
							} else {
								$nlines[$lid] = $line;
								$seen[$prodid] = $lid;
							}
						}
   					}
   				}
   			}
   			$g['lines'] = $nlines;
   		}
   		$self->replaceGroups($groups);
   	}

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        $update['show_list_prices'] = '1';
        $update['show_components'] = 'all';
        $update['receiving_stage'] = 'Received';
        $update['valid_until'] = date('Y-m-d', strtotime('+30 day'));
        $update['date_shipped'] = date('Y-m-d', time());

        $po_id = null;
        if (isset($_REQUEST['purchaseorder_id'])) {
            $po_id = $_REQUEST['purchaseorder_id'];
        } elseif (isset($_REQUEST['po_id'])) {
            $po_id = $_REQUEST['po_id'];
        }

        if(! empty($po_id)) {
        	$po = ListQuery::quick_fetch('PurchaseOrder', $po_id);
			if ($po) {
	        	$po_upd = RowUpdate::for_result($po);
		    	self::init_from_tally($upd, $po_upd);
			}
        } elseif (!empty($_REQUEST['invoice_id'])) {
        	$invoice = ListQuery::quick_fetch('Invoice', $_REQUEST['invoice_id']);
			if ($invoice) {
	        	$invoice_upd = RowUpdate::for_result($invoice);
		    	self::init_from_tally($upd, $invoice_upd);
			}
        } elseif (!empty($_REQUEST['credit_id'])) {
			$invoice = ListQuery::quick_fetch('CreditNote', $_REQUEST['credit_id']);
			if ($invoice) {
	        	$invoice_upd = RowUpdate::for_result($invoice);
		    	self::init_from_tally($upd, $invoice_upd);
			}
        }

        $upd->set($update);

        if (! $upd->getField('warehouse_id')) {
            require_bean('CompanyAddress');
            $upd->set('warehouse_id', CompanyAddress::getMainWarehouseId());
        }
    }

    static function auto_set_fields(RowUpdate $receiving_update) {
        $receiving = new Receiving();
        //Set Receiving data
        $receiving->id = $receiving_update->getPrimaryKeyValue();
        $receiving->po_id = $receiving_update->getField('po_id');

        $po_received = $receiving->updatePOStatus();

        $receiving_update->set('receiving_stage', $po_received ? 'Received' : 'Partially Received');
    }

}

