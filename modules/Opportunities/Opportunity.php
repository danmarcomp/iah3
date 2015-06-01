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

 * Description:
 ********************************************************************************/


require_once('data/SugarBean.php');


// Opportunity is used to store customer information.
class Opportunity extends SugarBean {
	var $field_name_map;
	// Stored fields
	var $id;
	var $lead_source;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $description;
	var $name;
	var $opportunity_type;
	var $amount;
	var $amount_usdollar;
	var $currency_id;
	var $date_closed;
	var $next_step;
	var $sales_stage;
	var $probability;

	// longreach - start added
	var $forecast_category;
	var $weighted_amount;
	var $weighted_amount_usdollar;
	var $exchange_rate;
	var $partner_id;
	var $partner_name;
	var $campaign_id;
	var $campaign_name;
	// longreach - end added





	// These are related
    var $amount_backup;
	var $account_name;
	var $account_id;
	var $contact_id;
	var $task_id;
	var $note_id;
	var $meeting_id;
	var $call_id;
	var $email_id;
	var $assigned_user_name;

	var $table_name = "opportunities";
	var $rel_account_table = "accounts_opportunities";
	var $rel_contact_table = "opportunities_contacts";
	var $module_dir = "Opportunities";


	
	var $object_name = "Opportunity";

	var $new_schema = true;

	
	/** Returns a list of the associated contacts
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function get_contacts()
	{
		require_once('modules/Contacts/Contact.php');
		$this->load_relationship('contacts');
		$query_array=$this->contacts->getQuery(true);
		
		//update the select clause in the retruned query.
		$query_array['select']="SELECT contacts.id, contacts.first_name, contacts.last_name, contacts.title, contacts.email1, contacts.phone_work, opportunities_contacts.contact_role as opportunity_role, opportunities_contacts.id as opportunity_rel_id ";
	
		$query='';
		foreach ($query_array as $qstring) {
			$query.=' '.$qstring;
		}	
	    $temp = Array('id', 'first_name', 'last_name', 'title', 'email1', 'phone_work', 'opportunity_role', 'opportunity_rel_id');
		return $this->build_related_list2($query, new Contact(), $temp);
	}

	function update_currency_id($fromid, $toid){
		$idequals = '';
		require_once('modules/Currencies/Currency.php');
		$currency = new Currency();
		$currency->retrieve($toid);
		foreach($fromid as $f){
			if(!empty($idequals)){
				$idequals .=' or ';
			}
			$idequals .= "currency_id='$f'";
		}

		if(!empty($idequals)){
			$query = "select amount, id from opportunities where (". $idequals. ") and deleted=0 and ".$this->filter_closed();
			$result = $this->db->query($query);
			while($row = $this->db->fetchByAssoc($result)){
				$query = "update opportunities set currency_id='".$currency->id."', amount_usdollar='".$currency->convertToDollar($row['amount'])."' where id='".$row['id']."'";
				$this->db->query($query);
			}

		}
		$currency->cleanup();
	}
	
	function filter_closed($tbl='opportunities', $invert=false) {
		return "($tbl.sales_stage ".($invert ? '' : 'not ')."like 'Closed %')";
	}
	
	
	function save_relationship_changes($is_update)
	{
		//if account_id was replaced unlink the previous account_id.
		//this rel_fields_before_value is populated by sugarbean during the retrieve call.
		if (!empty($this->account_id) and !empty($this->rel_fields_before_value['account_id']) and 
				(trim($this->account_id) != trim($this->rel_fields_before_value['account_id']))) {
				//unlink the old record.
				$this->load_relationship('accounts');							
				$this->accounts->delete($this->id,$this->rel_fields_before_value['account_id']);		    					    		    				
		}

		parent::save_relationship_changes($is_update);
		
		if (!empty($this->contact_id)) {
			$this->set_opportunity_contact_relationship($this->contact_id);
		}
	}

	function set_opportunity_contact_relationship($contact_id)
	{
		global $app_list_strings;
		$default = null;
		$this->load_relationship('contacts');
		$this->contacts->add($contact_id,array('contact_role'=>$default));
	}

	function set_notification_body($xtpl, $oppty)
	{
		global $app_list_strings;
		
		$xtpl->assign("OPPORTUNITY_NAME", $oppty->name);
		$xtpl->assign("OPPORTUNITY_AMOUNT", $oppty->amount);
		$xtpl->assign("OPPORTUNITY_CLOSEDATE", $oppty->date_closed);
		$xtpl->assign("OPPORTUNITY_STAGE", (isset($oppty->sales_stage)?$app_list_strings['sales_stage_dom'][$oppty->sales_stage]:""));
		$xtpl->assign("OPPORTUNITY_DESCRIPTION", $oppty->description);

		return $xtpl;
	}


	// longreach - start added
	static function update_forecasts(RowUpdate &$upd)
	{
		require_once('modules/Forecasts/Forecast.php');
		require_once('modules/Forecasts/ForecastCalculator.php');

		$period = AppConfig::setting('company.forecast_period');
		$fiscal_year_start = AppConfig::setting('company.fiscal_year_start');
		
		$uid = $upd->getField('assigned_user_id');
		$date = $upd->getField('date_closed');
		$prev_uid = $upd->getField('assigned_user_id', null, true);
		$prev_date = $upd->getField('date_closed', null, true);
		$updates = array(array('user' => $uid, 'date' => $date));

		if(! empty($prev_date) && ! empty($prev_uid)) {
			$upd2 = array('user' => $prev_uid, 'date' => $prev_date);
			// if assigned user or closing date changed, update for the combination of them
			if($upd2 != $updates[0])
				array_unshift($updates, $upd2);
		}
		
		$calc = new ForecastCalculator($period);

		foreach($updates as $update) {
			$info = Forecast::getPeriodInfo($update['date'], $period, $fiscal_year_start);
			$base = Forecast::retrieve_by_period($update['user'], $info['id']);
			if($base) {
				$focus = RowUpdate::for_result($base);
				$calc->fill_personal_forecast($focus, $info['id']);
				$focus->save();
				$calc->update_user_team_forecasts($update['user'], $info['id']);
				unset($focus);
			}
			unset($base);
		}
		
		$calc->cleanup();
	}
	
	
	function getTotals($where = '')
	{
		$need_users = preg_match('~\busers\.~', $where);
		if (strlen($where)) $where .= ' AND ';
		$where .= ' opportunities.deleted = 0';
		$query = 'SELECT SUM(amount_usdollar) AS amount,'
					. ' SUM(opportunities.amount_usdollar * opportunities.probability / 100) as weighted_amount '
					. ' FROM opportunities ';
		if ($need_users) $query .= ' LEFT JOIN users ON users.id=opportunities.assigned_user_id ';
		$query .= " WHERE $where";
		
		$result = $this->db->query($query);
		echo mysql_error();
		return $this->db->fetchByAssoc($result);
	}

	function get_view_closed_where_basic($param)
	{
		return $param['value'] ? '1' : $this->filter_closed();
	}
	
	function get_view_closed_where_advanced()
	{
		return '1';
	}
	
	function get_sales_stage_where_basic()
	{
		return '1';
	}
	
	function get_sales_stage_where_advanced($param)
	{
		$where = '1';
		if($param['value'] == 'Other')
			$where = $this->filter_closed();
		else if($param['value'] && $param['value'] != 'empty')
			$where = sprintf("opportunities.sales_stage = '%s'", $this->db->quote($param['value']));
		return $where;
	}

	function getDefaultListWhereClause()
	{
		return $this->filter_closed();
	}

	function get_search_prob_options()
	{
		return array(''=>'', '25'=>'25+', '50'=>'50+', '75'=>'75+');
	}
	
	function get_search_stage_options()
	{
		global $app_list_strings, $app_strings, $mod_strings;
		$sales_stage_dom = $app_list_strings['sales_stage_dom'];
		return array('empty' => $app_strings['LBL_NONE'], 'Other' => $mod_strings['LBL_NOT_CLOSED']) + $sales_stage_dom;
	}
	// longreach - end added
	
	/**
	 * Static helper function for getting releated account info.
	 */
	function get_account_detail($opp_id=null) {
		global $db;
		$ret_array = array();
		if(isset($opp_id) && $opp_id != $this->opp_id) {
			$query = "SELECT acc.id, acc.name, acc.assigned_user_id "
				. "FROM opportunities opp, accounts acc "
				. "WHERE acc.id=opp.account_id"
				. " AND opp.id='$opp_id'"
				. " AND acc.deleted=0";
		} else if($this->account_id) {
			$query = "SELECT acc.id, acc.name, acc.assigned_user_id "
				. "FROM accounts acc WHERE acc.id='{$this->account_id}'"
				. " AND acc.deleted=0";
		} else
			return $ret_array;
		$result = $db->query($query, true,"Error filling in opportunity account details: ");
		$row = $db->fetchByAssoc($result);
		if($row != null) {
			$ret_array['name'] = $row['name'];
			$ret_array['id'] = $row['id'];
			$ret_array['assigned_user_id'] = $row['assigned_user_id'];
		}
		return $ret_array;
	}

