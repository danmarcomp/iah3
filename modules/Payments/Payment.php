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

class Payment extends SugarBean 
{
	// Stored fields

    var $id;
    var $deleted;
    var $date_entered;
    var $date_modified;
    var $assigned_user_id;
	var $assigned_user_name;
    var $created_by;
    var $currency_id;
    var $exchange_rate;
	var $currency;
    var $amount;
    var $amount_usdollar;
    var $account_id;
	var $account_name;
    var $payment_date;
    var $payment_id;
    var $customer_reference;
    var $notes;
    var $payment_type;
	var $currency_symbol;
	var $prefix;
	var $direction;
	var $refunded;
	var $refund = 0;

	// used by subpanel
	var $invoice_currency_id;
	var $allocated;

	var $line_items = array();
	var $delete_line_items = array();

	var $table_name = 'payments';
	var $object_name = 'Payment';
	var $module_dir = 'Payments';
	var $new_schema = true;
	
	var $account_table = 'accounts';
	var $invoice_table = 'invoice';
	var $invoices_payments_table = 'invoices_payments';
	
	var $additional_column_fields = array(
		'assigned_user_name',
		'account_name',
		'currency',
		'total_allocated',
		'allocated',
		'allocated_usdollar',
		);
	
	var $relationship_fields = array(
		'account_id' => 'account_link',
	);
	
	static $line_links = array(
		'invoices' => array('invoices_payments', 'invoice'),
		'bills' => array('bills_payments', 'bill'),
		'credits' => array('credits_payments', 'credit'),
	);
        
	
	static function getCurrentPrefix() {
		return AppConfig::setting('company.payment_prefix');
	}
	

	static function getCurrentSequenceValue() {
		return AppConfig::current_sequence_value('payment_number_sequence');
	}
	
	static function getNextSequenceValue() {
		return AppConfig::next_sequence_value('payment_number_sequence');
	}
	
	
    function get_allocated_total()
    {
        return ($this->number_formatting_done ? unformat_number($this->amount) : $this->amount);
    }

	
	static function get_input_line_items($lines, $payment_id, $direction) {
        $line_items = array();
        $credit_items = array();

		if(isset($_REQUEST['credit_id']) && is_array($_REQUEST['credit_id']) && $direction == 'incoming') {
			foreach ($_REQUEST['credit_id'] as $key => $value) {
				$credit_items[$key]['credit_id'] = $value;
				$credit_items[$key]['payment_id'] = $payment_id;
				$amount = (float)unformat_number($_REQUEST['credit_allocated'][$key]);
				$currency = $_REQUEST['credit_currency_id'][$key];
				$exrt = (float)unformat_number($_REQUEST['credit_exchange_rate'][$key]);
				if(! $exrt) $exrt = 1;
				$credit_items[$key]['amount'] = $amount;
				$credit_items[$key]['amount_usdollar'] = round($amount / $exrt, 6);
				$credit_items[$key]['currency_id'] = $currency;
				$credit_items[$key]['exchange_rate'] = $exrt;
				$credit_items[$key]['date_modified'] = gmdate("Y-m-d H:i:s");
			}
		}

		if(isset($_REQUEST['invoice_id']) && is_array($_REQUEST['invoice_id'])) {
			foreach ($_REQUEST['invoice_id'] as $key => $value) {
				if($direction == 'incoming')
					$line_items[$key]['invoice_id'] = $value;
				elseif ($direction == 'credit')
					$line_items[$key]['credit_id'] = $value;
				else
					$line_items[$key]['bill_id'] = $value;
				$line_items[$key]['payment_id'] = $payment_id;
				$amount = (float)unformat_number($_REQUEST['invoice_allocated'][$key]);
				$currency = $_REQUEST['invoice_currency_id'][$key];
				$exrt = (float)unformat_number($_REQUEST['invoice_exchange_rate'][$key]);
				if(! $exrt) $exrt = 1;
				$line_items[$key]['amount'] = $amount;
				$line_items[$key]['amount_usdollar'] = round($amount / $exrt, 6);
				$line_items[$key]['currency_id'] = $currency;
				$line_items[$key]['exchange_rate'] = $exrt;
				$line_items[$key]['date_modified'] = gmdate("Y-m-d H:i:s");
			}
		}
		
		
		if($direction == 'incoming') {
			$ret['invoices'] = $line_items;
			$ret['credits'] = $credit_items;
		}
		elseif ($direction == 'credit') 
			$ret['credits'] = $line_items;
		else
			$ret['bills'] = $line_items;
		return $ret;
	}
	

