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


class Contract extends SugarBean {

	// Stored fields
	var $id;
	var $account_id;
	var $date_modified;
	var $date_entered;
	var $is_active;
	var $contract_no;
	var $description;
	var $deleted;
	var $created_by;
	var $modified_user_id;
	
	// Looked up
	var $account_name;
	var $account_phone;
	var $total_purchase_value;
	var $name;
	var $created_by_name;
	var $modified_user_name;
	
	var $object_name = 'Contract';
	var $module_dir = 'Service';
	var $new_schema = true;
	
	var $table_name = "service_maincontracts";
	var $subcontract_table = "service_subcontracts";
	var $rel_account_table = "accounts";

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = Array(
		'created_by_name',
		'modified_user_name',
		'account_id', 
	);

	
	static function status_subselect($colspec, $table, $for_order=false) {
		global $db;
		$subc = AppConfig::setting("model.detail.SubContract.table_name");
		$subc = $db->quoteField($subc);
		$id_f = $db->quoteField('id', $table);
		$active_f = $db->quoteField('is_active', $table);
		return "
			CASE WHEN $active_f='no' THEN 'grey' ELSE (SELECT 
				CASE 
					WHEN MAX(contracts.is_active) IS NULL 
					OR MIN(contracts.date_expire) > DATE_ADD(CURDATE(),INTERVAL 30 DAY) 
					THEN 'green'
				WHEN MIN(contracts.date_expire) > CURDATE()
					THEN 'yellow'
				ELSE 'red' END
			FROM $subc contracts
			WHERE contracts.main_contract_id=$id_f
			AND contracts.is_active = 'yes'
			AND contracts.date_expire != 0) END";
	}


	/** Returns a list of the associated contacts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_contacts()
	{
		// First, get the list of IDs.
		$query = "SELECT c.id, c.first_name, c.last_name, c.title, c.email1, c.phone_work, o_c.contact_role as Contracts_role, o_c.id as Contracts_rel_id ".
				 "from $this->rel_contact_table o_c, contacts c ".
				 "where o_c.Contracts_id = '$this->id' and o_c.deleted=0 and c.id = o_c.contact_id AND c.deleted=0 order by c.last_name";

	    $temp = Array('id', 'first_name', 'last_name', 'title', 'email1', 'phone_work', 'Contracts_role', 'Contracts_rel_id');
		return $this->build_related_list2($query, new Contact(), $temp);
	}
	

    static function calc_total_purchase($spec) {
        $cost = 0;

        if (isset($spec['raw_values']['id'])) {
            global $db;

            $query = " SELECT SUM(assets.purchase_usdollar * assets.quantity) AS total_purchase
                FROM service_subcontracts AS subc, assets
                WHERE subc.main_contract_id = '" .$spec['raw_values']['id']. "' AND
                    assets.service_subcontract_id = subc.id AND
                    subc.deleted = 0 AND assets.deleted = 0
                GROUP BY subc.main_contract_id";

            $result = $db->query($query);
            $data = $db->fetchByAssoc($result);

            if (isset($data['total_purchase'])) {
                global $current_user;

                if( !isset($current_user->currency_for_contract)) {
                    require_once('modules/Currencies/Currency.php');
                    $currency = new Currency();
                    $currency->retrieve($current_user->getPreference('currency'));
                    $current_user->currency_for_contract = $currency;
                } else {
                    $currency = $current_user->currency_for_contract;
                }

                $total_value = stripslashes( $data['total_purchase'] );
                $cost = $currency->convertFromDollar($total_value);
            }
        }

        $cost = format_number($cost);

        return $cost;
    }
    
    static function check_account(RowUpdate &$upd) {
    	if($upd->new_record) {
    		$account_id = $upd->getField('account_id');
			$account = ListQuery::quick_fetch_row('Account', $account_id, array('main_service_contract_id'));
			if($account && ! empty($account['main_service_contract_id'])) {
				$upd->addValidationError('invalid_value', 'account');
			}
    	}
    }

    static function set_number(RowUpdate &$upd) {
        if ($upd->new_record) {
            $account_id = $upd->getField('account_id');

            if ($account_id) {
                $number = '0000';
                $account = ListQuery::quick_fetch_row('Account', $account_id, array('name'));

                if ($account) {
                    $name = $account['name'];

                    $first_initial = strtoupper(substr($name, 0, 1));
                    if ($first_initial < 'A' || $first_initial > 'Z')
                        $first_initial = 'Z';

                    $counts = get_contract_counts();
                    $first_initial_count = $counts[$first_initial];

                    while (strlen($first_initial_count) < 4) {
                        $first_initial_count = '0' . $first_initial_count;
                    }

                    $number = $first_initial . $first_initial_count;
                }

                $upd->set('contract_no', $number);
            }
        }
    }

    static function set_account_maincontract_relationship( $contract_id, $account_id ) {
        global $db;
        $query = "update `accounts` set main_service_contract_id ='$contract_id' where id = '$account_id' ";
        $db->query($query);
    }

    static function set_account_contract(RowUpdate &$upd) {
        if($upd->getField('account_id') != "")
            Contract::set_account_maincontract_relationship( $upd->getPrimaryKeyValue(), $upd->getField('account_id') );
    }

    static function add_view_popups(DetailManager $mgr) {
        require_bean('Account');
        Account::add_account_popup($mgr->getRecord(), 'account_id', 'service');
    }

    static function init_record(RowUpdate &$upd, $input) {
        $update = array();
        $fields = array('account_name', 'account_id', 'contract_no', 'date_modified', 'date_entered');

        for ($i = 0; $i < sizeof($fields); $i++) {
            $field = $fields[$i];
            if (! empty($input[$field])) {
                $update[$field] = urldecode($input[$field]);
            }
        }

        $upd->set($update);
    }
}


function get_contract_counts() {
	$seed = new Contract();
	$query = "SELECT LEFT(contract_no, 1) AS initial, MAX(MID(contract_no, 2))+1 AS next_number ".
		" FROM $seed->table_name GROUP BY initial";
	$result = $seed->db->query($query, true, "Error retrieving existing contract numbers");
	$counts = array();
	for($v = 'A'; $v < 'Z'; $v++)
		$counts[$v] = 1;
	$counts['Z'] = 1;
	while($row = $seed->db->fetchByAssoc($result)) {
		$counts[$row['initial']] = $row['next_number'];
	}
	$seed->cleanup();
	return $counts;
}
?>