    static function get_duplicates_where(RowUpdate &$upd) {
        $clause = array(
            'value' => $upd->getField('name'),
            'field' => 'name',
            'operator' => 'like',
            'match' => 'prefix'
        );

        return array('name' => $clause);
    }

    static function find_duplicates(RowUpdate &$upd, $redirect = true) {
        require_once('include/layout/DuplicateManager.php');
        $manager = new DuplicateManager($upd, $_REQUEST);
        return $manager->check(self::get_duplicates_where($upd), $redirect);
    }

    static function calc_weighted_amount($amount, $prob) {
        $weighted_amount = ($amount * $prob) / 100;
        return $weighted_amount;
    }

    static function init_record(RowUpdate &$upd, $input) {
        if (! empty($input['quote_id'])) {
            self::init_from_quote($upd, $input);
        } else {
            $update = array();

            $fields = array('name', 'amount', 'date_closed', 'sales_stage',
                'probability', 'currency_id', 'lead_source', 'partner_id', 'campaign_id',
                'account_id', 'assigned_user_id', 'exchange_rate', 'description');
            $field = null;

            for ($i = 0; $i < sizeof($fields); $i++) {
                $field = $fields[$i];
                if (isset($input[$field])) {
                    $update[$field] = $input[$field];
                }
            }

            $upd->set($update);

            $contact_id = array_get_default($input, 'contact_id');
            if($contact_id && ($ctc = ListQuery::quick_fetch('Contact', $contact_id))) {
                $upd->set('account_id', $ctc->getField('primary_account_id'));
            }

            $account_id = $upd->getField('account_id');
            if($account_id && ($acc = ListQuery::quick_fetch('Account', $account_id))) {
                $upd->set('currency_id', $acc->getField('currency_id'));
                $upd->set('exchange_rate', $acc->getField('exchange_rate'));
            }

            if( ($ss = $upd->getField('sales_stage')) && ! strlen($upd->getField('probability'))) {
                $prob_map = self::probability_map();
                if(isset($prob_map[$ss])) {
                    $upd->set('probability', $prob_map[$ss]);
                }
            }
        }
    }