    static function updateInvoice($id, $direction) {
        if($direction == 'incoming') {
			$model = 'Invoice';
		} elseif ($direction == 'credit') {
            $model = 'CreditNote';
        } else {
            $model = 'Bill';
        }

        $source = ListQuery::quick_fetch($model, $id);

        if($source) {
            $source_upd = RowUpdate::for_result($source);
            $source_upd->save();

            if ($direction == 'incoming') {
                require_once('modules/MonthlyServices/MonthlyService.php');
                MonthlyService::updateBalanceDue($id);
            }
        }
    }
    
    static function after_delete(RowUpdate $upd) {
    	$lines = self::query_line_items($upd->getPrimaryKeyValue());
    	self::grab_related_ids($lines, $related);
    	self::update_related_amounts($upd, $related);
    }

    static function update_related_amounts(RowUpdate $upd, array $related) {
    	$mod = new ModelDef('Payment');
    	foreach($related as $link => $rows) {
    		$target = $mod->getLinkTargetModel($link);
    		if(! $target || ! $rows) continue;
    		$lq = new ListQuery($target);
    		$result = $lq->queryRecordSet(array_keys($rows));
    		if($result && ! $result->failed) {
    			foreach($result->getRowIndexes() as $idx) {
    				$row = $result->getRowResult($idx);
    				$upd = RowUpdate::for_result($row);
    				$upd->save();
    			}
    		}
    	}
    }
    
    static function grab_related_ids($lines, &$target) {
    	if(! isset($target)) $target = array();
    	foreach(self::$line_links as $k => $info) {
    		if(! isset($lines[$k])) continue;
    		$idf = $info[1].'_id';
			foreach($lines[$k] as $row) {
				$target[$k][$row[$idf]] = 1;
			}
		}
    }
        
    static function query_line_items($payment_id, $get_names=false) {
        $ret = array();
        foreach(self::$line_links as $k => $_)
        	$ret[$k] = array();

    	if($payment_id) {
			$lq = new ListQuery();
			
			foreach(self::$line_links as $k => $info) {
				$lq1 = new ListQuery($info[0], array(
					'invoice_id' => $info[1].'_id', 'payment_id', 'date_modified',
					'amount_usd' => 'amount_usdollar', 'amount', 'currency_id', 'exchange_rate'),
					array('alias' => $k));
				if($get_names) {
					$lq1->addFields(array(
						'invoice_name' => $info[1] . '.name',
						'invoice_no' => $info[1] . '.full_number',
						'invoice_currency_id' => $info[1] . '.currency_id',
						'invoice_amount' => $info[1] . '.amount',
						'invoice_amount_usd' => $info[1] . '.amount_usdollar',
						'invoice_amount_due' => $info[1] . '.amount_due',
						'invoice_amount_due_usd' => $info[1] . '.amount_due_usdollar',
						'invoice_due_date' => $info[1] . '.due_date',
					));
				}
				$lq1->addSimpleFilter('payment_id', $payment_id);
				$lq1->addSourceNameField('source');
				$lq->addUnionQuery($lq1, $k);
			}
			
			foreach($lq->fetchAllRows() as $row) {
				$rel_f = self::$line_links[$row['source']][1] . '_id';
				if($rel_f != 'invoice_id')
					$row[$rel_f] = $row['invoice_id'];
				$row['is_credit'] = ($k == 'credits') ? 1 : 0;
				$ret[$row['source']][$row[$rel_f]] = $row;
			}
		}

        return $ret;
    }

    static function add_line_items(RowUpdate $upd) {
		$id = $upd->getPrimaryKeyValue();
		$qid = $upd->new_record ? null : $id;
		$direction = $upd->getField('direction');

		$lines = self::query_line_items($qid);
		$new_lines = self::get_input_line_items($lines, $id, $direction);
		
		self::update_line_items($upd, $lines, $new_lines);
		// recalc handled by after_add_link:
		/*self::grab_related_ids($lines, $related);
		self::grab_related_ids($new_lines, $related);
		self::update_related_amounts($upd, $related);*/

	}

