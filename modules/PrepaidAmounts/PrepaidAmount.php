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
require_once('modules/Accounts/Account.php');
require_once('modules/SubContracts/SubContract.php');


class PrepaidAmount extends SugarBean {

	// Stored fields
	var $id;
	var $name;
	var $date_modified;
	var $date_entered;
	var $created_by;
	var $modified_user_id;
	var $deleted;
	var $account_id;
	var $subcontract_id;
	var $related_type;
	var $related_id;
	var $currency_id;
	var $exchange_rate;
	var $amount;
	var $amount_usdollar;
	var $transaction_type;
	
	// Looked up
	var $account_name;
	var $account_name_owner;
	var $subcontract_name;
	var $subcontract_name_owner;
	var $related_name;
	var $related_name_owner;
	var $created_by_name;
	var $modified_user_name;
	
	var $object_name = 'PrepaidAmount';
	var $module_dir = 'PrepaidAmounts';
	var $new_schema = true;
	
	var $table_name = "prepaid_amounts";

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'created_by_name',
		'modified_user_name',
		'account_name',
		'subcontract_name',
		'related_name',
	);


	function PrepaidAmount() {
		parent::SugarBean();
	}


	function get_summary_text() {
		return $this->name;
	}
	
	
	function initFromSubcontract($subcon_id) {
		$this->transaction_type = 'credit';
		$this->subcontract_id = $subcon_id;
		$subcon = new SubContract();
		$subcon->retrieve($subcon_id);
		$this->subcontract_name = $subcon->name;

		require_once('modules/Service/Contract.php');
		$main = new Contract();
		$main->retrieve($subcon->main_contract_id);
		$this->account_id = $main->account_id;
		$main->cleanup();
		$subcon->cleanup();
		
		$acc = new Account();
		$acc->retrieve($this->account_id);
		$this->account_name = $acc->name;
		$this->currency_id = $acc->currency_id;
		$this->exchange_rate = $acc->exchange_rate;
		$acc->cleanup();
	}
	
	
	function initFromRelated($related_type, $related_id, $subcon_id=null) {
		global $beanList, $beanFiles;
		$this->related_type = $related_type;
		$this->related_id = $related_id;
		require_once($beanFiles[$beanList[$this->related_type]]);
		$bean = new $beanList[$this->related_type];
		$bean->retrieve($this->related_id);
		$this->related_name = $bean->name;

		if($subcon_id)
			$this->subcontract_id = $subcon_id;
		else if(isset($bean->contract_id))
			$this->subcontract_id = $bean->contract_id;
		else if(isset($bean->subcontract_id))
			$this->subcontract_id = $bean->subcontract_id;
		$this->initFromSubcontract($this->subcontract_id);		
		$this->transaction_type = 'debit';
		
		if(method_exists($bean, 'calculateCosts')) {
			$this->related_costs = $bean->calculateCosts($this->currency_id);
		}
	}


	function getBalance($subcontract_id, $currency_id=null, $related_type=null, $related_id=null, $excl_id=null) {
		require_bean('Currency');
		$currency = new Currency();
		$currency->retrieve($currency_id);
		$tbl = $this->table_name;
		$query = "SELECT related_type, related_id, subcontract_id, " .
			"currency_id, exchange_rate, amount, amount_usdollar, transaction_type FROM `$tbl` prepaid ";
		$query .= "WHERE (subcontract_id = '".$this->db->quote($subcontract_id)."' ";
		if($related_id)
			$query .= "OR (related_type = '".$this->db->quote($related_type)."' AND related_id = '".$this->db->quote($related_id)."') ";
		$query .= ') ';
		if($excl_id)
			$query .= "AND id != '".$this->db->quote($excl_id)."' ";
		$query .= "AND NOT deleted";
		$r = $this->db->query($query, true);
		$ret = array('currency_id' => $currency_id, 'row_count'=> 0, 'total' => 0, 'total_usd' => 0, 'prev' => 0, 'prev_usd' => 0);
		while($row = $this->db->fetchByAssoc($r)) {
			$ret['row_count'] ++;
			$usd = $row['amount_usdollar'];
			if($currency->id == $row['currency_id'])
				$amt = $row['amount'];
			else
				$amt = $currency->convertFromDollar($row['amount_usdollar'], $currency->decimal_places);
			if($row['transaction_type'] == 'debit') {
				$amt = -$amt;
				$usd = -$usd;
			}
			if($row['subcontract_id'] == $subcontract_id) {
				$ret['total'] += $amt;
				$ret['total_usd'] += $usd;				
			}
			if($related_id && $row['related_type'] == $related_type && $row['related_id'] == $related_id) {
				$ret['prev'] += $amt;
				$ret['prev_usd'] += $usd;
			}
		}
		$currency->cleanup();
		return $ret;
	}

	
	function save($check_notify = FALSE) {
		// must do this before manipulating numeric fields
		$this->unformat_all_fields();

		// system standard currency
		if(isset($this->amount) && !empty($this->amount)){
			require_once('modules/Currencies/Currency.php');
			$currency = new Currency();
			$currency->retrieve($this->currency_id);
			adjust_exchange_rate($this, $currency);
			$this->amount_usdollar = $currency->convertToDollar($this->amount);
			$currency->cleanup();
		}
		else {
			$this->amount_usdollar = 0;
			$this->exchange_rate = null;
		}
	
		$ret = parent::save($check_notify);
		
		if($this->subcontract_id) {
			// update prepaid balance
			$subcon = new SubContract();
			if($subcon->retrieve($this->subcontract_id))
				$subcon->save();
			$subcon->cleanup();
		}
		
		return $ret;
	}


    static function init_record(RowUpdate &$upd, $input) {
        $update = array();

        if ( isset($input['related_id']) && (isset($input['return_module']) && $input['return_module'] == 'Cases') ) {
            $update['related_id'] = $input['related_id'];
            $update['related_type'] = 'Cases';
			$update['transaction_type'] = 'debit';

			$result = ListQuery::quick_fetch('aCase', $input['related_id'], array('account', 'contract'));
			if ($aid = $result->getField('account_id')) {
				$update['account'] = $result->getField('account');;
				$update['account_id'] = $aid;
			}
			if ($cid = $result->getField('contract_id')) {
				$update['subcontract'] = $result->getField('contract');;
				$update['subcontract_id'] = $cid;
			}

        } elseif (isset($input['subcontract_id'])) {
            $update['subcontract_id'] = $input['subcontract_id'];
            $update['transaction_type'] = 'credit';

            $subcontract = ListQuery::quick_fetch_row('SubContract', $input['subcontract_id'], array('main_contract_id'));
            $main_contract_id = null;

            if ($subcontract != null)
                $main_contract_id = $subcontract['main_contract_id'];

            if ($main_contract_id != null) {
                $contract = ListQuery::quick_fetch_row('Contract', $main_contract_id, array('account_id'));

                if ($contract != null) {
                    $update['account_id'] = $contract['account_id'];

                    $account = ListQuery::quick_fetch_row('Account', $contract['account_id'], array('currency_id'));
                    if (! empty($account['currency_id']))
                        $update['currency_id'] = $account['currency_id'];
                }
            }
        }

        $upd->set($update);
    }

    static function update_balance(RowUpdate $upd) {
        $subcontract_id = $upd->getField('subcontract_id');

        if ($subcontract_id)
            SubContract::update_prepaid_balance($subcontract_id);
    }
}
?>