    static function init_from_quote(RowUpdate &$upd, $input) {
        $update = array();

        $quote = ListQuery::quick_fetch('Quote', $input['quote_id']);

        if ($quote) {
            $relations = array(
                'assigned_user_id' => 'assigned_user_id',
                'name' => 'name',
                'amount' => 'amount',
                'currency_id' => 'currency_id',
                'exchange_rate' => 'exchange_rate',
                'description' => 'description',
                'billing_account_id' => 'account_id',
                'billing_account_name' => 'account_name',
                'valid_until' => 'date_closed',
            );

            foreach ($relations as $key => $value) {
                $update[$value] = $quote->getField($key);
            }
        }

        $update['lead_source'] = 'Self Generated';
        $update['opportunity_type'] = 'New Business';
        $update['sales_stage'] = 'Proposal/Price Quote';
        $map = self::probability_map();
        $update['probability'] = array_get_default($map, $update['sales_stage'], 0);

        $upd->set($update);
    }

    static function before_save(RowUpdate &$upd) {
    	$prev_stage = $upd->getField('sales_stage', null, true);
    	$stage = $upd->getField('sales_stage');
		if(preg_match('/^Closed .*/', $stage) && (empty($prev_stage) || $prev_stage != $stage)) {
			// if opportunity has closed and the close date is in the future then make it today.
			$closed = $upd->getField('date_closed');
			$now = gmdate('Y-m-d');
			if(empty($closed) || $closed > $now) {
				$upd->set('date_closed', $now);
			}
		}
    }
	
