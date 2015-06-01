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
require_once('modules/Shipping/ShippingLineGroup.php');
require_once('modules/Accounts/Account.php');

class Shipping extends SugarBean {
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
	var $shipping_number;
	var $tracking_number;	
	var $shipping_stage;
	var $related_po_id;
	var $opportunity_id, $opportunity_name;
	var $invoice_id;
	var $so_id;

	var $billing_account_name, $billing_account_id;
	var $billing_address_street, $billing_address_city, $billing_address_state, $billing_address_postalcode, $billing_address_country;
	// Used by quick-creation form
	var $account_id;
	//
	var $shipping_account_name, $shipping_account_id;
	var $shipping_address_street, $shipping_address_city, $shipping_address_state, $shipping_address_postalcode, $shipping_address_country;
	var $shipping_phone;
	var $shipping_email;
	//
	var $billing_contact_id, $billing_contact_name;
	var $shipping_contact_id, $shipping_contact_name;
	var $currency_id, $exchange_rate;
	var $shipping_provider_id;
	var $tax_information;

	var $amount, $amount_usdollar;
	var $cancelled;
	var $show_components;
	var $tax_exempt;

	var $warehouse_id;
	var $warehouse_name;

	//
	var $allocated; // for subpanel on payments
	
	// Static members
	var $table_name = "shipping";
	var $object_name = "Shipping";
	var $group_object_name = "ShippingLineGroup";
	var $module_dir = "Shipping";
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
		'billing_account_name',
		'shipping_account_name',
		'billing_contact_name',
		'shipping_contact_name',
		'update_quote_id',
		'invoice_name',
		'so_name',
		'warehouse_name',
	);


	static $inherit_invoice_fields = array(
		'assigned_user_id',
		'assigned_user_name',
		'name',
		'shipping_account_id',
		'shipping_account_name',
		'shipping_contact_id',
		'shipping_contact_name',
		'shipping_address_street',
		'shipping_address_city',
		'shipping_address_state',
		'shipping_address_postalcode',
		'shipping_address_country',
		'currency_id',
		'exchange_rate',
		'shipping_provider_id',
		'description',
		'show_components',
		'purchase_order_num',
		'id' => 'invoice_id',
		'from_so_id' => 'so_id',
	);
	

	
	static function init_from_account($account_id) {
        $account = ListQuery::quick_fetch_row('Account', $account_id);
        $data = array();

        if ($account != null) {
            $data['shipping_account_id'] = $account['id'];
            $data['shipping_provider_id'] = $account['default_shipper_id'];
            if ($account['tax_code_id'] == EXEMPT_TAXCODE_ID)
                $data['tax_exempt'] = 1;

            $fields = array(
                'address_street', 'address_city', 'address_state',
                'address_postalcode', 'address_country',
                'currency_id', 'exchange_rate',
                'default_terms' => 'terms',
                'default_discount_id',
                'tax_information',
            );

            foreach($fields as $k => $f) {
                if(is_int($k)) $k = $f;
                if (strpos($k, 'address') !== false) {
                    $index = 'shipping_' . $k;
                    $data[$index] = $account[$index];
                } else {
                    $data[$f] = $account[$k];
                }
            }
        }

        return $data;
	}
	
	
	function getCurrentPrefix()
	{
		return AppConfig::get_sequence_prefix('shipping_prefix');
	}
	

	function getCurrentSequenceValue()
	{
		return AppConfig::current_sequence_value('shipping_sequence');
	}
	
	function getNextSequenceValue()
	{
		return AppConfig::next_sequence_value('shipping_sequence');
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
		$ret = ShippingLineGroup::newForParent($this);
		return $ret;
	}
	
	function line_items_editable() {
		return true;
	}
	
	
	function extractQuantities($line_items, &$products, &$assemblies, $sign, $names = false)
	{
		foreach ($line_items as $group) {
			if (!empty($group->lines)) foreach ($group->lines as $row) {
				if($row['related_type'] == 'ProductCatalog') {
					$id = $row['related_id'];
					if(! isset($products[$id])) {
						if ($names) {
							$products[$id] = array(
								'name' => $row['name'],
								'q' => 0,
							);
						} else {
							$products[$id] = 0;
						}
					}
					if ($names) {
						$q =& $products[$id]['q'];
					} else {
						$q =& $products[$id];
					}
					$q += $row['ext_quantity'] * $sign;
				}
				else if($row['related_type'] == 'Assemblies') {
					$id = $row['related_id'];
					if(! isset($assemblies[$id])) {
						if ($names) {
							$assemblies[$id] = array(
								'name' => $row['name'],
								'q' => 0,
							);
						} else {
							$assemblies[$id] = 0;
						}
					}
					if ($names) {
						$q =& $assemblies[$id]['q'];
					} else {
						$q =& $assemblies[$id];
					}
					$q += $row['ext_quantity'] * $sign;
				}
			}
		}
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

	function get_invoiced($invoice_id, $is_receiving = false)
	{
		$invoiced = array();
		if ($is_receiving) {
		} else {
			$invoice_lines = 'invoice_lines';
			$invoice = 'invoice';
			$invoice_id = 'invoice_id';
		}
		$query = "
			SELECT 
				invoice_lines.related_id, invoice_lines.serial_numbers,
				invoice_lines.ext_quantity, invoice_lines.name
			FROM
				invoice_lines
			LEFT JOIN 
				invoice
			ON
				invoice.id = invoice_lines.invoice_id
			WHERE
				invoice.id = '{$this->invoice_id}'
				AND invoice.deleted = 0
				AND invoice_lines.deleted = 0
				AND invoice_lines.related_type = 'ProductCatalog'
		";
		$res = $this->db->query($query, true);
		while ($row = $this->db->fetchByAssoc($res)) {
			$key = empty($row['related_id']) ? $row['name'] : $row['related_id'];
			if (!isset($invoiced[$key])) {
				$invoiced[$key] = 0;
			}
			$invoiced[$key] += $row['ext_quantity'];
		}
	}

	function save_line_groups() {
		if(isset($this->line_groups) && is_array($this->line_groups)) {
			foreach(array_keys($this->line_groups) as $k)
				$this->line_groups[$k]->save();
			$total = $this->line_groups['GRANDTOTAL']->total;
			$this->amount = $this->number_formatting_done ? format_number($total) : sprintf('%0.2f', $total);
		}
	}

    
	function sales_order_allocations(){
		    $query="SELECT inv.*,CONCAT(inv.prefix,inv.invoice_number) AS full_number from invoice inv LEFT JOIN sales_orders po ON inv.id=po.related_invoice_id
		    WHERE po.id='".$this->id."'";
		
		return $query;
	}
	
	function get_view_closed_where_advanced($param)
	{
		return '1';
	}

	function get_view_closed_where_basic($param)
	{
		return empty($param['value']) ? "shipping.shipping_stage !=  'Shipped'" : '1';
	}
	
	function getDefaultListWhereClause()
	{
		return "shipping.shipping_stage != 'Closed'";
	}

	function get_assigned_contact_name(&$contact_id, $owner_field = '')
	{
        if (!empty($owner_field)) $this->$owner_field = '';
		$query = "SELECT contact.first_name, contact.last_name,contact.salutation, contact.assigned_user_id
		          FROM $this->contact_table contact
		          WHERE contact.id = '$contact_id' AND NOT contact.deleted
		          LIMIT 1";
		$result = $this->db->query($query);
		$encode = empty($this->pdf_output_mode);
		$row = $this->db->fetchByAssoc($result, -1, $encode);
        if (!empty($owner_field)) $this->$owner_field = $row['assigned_user_id'];
		if ($row) {
			return $GLOBALS['locale']->getLocaleFormattedName($row['first_name'], $row['last_name'], $row['salutation']);
		} else {
			return '';
		}
	}

	function search_by_number($param) {
		if (empty($param['value'])) {
			return 1;
		} else {
			return "(shipping.shipping_number = '" . PearDatabase::quote($param['value']) . "' OR CONCAT(shipping.prefix, shipping.shipping_number) = '" . PearDatabase::quote($param['value']) . "' ) ";
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

    function updateInvoiceStatus() {
        if (! empty($this->invoice_id)) {
            $invoiced = $this->getInvoicedLines($this->invoice_id);
            $shipped = $this->getShippedLines('invoice');
            $diff = $this->getItemsDiff($invoiced, $shipped);
            $invoiceShipped = empty($diff);

            $invoice_result = ListQuery::quick_fetch('Invoice', $this->invoice_id);

            if ($invoice_result) {
                $invoice_update = RowUpdate::for_result($invoice_result);

                if ($this->shipping_stage == 'Shipped') {
                    $shipping_stage = $invoiceShipped ? 'Shipped' : 'Partially Shipped';
                } else {
                    $shipping_stage = 'Pending';
                }

                $invoice_update->set('shipping_stage', $shipping_stage);
                $invoice_update->save();
            }
        }
    }

    function updateSOStatus() {
        if (!empty($this->so_id)) {
            $so_lines = $this->getSOLines();
            $shipped = $this->getShippedLines('so');
            $diff = $this->getItemsDiff($so_lines, $shipped);
            $soShipped = empty($diff);

            $so_result = ListQuery::quick_fetch('SalesOrder', $this->so_id);

            if ($so_result) {
                $so_update = RowUpdate::for_result($so_result);
                if ($this->shipping_stage == 'Shipped') {
                    $so_stage = $soShipped ? 'Closed - Shipped and Invoiced' : 'Partially Shipped and Invoiced';
                } else {
                    $so_stage = 'In Manufacturing';
                }

                $so_new_update = array('so_stage' => $so_stage);

                if ($soShipped) {
                    global $timedate;
                    $so_new_update['delivery_date'] = date($timedate->get_date_format(), time());
                }

                $so_update->set($so_new_update);
                $so_update->save();
            }
        }
    }

    /**
     *
     * @return array
     */
    function getInvoicedLines() {
        $query = "SELECT invoice_lines.related_id, invoice_lines.serial_numbers,
          invoice_lines.ext_quantity, invoice_lines.name
          FROM invoice_lines
          LEFT JOIN invoice ON invoice.id = invoice_lines.invoice_id
          WHERE invoice.id = '{$this->invoice_id}' AND invoice.deleted = 0
          AND invoice_lines.deleted = 0
          AND invoice_lines.related_type = 'ProductCatalog'";

        $res = $this->db->query($query);
        $invoiced = array();

        while ($row = $this->db->fetchByAssoc($res)) {
            $key = empty($row['related_id']) ? $row['name'] : $row['related_id'];

            if (! isset($invoiced[$key]))
                $invoiced[$key] = 0;

            $invoiced[$key] += $row['ext_quantity'];
        }

        return $invoiced;
    }

    /**
     * Get Sales Orders lines
     *
     * @return array
     */
    function getSOLines() {
        $query = "SELECT sales_order_lines.related_id, sales_order_lines.serial_numbers,
          sales_order_lines.ext_quantity, sales_order_lines.name
          FROM sales_order_lines
          LEFT JOIN sales_orders ON sales_orders.id = sales_order_lines.sales_orders_id
          WHERE sales_orders.id = '{$this->so_id}'
          AND sales_orders.deleted = 0 AND sales_order_lines.deleted = 0
          AND sales_order_lines.related_type = 'ProductCatalog'";

        $res = $this->db->query($query);
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
     * @param string $base - base line items object:
     * 'invoice', 'so'
     * @return array
     */
    function getShippedLines($base = 'invoice') {
        if ($base == 'so') {
            $type_clause = "(shipping.so_id = '{$this->so_id}'";
        } else {
            $type_clause = "(shipping.invoice_id = '{$this->invoice_id}'";
        }

        $type_clause .= " AND shipping.deleted = 0)";

        $query = "SELECT shipping_lines.related_id, shipping_lines.serial_numbers,
          shipping_lines.ext_quantity, shipping_lines.name
          FROM shipping_lines
          LEFT JOIN shipping ON shipping.id = shipping_lines.shipping_id
          WHERE (shipping_lines.shipping_id = '{$this->id}' OR " .$type_clause. ")
          AND shipping_lines.deleted = 0
          AND shipping_lines.related_type = 'ProductCatalog'";

        $res = $this->db->query($query);
        $shipped = array();

        while ($row = $this->db->fetchByAssoc($res)) {
            $key = empty($row['related_id']) ? $row['name'] : $row['related_id'];
            if (!isset($shipped[$key]))
                $shipped[$key] = 0;
            $shipped[$key] += $row['ext_quantity'];
        }

        return $shipped;
    }

    function getItemsDiff($invoiced, $shipped) {
        $diff = array();

        foreach ($invoiced as $k => $v) {
            $invoiced_item = array_get_default($invoiced, $k, null);
            $shipped_item = array_get_default($shipped, $k, 0);

            if ($invoiced_item) {
                if ($invoiced_item > $shipped_item) {
                    $diff[$k] = $invoiced_item - $shipped_item;
                }
            }
        }

        return $diff;
    }

    static function init_from_tally(RowUpdate &$self, RowUpdate &$tally) {
   		$existing = array();

   		$type = $tally->getModelName();
   		if ($type == 'Invoice') {
   			$map = self::$inherit_invoice_fields;
   			foreach ($map as $key => $value) {
   				if(is_int($key)) $key = $value;
   				$fv = $tally->getField($key);
   				if(isset($fv))
   					$self->set($value, $fv);
   			}

			$invoice_id = $tally->getPrimaryKeyValue();
			$query = "
				SELECT
					shipping_lines.*
				FROM shipping_lines
				LEFT JOIN shipping
					ON shipping_lines.shipping_id=shipping.id AND shipping.deleted=0
				WHERE shipping_lines.deleted = 0 AND shipping.invoice_id = '{$invoice_id}'
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

    static function init_from_so(TallyUpdate &$self, TallyUpdate $so) {
   		foreach (self::$inherit_invoice_fields as $key => $value) {
   			if(is_int($key)) $key = $value;
   			$self->set($value, $so->getField($key));
   		}
   		$groups = $so->getGroups(true);
   		foreach ($groups as $g => &$group) {
   			if (isset($group['lines']) && is_array($group['lines'])) {
   				foreach ($group['lines'] as $l => &$line) {
   					if(! empty($line['is_comment']))
   						continue;
   					$line['unit_price'] = $line['cost_price'];
   					$line['unit_price_usd'] = $line['cost_price_usd'];
   					$line['std_unit_price'] = $line['cost_price'];
   					$line['std_unit_price_usd'] = $line['cost_price_usd'];
   					$line['pricing_adjustment_id'] = '';

   					if ($line['related_id'] && ($line['related_type'] == 'ProductCatalog' || $line['related_type'] == 'Assemblies')) {
   						$bean_name = AppConfig::module_primary_bean($line['related_type']);
   						$rec = ListQuery::quick_fetch($bean_name, $line['related_id']);
   						if ($rec) {
   							$pname = $rec->getField('purchase_name');
   							if(! empty($pname))
   								$line['name'] = $pname;
   						}
   					}

   				}
   			}
   			// delete all discounts and line pricing adjustments
   			$adjusts = array();
   			foreach($group['adjusts'] as $idx => $adj) {
   				if($adj['type'] == 'ProductAttributes')
   					$adjusts[$idx] = $adj;
   			}
   			$group['adjusts'] = $adjusts;
   		}
   		$self->set('line_items', $groups);
   		$self->set('tax_information', '');
   	}

    static function init_record(RowUpdate &$upd, $input) {
        global $timedate;
        $update = array();

        if(! empty($input['account_id'])) {
            $account_data = self::init_from_account($input['account_id']);
            $update += $account_data;
        }

        if(!empty($input['salesorder_id'])) {
        	$so = ListQuery::quick_fetch('SalesOrder', $input['salesorder_id']);
            if ($so) {
                $so_upd = RowUpdate::for_result($so);
                self::init_from_so($upd, $so_upd);
                $update['so_id'] = $input['salesorder_id'];
            }
        }

        $update['show_list_prices'] = '1';
        $update['show_components'] = 'all';
        $update['shipping_stage'] = 'In Preparation';
        $update['date_shipped'] = date('Y-m-d', time());

        $from_invoice_id = null;
        if(! empty($input['invoice_id'])) {
            $from_invoice_id = $input['invoice_id'];
        } elseif (! empty($input['from_invoice_id'])) {
            $from_invoice_id = $input['from_invoice_id'];
        }

        if($from_invoice_id) {
        	$invoice = ListQuery::quick_fetch('Invoice', $from_invoice_id);
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

    static function update_stage(RowUpdate $shipping_update) {
        $shipping = new Shipping();

        //Set Shipping data
        $shipping->id = $shipping_update->getPrimaryKeyValue();
        $shipping->invoice_id = $shipping_update->getField('invoice_id');
        $shipping->so_id = $shipping_update->getField('so_id');
        $shipping->shipping_stage = $shipping_update->getField('shipping_stage');

        $shipping->updateInvoiceStatus();
        $shipping->updateSOStatus();
    }
}
?>