	static function before_subpanel_create($mgr, &$stop)
	{
		$stop = true;
	}
    
	static function after_add_link(RowUpdate $upd, $link_name) {
    	if(isset(self::$line_links[$link_name])) {
			$rel_id = array_get_default($upd->link_update->saved, self::$line_links[$link_name][1] . '_id');
			$tmodel = $upd->getLinkTargetModel($link_name);
			$result = ListQuery::quick_fetch($tmodel, $rel_id);
			if($result && ! $result->failed) {
				$rel = RowUpdate::for_result($result);
				$rel->save();
    		}
		}
    	
    	if($link_name == 'credits') {
    		//self::direct_update_total($upd->getPrimaryKeyValue());
    	}
    }
    
    static function get_credit_totals($payment_id) {
    	$qid = $GLOBALS['db']->quote($payment_id);
    	$q = "SELECT COALESCE(SUM(amount), 0) AS amt, COALESCE(SUM(amount_usdollar), 0) AS usd FROM credits_payments WHERE payment_id='$qid' AND NOT deleted";
    	$ret = $GLOBALS['db']->fetchSQL($q);
    	return $ret[0];
    }
    
    static function direct_update_total($payment_id) {
    	$row = self::get_credit_totals($payment_id);
    	$qid = $GLOBALS['db']->quote($payment_id);
    	$q = "UPDATE payments SET total_amount=amount + '{$row['amt']}', total_amount_usdollar=amount_usdollar + '{$row['usd']}'
    		WHERE id='$qid' AND NOT deleted";
    	$GLOBALS['db']->query($q);
    }
    
    static function update_line_items(RowUpdate $upd, $existing, $lines) {
    	$delete = array();

    	foreach(self::$line_links as $k => $info) {
    		$idf = $info[1].'_id';
    		$delete[$k] = array();
    		
    		if(isset($existing[$k])) {
				foreach($existing[$k] as $row) {
					$delete[$k][$row[$idf]] = 1;
				}
			}
			
			if(isset($lines[$k])) {
				foreach($lines[$k] as $row) {
					$upd->addUpdateLink($k, $row);
					$delete[$k][$row[$idf]] = 0;
				}
			}
			
			foreach($delete[$k] as $id => $v) {
				if($v) $upd->removeLink($k, $id);
			}
    	}
    }

    static function init_record(RowUpdate &$upd, $input) {
        global $timedate, $current_user;
        $update = array();

        $update['payment_date'] = gmdate('Y-m-d');

        if(! empty ($input['invoice_id'])) {
            $source_name = 'Invoice';
            $source_id = $input['invoice_id'];
            $source_field = 'billing_account_id';
        } else if(! empty ($input['creditnote_id'])) {
            $source_name = 'CreditNote';
            $source_id = $input['creditnote_id'];
            $source_field = 'billing_account_id';
        } else if (! empty($input['bill_id'])) {
            $source_name = 'Bill';
            $source_id = $input['bill_id'];
            $source_field = 'supplier_id';
        } else {
			$source_name = null;
			$source_id = null;
			$source_field = '';
		}

        if ($source_name && $source_id) {
            $source = ListQuery::quick_fetch_row($source_name, $source_id, array($source_field, 'amount_due', 'currency_id', 'exchange_rate'));
            if ($source) {
                $update['account_id'] = $source[$source_field];
                if($source_name != 'CreditNote' && (int)$source['amount_due'] >= 0) {
                    $update['amount'] = $source['amount_due'];
                } else {
                    $update['amount'] = 0;
                }
                $update['currency_id'] = $source['currency_id'];
                $update['exchange_rate'] = $source['exchange_rate'];
            }
        }

        $upd->set($update);
    }
    
    static function fill_defaults(RowUpdate $upd) {
    	if($upd->new_record) {
			$upd->set(array(
				'total_amount' => $upd->getField('amount'),
				'total_amount_usdollar' => $upd->getField('amount_usdollar'),
			));
    	}
    }
    
    static function before_save(RowUpdate $upd) {
    	if(! $upd->new_record) {
    		$credits = self::get_credit_totals($upd->getPrimaryKeyValue());
    		$upd->set(array(
				'total_amount' => $upd->getField('amount') + $credits['amt'],
				'total_amount_usdollar' => $upd->getField('amount_usdollar') + $credits['usd'],
			));
    	}
    }
}
?>