	static function after_save(RowUpdate $upd) {
		$acct = $upd->getField('account_id');
		if($acct)
			$upd->addUpdateLink('accounts', $acct);
	
		self::update_forecasts($upd);

        if (! empty($_REQUEST['quote_id'])) {
            $quote_result = ListQuery::quick_fetch('Quote', $_REQUEST['quote_id']);
            if ($quote_result){
                $quote_update = RowUpdate::for_result($quote_result);
                $quote_update->set('opportunity_id', $upd->getPrimaryKeyValue());
                $quote_update->save();
            }

        }
	}

    static function send_notification(RowUpdate $upd) {
        $vars = array(
            'OPPORTUNITY_NAME' => array('field' => 'name', 'in_subject' => true),
            'OPPORTUNITY_AMOUNT' => array('field' => 'amount'),
            'OPPORTUNITY_CLOSEDATE' => array('field' => 'date_closed'),
            'OPPORTUNITY_STAGE' => array('field' => 'sales_stage'),
            'OPPORTUNITY_DESCRIPTION' => array('field' => 'description')
        );

        $manager = new NotificationManager($upd, 'OpportunityAssigned', $vars);

        if ($manager->wasRecordReassigned())
            $manager->sendMails();
    }

	static function add_convert_project(DetailManager $mgr) {
		$mgr->getLayout()->addScriptInclude('modules/Opportunities/convert.js');
		$mgr->getLayout()->addFormInitHook('initOppConvert(this)');
	}

    static function add_additional_hiddens(DetailManager $mgr) {
        if (! empty($_REQUEST['quote_id']))
            $mgr->layout->addFormHiddenFields(array('quote_id' => $_REQUEST['quote_id']), false);
    }
    
    static function probability_map() {
    	return AppConfig::setting("lang.lists.base.app.sales_probability_dom", array());
    }

	static function mass_fill_probability($mu, RowUpdate $upd, $input)
	{
		$origStage = $upd->getField('sales_stage', null, true);
		$newStage = $upd->getField('sales_stage', null, false);

		$prob = self::probability_map();
		$cate = AppConfig::setting("lang.lists.base.app.sales_forecast_dom");
		if (isset($input['sales_stage'])) {
			if ($origStage !== $newStage) {
				if (isset($prob[$newStage])) {
					$upd->set('probability', $prob[$newStage]);
				}
				if (!isset($input['forecast_category']) && isset($cate[$newStage])) {
					$upd->set('forecast_category', $cate[$newStage]);
				}
			}
		}
	}

    static function get_activity_status(RowUpdate $upd) {
        $status = null;

        if ($upd->getField('sales_stage') != $upd->getField('sales_stage', null, true)) {
            if ($upd->getField('sales_stage') == 'Closed Lost') {
                $status = 'lost';
            } elseif ($upd->getField('sales_stage') == 'Closed Won') {
                $status = 'won';
            }
        }

        return $status;
    }
}
?>
