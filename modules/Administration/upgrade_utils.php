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



/**
 * Remove duplicate rows in the config table and add a primary key on (category,name)
 */
function addConfigPrimaryKey() {
	global $db;
	
	// check for existing index
	$q = "SHOW INDEX FROM config WHERE Key_name='PRIMARY'";
	$qr = $db->query($q);
	if($db->fetchRows($qr))
		return false; // already exists

	// query duplicate rows
	$dups_q = "SELECT category,name,value,count(*) AS c FROM config GROUP BY category,name HAVING c > 1";
	$qr = $db->query($dups_q);
	$rows = $db->fetchRows($qr);
	if(! $rows) $rows = array();
	
	$refetch = array();
	$rf_result = array();
	foreach($rows as $r) {
		if($r['category'] == 'company' && substr($r['name'], -9) == '_sequence')
			$refetch[] = $db->quote($r['name']);
	}
	if($refetch) {
		// fetch maximum value for duplicate sequence rows
		$rfin = implode("','", $refetch);
		$q = "SELECT name,MAX(value) AS val FROM config WHERE category='company' AND name IN ('$rfin') GROUP BY name";
		$qr = $db->query($q);
		$rf_rows = $db->fetchRows($qr);
		if($rf_rows)
		foreach($rf_rows as $r)
			$rf_result[$r['name']] = $r['val'];
	}
	
	// now delete duplicate rows, and reinsert retrieved values
	foreach($rows as $r) {
		$val = $r['value'];
		if($r['category'] == 'company' && isset($rf_result[$r['name']]))
			$val = $rf_result[$r['name']];
		$cat = $db->quote($r['category']);
		$name = $db->quote($r['name']);
		$val = $db->quote($val);
		$q1 = "DELETE FROM config WHERE category='$cat' AND name='$name'";
		$q2 = "INSERT INTO config (category,name,value) VALUES ('$cat', '$name', '$val')";
		$db->query($q1);
		$db->query($q2);
	}
	
	// add the primary key
	return $db->query("ALTER TABLE config ADD PRIMARY KEY (category, name)");
}


function updateFlagValues() {
	global $db;
	$all_models = AppConfig::setting('model.index.by_type.bean');
	
	foreach($all_models as $model_name) {
		$detail = AppConfig::setting("model.detail.$model_name");
		$table_name = array_get_default($detail, 'table_name');
		$fields = AppConfig::setting("model.fields.$model_name");
		$chk = array();
		if($fields)
		foreach($fields as $f) {
			if($f['type'] == 'bool' && $f['name'] != 'deleted')
				$chk[$f['name']] = array_get_default($f, 'default', 0);
		}
		if($chk) {
			$sql = "SHOW FULL COLUMNS FROM $table_name";
			$result = $db->query($sql);
			$found = array();
			if($result) {
				while($row = $db->fetchByAssoc($result)) {
					if(isset($chk[$row['Field']]) && $row['Type'] == 'varchar(3)')
						$found[$row['Field']] = $row;
				}
			}
			foreach($found as $fname => $fdesc)
				preUpdateFlag($table_name, $fname, $chk[$fname]);
		}
	}
}


function preUpdateFlag($table, $flag_name, $default=0) {
    global $db;
    $update_null_flag = "UPDATE `$table` SET `$flag_name` = '$default' WHERE `$flag_name` IS NULL OR `$flag_name` = ''";
    $db->query($update_null_flag, false);
    $update_on_flag = "UPDATE `$table` SET `$flag_name` = '1' WHERE `$flag_name` = 'on'";
    $db->query($update_on_flag, false);
    $update_off_flag = "UPDATE `$table` SET `$flag_name` = '0' WHERE `$flag_name` = 'off'";
    $db->query($update_off_flag, false);
}


/**
 * Move some earlier settings from database to local_config
 */
function upgradeConfigSettings() {
	global $db;
	$upgrade = array(
		'export' => array(
			'enabled',
			'charset',
			'admin_only',
		),
		'mail' => array(
			'smtpserver' => 'email.smtp_server',
			'smtpport' => 'email.smtp_port',
			'sendtype' => 'email.send_type',
			'smtpuser' => 'email.smtp_user',
			'smtppass' => 'email.smtp_password',
			'smtpauth_req' => 'email.smtp_auth_req',
		),
		'notify' => array(
			'fromaddress' => 'notify.from_address',
			'fromname' => 'notify.from_name',
			'send_by_default',
			'on' => 'notify.enabled',
			'send_from_assigning_user',
			'cases_user_create',
			'cases_user_update',
			'cases_contact_create',
			'cases_contact_update',
		),
		'proxy' => array(
			'on' => 'proxy.enabled',
			'host' => 'proxy.host_name',
			'port',
			'auth' => 'proxy.auth_req',
			'username' => 'proxy.user_name',
			'password',
		),
		'massemailer' => array(
			'campaign_emails_per_run',
			'tracking_entities_location_type',
			'email_copy',
		),
		'telephony' => array(
			'integration',
			'format_phones',
			'country',
			'local_digits',
			'local_code',
			'country_code',
			'ext',
		),
	);
	$query = "SELECT * FROM config WHERE category IN ('export', 'mail', 'notify', 'proxy', 'massemailer', 'telephony')";
	$result = $db->query($query, false);
	if($result) {
		while( ($row = $db->fetchByAssoc($result, -1, false)) ) {
			if(isset($upgrade[$row['category']][$row['name']])) {
				AppConfig::set_local($upgrade[$row['category']][$row['name']], $row['value']);
			}
			else if(in_array($row['name'], $upgrade[$row['category']])) {
				AppConfig::set_local($row['category'] . '.' . $row['name'], $row['value']);
			}
		}
	}
	AppConfig::save_local();	
}


/**
 * Delete duplicate records in link tables and
 * update PRIMARY KEYs
 *
 * @return void
 */
function updateLinkTables() {
    global $db;

    //Fix `user_preferences` table
    $db->query("DELETE FROM `user_preferences` WHERE `user_preferences`.`assigned_user_id` IS NULL OR deleted", false);
    $db->query("ALTER TABLE `user_preferences` DROP `deleted`, DROP `date_entered`", false);

    $meta = AppConfig::setting("model.index.by_type.link");
    $meta[] = 'user_preferences';
    $meta[] = 'mail_threads';

    foreach($meta as $m) {
        $t = AppConfig::setting("model.detail.$m.table_name");
        $pk = AppConfig::setting("model.detail.$t.primary_key");
        
		// new tables, skip adjustment
        if($t == 'bills_payments' || $t == 'credits_payments') {
        	continue;
        }
        // never had an id column
        if($t == 'users_feeds') {
        	continue;
        }

        //delete deleted records
        if($t != 'user_preferences' && $t != 'workflow_data_audit_link') {
			$db->query("DELETE FROM `$t` WHERE deleted", false);
		}

        if(is_array($pk)) {
            $clause = array();
            for ($i = 0; $i < sizeof($pk); $i++) {
                $clause[] = "t1.".$pk[$i]." = t2.".$pk[$i];
            }
            $clause = implode(' AND ', $clause);
            
            //remove duplicate records
            $delete_query = "DELETE t1.* FROM $t t1 LEFT JOIN $t t2 ON ($clause AND t1.id < t2.id) WHERE t2.id IS NOT NULL";
            $result = $db->query($delete_query, false);

            $pk_str = implode("`, `", $pk);
            //update link table
            $fix_query0 = "SHOW INDEX FROM `$t` WHERE Key_name='PRIMARY'";
            $r = $db->query($fix_query0, false);
            if($r && $db->getRowCount($r)) {
				$fix_query1 = "ALTER TABLE `$t` DROP PRIMARY KEY";
				$db->query($fix_query1, false);
			}
            $fix_query2 = "ALTER TABLE `$t` ADD PRIMARY KEY (`$pk_str`), DROP `id`";
            $db->query($fix_query2, false);
        }
    }
}

/**
 * Update currency_id fields to compatibility
 * with infoathand 7.0
 *
 * @return void
 */
function upgradeCurrency() {
    global $db;

    $query = "SHOW TABLES";
    $res = $db->query($query, false);

    while ($row = $db->fetchByAssoc($res)) {
        $table = $row['Tables_in_' . $db->dbName];

        $columns_query = "SHOW COLUMNS FROM `" .$table. "`";
        $columns_res = $db->query($columns_query, false);
        $columns = $db->fetchRows($columns_res);

        for ($i = 0; $i < sizeof($columns); $i++) {
        	$field_name = $columns[$i]['Field'];
            if (substr($field_name, -11) == 'currency_id') {
                $update_query = "UPDATE `".$table."` SET `$field_name` = '-99' WHERE `$field_name` IS NULL OR `$field_name` = ''";
                $db->query($update_query);
                break;
            }
        }

    }
}

/**
 * Update some db fields to compatibility with
 * infoathand 7.0: date/time fields
 *
 * @return void
 */
function additionalDbUpdate() {
    $tables = array ("calls", "meetings", "project_task", "booked_hours", "tasks", "emails", "email_marketing");

    updateDateTime('event_reminders', 'date_send', 'time_send', false);

    foreach($tables as $t) {
		$allow_null = true;
		if($t == 'booked_hours' || $t == 'project_task')
			$allow_null = false;
        updateDateTime($t, 'date_start', 'time_start', $allow_null);

        if ($t == "project_task" || $t == "tasks") {
            updateDateTime($t, 'date_due', 'time_due', $allow_null);
        }
    }

	// check
    //updateFlag("project_task", "milestone_flag");
    //updateFlag("users", "in_directory");
}


/**
 * Update durations for info@hand 7.0
 *
 * @return void
 */
function updateDurations() {
    global $db;
    $hours_per_day = AppConfig::setting('company.hours_in_work_day', 8);
    $tables = array ("calls", "meetings", "booked_hours", "tasks");

    foreach($tables as $t) {
        if ($t == "calls" || $t == "meetings") {
            $duration_query = "ALTER TABLE ".$t." ADD `duration` INT( 4 ) UNSIGNED NULL AFTER `name`";
            $db->query($duration_query, false);

            $update_durations = "UPDATE ".$t." SET `duration` = ((`duration_hours` * 60) + `duration_minutes`)";
            $db->query($update_durations, false);

            drop($t, 'duration_hours');
            drop($t, 'duration_minutes');

        } elseif ($t == "booked_hours") {
            $temp_query = "ALTER TABLE ".$t." ADD `duration` INT( 4 ) UNSIGNED NULL";
            $db->query($temp_query, false);

            $update_temp = "UPDATE ".$t." SET `duration` = (`quantity` * 60)";
            $db->query($update_temp, false);

            $hours_query = "ALTER TABLE ".$t." CHANGE `quantity` `quantity` INT(10) UNSIGNED NOT NULL";
            $db->query($hours_query, false);

            $update_hours = "UPDATE ".$t." SET `quantity` = `duration`";
            $db->query($update_hours, false);

            drop($t, 'duration');
        } elseif ($t == 'tasks') {
            $get_effort_query = "SELECT `id`, `effort_estim`, `effort_estim_unit`, `effort_actual`, `effort_actual_unit` FROM `tasks`";
            $result = $db->query($get_effort_query);

            while($row = $db->fetchByAssoc($result)) {
                $estim = $row['effort_estim'];
                if(isset($estim) && $estim !== '') {
					if ($row['effort_estim_unit'] == "hours") {
						$estim = $estim * 60;
					} elseif ($row['effort_estim_unit'] == "days") {
						$estim = $estim * ($hours_per_day * 60);
					}
	
					$estim_update_query = "UPDATE ".$t." SET `effort_estim` = '".$estim."' WHERE `id` = '".$row['id']."'";
					$db->query($estim_update_query, false);
				}

                $actual = $row['effort_actual'];
                if(isset($actual) && $actual !== '') {
					if ($row['effort_actual_unit'] == "hours") {
						$actual = $actual * 60;
					} elseif ($row['effort_actual_unit'] == "days") {
						$actual = $actual * ($hours_per_day * 60);
					}
	
					$actual_update_temp = "UPDATE ".$t." SET `effort_actual` = '".$actual."' WHERE `id` = '".$row['id']."'";
					$db->query($actual_update_temp, false);
				}
            }
            
            $estim_query = "ALTER TABLE ".$t." CHANGE `effort_estim` `effort_estim` INT(10) UNSIGNED NULL";
            $db->query($estim_query, false);
            $actual_query = "ALTER TABLE ".$t." CHANGE `effort_actual` `effort_actual` INT(10) UNSIGNED NULL";
            $db->query($actual_query, false);
        }
    }
}

/**
 * Fill Expense Item new tax field
 *
 */
function fillExpenseItemsTax() {
    global $db;
    $query = "UPDATE `expense_items` SET `tax` = IF((`total` - `amount`) > 0, `total` - `amount`, 0), `tax_usdollar` = IF((`total_usdollar` - `amount_usdollar`) > 0, `total_usdollar` - `amount_usdollar`, 0)";
    $db->query($query, false);
}

/**
 * Fill new projects totals fields (calc financials)
 *
 */
function fillProjectTotalAmounts() {
    global $db;

    $query = "UPDATE project AS target
        INNER JOIN (
            SELECT project_id, expected_cost_sum, expected_revenue_sum, actual_cost_sum, actual_revenue_sum,
            expected_cost_usd_sum, expected_revenue_usd_sum, actual_cost_usd_sum, actual_revenue_usd_sum
            FROM project_financials pf INNER JOIN (
                SELECT project_id AS prj_id,
                SUM(expected_cost) AS expected_cost_sum, SUM(expected_revenue) AS expected_revenue_sum,
                SUM(actual_cost) AS actual_cost_sum, SUM(actual_revenue) AS actual_revenue_sum,
                SUM(expected_cost_usd) AS expected_cost_usd_sum, SUM(expected_revenue_usd) AS expected_revenue_usd_sum,
                SUM(actual_cost_usd) AS actual_cost_usd_sum, SUM(actual_revenue_usd) AS actual_revenue_usd_sum
                FROM project_financials
                GROUP BY project_id
        ) AS prf
        WHERE pf.project_id  = prf.prj_id
        GROUP BY project_id
        ) AS source
        ON target.id = source.project_id
        SET target.total_expected_cost = source.expected_cost_sum, target.total_expected_revenue = source.expected_revenue_sum,
        target.total_actual_cost = source.actual_cost_sum, target.total_actual_revenue = source.actual_revenue_sum,
        target.total_expected_cost_usdollar = source.expected_cost_usd_sum, target.total_expected_revenue_usdollar = source.expected_revenue_usd_sum,
        target.total_actual_cost_usdollar = source.actual_cost_usd_sum, target.total_actual_revenue_usdollar = source.actual_revenue_usd_sum";

    $db->query($query, false);
}

function convertRecurringInvoices()
{
	global $db;
	require_once 'include/database/RowUpdate.php';
	require_once 'include/database/ListQuery.php';
	require_once 'modules/Invoice/Invoice.php';

	$lq = new ListQuery('CompanyAddress');
	$lq->addSimpleFilter('main', 1);
	$vendor =  $lq->runQuerySingle();
	if ($vendor)
		$vendor_id = $vendor->getField('id');
	else
		$vendor_id = '';

	$cat = RowUpdate::blank_for_model('BookingCategory');
	$cat->set('name', 'Service category for converted recurring invoices');
	$cat->set('booking_class', 'services-monthly');
	$cat->save();

	$query = "SELECT r.*, i.invoice_date, i.assigned_user_id invoice_user FROM recurrence_rules r LEFT JOIN invoice i ON i.id = r.parent_id WHERE r.parent_type='Invoice'  AND r.freq = 'MONTHLY' AND r.freq_interval = 1 AND r.deleted=0 AND i.deleted=0 ";
	$res = $db->query($query);
	while ($rec = $db->fetchByAssoc($res)) {
		$day = 1;
		if ((string)$rec['rule'] !== '') {
			$m = array();
			if (!preg_match('/^BYMONTHDAY=(\d+)$/i', $rec['rule'], $m))
				continue;
			$day = $m[1];
		}
		$start_date = addMonth($rec['invoice_date'], $day);
		
		list($byDate) = explode(' ', $rec['until']);
		if ($rec['limit_count'])
			$byMax = gmdate('Y-m-d', strtotime($start_date . ' GMT + ' . $rec['limit_count'] . ' MONTH'));
		else $byMax = null;
		$end_date = null;
		if ($byDate || $byMax) {
			if ($byDate && $byMax) {
				if ($byDate < $byMax)
					$end_date = $byDate;
				else
					$end_date = $byMax;
			} else if ($byDate) {
				$end_date = $byDate;
			} else {
				$end_date = $byMax;
			}
		}
		if (!$end_date)
			$end_date = '2100-12-31';

		$quote = RowUpdate::blank_for_model('Quote');
		$result = ListQuery::quick_fetch('Invoice', $rec['parent_id']);
		$invoice = RowUpdate::for_result($result);
		Invoice::init_from_tally($quote, $invoice);
		$quote->set('valid_until', $invoice->getField('invoice_date'));
		$quote->save();

		$service = RowUpdate::blank_for_model('MonthlyService');

		$data = array(	
			'assigned_user_id' => $rec['invoice_user'],
			'booking_category_id' => $cat->getPrimaryKeyValue(),
			'quote_id' => $quote->getPrimaryKeyValue(),
			'account_id' => $quote->saved['billing_account_id'],
			'cc_user_id' => $rec['invoice_user'],
			'invoice_value' => $quote->saved['amount_usdollar'],
			'purchase_order_num' => $quote->saved['purchase_order_num'],
			'invoice_terms' =>  $quote->saved['terms'],
			'start_date' => $start_date,
			'end_date' => $end_date,
			'billing_day' => $day,
			'address_id' => $vendor_id,
		);
		if ($rec['date_last_instance']) {
			$dd = $d;
			$data['next_invoice'] = addMonth($rec['date_last_instance'], $dd);
		}
		$service->set($data);
		$service->save();
		$service->addUpdateLink('invoices', $invoice->getPrimaryKeyValue());
	}

	$db->query("DELETE FROM recurrence_rules WHERE parent_type = 'Invoice'");
}


function splitCreditNotes() {
	global $db;

	require_once 'include/database/RowUpdate.php';
	require_once 'include/database/ListQuery.php';

	$copy = array(
		'id',
		'prefix',
		'invoice_number' => 'credit_number',
		'date_entered',
		'date_modified',
		'modified_user_id',
		'assigned_user_id',
		'created_by',
		'deleted',
		'cancelled',
		'name',
		'opportunity_id',
		'due_date',
		'billing_account_id',
		'billing_contact_id',
		'billing_address_street',
		'billing_address_city',
		'billing_address_state',
		'billing_address_postalcode',
		'billing_address_country',
		'billing_phone',
		'billing_email',
		'currency_id',
		'exchange_rate',
		'description',
		'amount',
		'amount_usdollar',
		'amount_due',
		'amount_due_usdollar',
		'tax_information',
		'tax_exempt',
		'discount_before_taxes',
		'pretax',
		'pretax_usd',
	);

	$notes_result = $db->query("SELECT * FROM invoice WHERE credit_note = 1  AND deleted = 0");
	while ($note = $db->fetchByAssoc($notes_result)) {
		$payments_result = $db->query("SELECT * FROM invoices_payments WHERE invoice_id='{$note['id']}' AND deleted=0");
		while ($payment = $db->fetchByAssoc($payments_result)) {
			$fields = array(
				'credit_id' => "'{$note['id']}'",
				'currency_id' => "'{$payment['currency_id']}'",
				'payment_id' => "'{$payment['payment_id']}'",
				'date_modified' => "'{$payment['date_modified']}'",
				'amount' =>  (float)$payment['amount'],
				'amount_usdollar' => (float)$payment['amount_usdollar'],
				'exchange_rate' =>  (float)$payment['exchange_rate'],
			);
			$query = "INSERT into credits_payments (";
			$query .= join(', ', array_keys($fields));
			$query .= ") VALUES (";
			$query .= join(', ', $fields);
			$query .= ")";
			$db->query($query);
			$query = "UPDATE invoices_payments SET invoice_id = '{$note['refunded_invoice_id']}' WHERE invoice_id='{$note['id']}' AND  payment_id='{$payment['payment_id']}'";
			$db->query($query);
		}

		$fields = array();
		foreach ($copy as $from => $to) {
			if (is_numeric($from))
				$from = $to;
			$fields[$to] = "'" . $note[$from] . "'";
		}

		$query = "INSERT into credit_notes (";
		$query .= join(', ', array_keys($fields));
		$query .= ") VALUES (";
		$query .= join(', ', $fields);
		$query .= ")";
		$db->query($query);

		$components = array(
			'InvoiceAdjustment' => 'CreditNoteAdjustment',
			'InvoiceComment' => 'CreditNoteComment',
			'InvoiceLine' => 'CreditNoteLine',
			'InvoiceLineGroup' => 'CreditNoteLineGroup',
		);

		foreach ($components as $from => $to) {
			$lq = new ListQuery($from);
			$invoice_id_field = ($from == InvoiceLineGroup) ? 'parent_id' : 'invoice_id';
			$lq->addSimpleFilter($invoice_id_field, $note['id']);
			$result = $lq->runQuery(0, null, null, null, 100);
			while(! $result->failed) {
				foreach($result->getRowIndexes() as $idx) {
					$row = $result->getRowResult($idx);
					$update = RowUpdate::blank_for_model($to);
					$update->set($row->row);
					$update->set('credit_id', $note['id']);
					$update->save();

					$update = new RowUpdate($row);
					$update->markDeleted();
				}
				if($result->page_finished)
					break;
				$lq->pageResult($result);
			}
		}
		
		$query = "UPDATE invoice SET deleted = 1 WHERE id='{$note['id']}'";
		$db->query($query);
	}
}


function copyCustomLang() {
    $old_custom_files = glob_unsorted(
        AppConfig::custom_dir().'include/language/*.lang.php',
        AppConfig::custom_dir().'modules/*/language/*.lang.php'
    );

    foreach($old_custom_files as $old_file) {
        $app_list_strings = $app_strings = array();
        $mod_strings = array();
		$changed = false;

        if(is_file($old_file)) {
            $path = explode('/', $old_file);

            $lang_file_name = basename($old_file);
            $lang = explode('.', $lang_file_name);
            $lang = $lang[0];
            $key = null;

            include($old_file);

            if ($path[1] == 'include') {
                foreach ($app_list_strings as $dom_name => $dom) {
                	if(is_array($dom)) {
                		$key = "lang.lists.{$lang}.app.{$dom_name}";
                		if(AppConfig::setting($key) === null)
                			AppConfig::set_local($key, $dom);
                		$changed = true;
                	}
                }
                foreach ($app_strings as $label_key => $label_value) {
                	if(is_string($label_value)) {
                		$key = "lang.strings.{$lang}.app.{$label_key}";
                		if(AppConfig::setting($key) === null)
                			AppConfig::set_local($key, $label_value);
                		$changed = true;
                	}
                }
            } elseif ($path[1] == 'modules') {
                $module = $path[2];
                foreach ($mod_strings as $label_key => $label_value) {
                	if(is_string($label_value)) {
                		$key = "lang.strings.{$lang}.{$module}.{$label_key}";
                		if(AppConfig::setting($key) === null)
                			AppConfig::set_local($key, $label_value);
                		$changed = true;
                	}
                }
            }

			if($changed)
				AppConfig::save_local('lang');
            upgrade_unlink($old_file);
        }
    }
}


/**
 * Update fields meta data to compatibility
 * with infoathand 7.0
 *
 * @return bool
 */
function updateFieldsMetaData() {
    global $db;

    $q = "SELECT * FROM fields_meta_data WHERE NOT deleted";
    $r = $db->query($q);
    $qs = array();
    $fail = false;
    if($r) {
        while( ($row = $db->fetchByAssoc($r)) ) {
            if(! array_key_exists('custom_bean', $row)) {
                echo '<p>Perform database repair first.</p>';
                $fail = true;
                break;
            }
            if(empty($row['custom_bean'])) {
                $bean = AppConfig::module_primary_bean($row['custom_module']);
                $qs[] = "UPDATE fields_meta_data SET custom_bean='$bean' WHERE id='{$row['id']}'";
            }
        }
    }

    if(! $fail) {
        foreach($qs as $q)
            $db->query($q);
    }

    return $fail;
}

//DB update utils
function updateFlag($table, $flag_name) {
    global $db;
    $update_on_flag = "UPDATE `".$table."` SET `".$flag_name."` = 1 WHERE `".$flag_name."` = 'on'";
    $db->query($update_on_flag, false);
    $update_off_flag = "UPDATE `".$table."` SET `".$flag_name."` = 0 WHERE `".$flag_name."` = 'off'";
    $db->query($update_off_flag, false);

    $flag_query = "ALTER TABLE `".$table."` CHANGE `".$flag_name."` `".$flag_name."` TINYINT( 1 ) NOT NULL DEFAULT '0'";
    $db->query($flag_query, false);
}

function updateDateTime($table, $date_field, $time_field, $allow_null=true) {
    global $db;
    $date_query = "ALTER TABLE ".$table." MODIFY COLUMN ".$date_field." datetime NULL;";
    $db->query($date_query, false);

	$val = "CONCAT(DATE_FORMAT(`$date_field`, '%Y-%m-%d'), ' ', IFNULL(`$time_field`, '00:00:00'))";
	if($table == 'tasks') $val = "IF(`{$date_field}_flag`, NULL, $val)";
    $update_dates = "UPDATE $table SET `$date_field` = $val";
    $db->query($update_dates, false);

    drop($table, $time_field);
    
    if(! $allow_null) {
		$date_query = "ALTER TABLE ".$table." MODIFY COLUMN ".$date_field." datetime NOT NULL;";
		$db->query($date_query, false);
	}
}

function drop($table, $field) {
    global $db;
    $drop_query = "ALTER TABLE ".$table." DROP `".$field."`";
    $db->query($drop_query, false);
}


function deletePre70Files() {
    $old_files = array(
        'config.php', 'config_override.php', 'json_server.php', 'dictionary.php', 'export.php', 'metagen.php',
        'PEAR.php', 'HandleAjaxCall.php', 'SugarSecurity.php', 'TreeData.php', 'Validate.php',
        'include/Dashlets/DashletGenericConfigure.tpl',
        'include/Dashlets/DashletGenericDisplay.tpl', 'include/DetailView/DetailView.php', 'include/json_config.php',
        'include/time.php', 'include/javascript/javascript.php', 'include/layout/ModulePage.php', 'include/layout/DashletPage.php',
        'include/Popups/Popup_picker.php', 'cache/dashlets/dashlets.php', 'include/export_utils.php',
        'modules/BeanDictionary.php', 'modules/TableDictionary.php',
        'modules/Administration/UpgradeWizard.php', 'modules/Administration/UpgradeWizardCommon.php',
        'modules/Administration/UpgradeWizard_commit.php', 'modules/Administration/UpgradeWizard_prepare.php');
    deleteFilesGroup($old_files);

    deleteOldDuplicateActions();

    $old_dirs = array(
		'cache/studio',
    	'include/EditView',
    	'include/CurrencyService',
    	'metadata',
    	'Mail',
    	'Net',
    	'ModuleInstall',
    	'Payment',
    	'Validate',
    	'modules/CustomFields',
    	'modules/Dropdown',
    	'modules/Dynamic',
		'modules/DynamicFields',
		'modules/DynamicLayout',
		'modules/EditCustomFields',
    	'modules/Groups',
    	'modules/Help',
    	'modules/Import',
    	'modules/LabelEditor',
    	'modules/Studio',
    	'modules/Sync',
    	'modules/UpgradeWizard');
    deleteFoldersGroup($old_dirs);

    $lang_files = array(glob('include/language/*.case_*_notify.html'), glob('include/language/*.notify_template.*'),
        glob('include/language/*.lang.*'), glob('install/language/*.lang.*'));
    
    foreach ($lang_files as $index => $details) {
       deleteFilesGroup($details);
    }

    $modules_lang = glob('modules/*/language/*.lang*.php', GLOB_NOSORT);
    deleteFilesGroup($modules_lang);
    $dashlets_data = glob('modules/*/Dashlets/*/*.data.php', GLOB_NOSORT);
    deleteFilesGroup($dashlets_data);
    $metadata_files = glob('modules/*/metadata/*', GLOB_NOSORT);
    deleteFilesGroup($metadata_files);

    $subpanels = glob('modules/*/subpanels', GLOB_NOSORT);
    deleteFoldersGroup($subpanels);
    $tpls = glob('modules/*/tpls', GLOB_NOSORT);
    deleteFoldersGroup($tpls);

    $files_for_del = array('index.php', 'Delete.php', 'DetailView.php', 'DetailView.html', 'EditView.php', 'EditView.html',
        'ListView.php', 'ListView.html', 'Save.php', 'vardefs.php', 'SearchForm.html', 'Popup.php', 'Popup_picker.php',
        'Popup_picker.html', 'field_arrays.php', 'layout_defs.php', 'GenerateWebToLeadForm.php', 'RoiDetailView.php', 'RoiDetailView.html',
        'Schedule.html', 'Subscriptions.html', 'TrackDetailView.php', 'TrackDetailView.html', 'WebToLeadCreation.html', 'WebToLeadDownloadForm.html',
        'WebToLeadForm.html', 'WebToLeadFormSave.php', 'WizardEmailEditMbox.html', 'WizardEmailSetup.html', 'WizardEmailSetupSave.php', 'WizardHome.html',
        'WizardMarketing.html', 'WizardMarketingSave.php', 'WizardNewsletter.php', 'WizardNewsletter.html', 'WizardNewsletterSave.php',
        'asyncHoursEditView.php', 'asyncHoursSave.php', 'asyncMeetingEditView.php', 'asyncMeetingSave.php', 'asyncSearchResources.php', 'asyncSearchUsers.php',
        'HoursEditView.js', 'HoursEditView.tpl', 'MeetingsEditView.js', 'MeetingsEditView.tpl', 'defaultPreferences.php');

    $exclude_modules = array('Administration', 'Configurator',
        'Home', 'Import', 'LicenseInfo', 'MailMerge', 'MergeRecords',
        'NewStudio', 'UWizard');
    $exclude_files = array(
    	'Audit' => array('Popup_picker.php', 'Popup_picker.html'),
        'Calendar' => array('index.php'),
        'Directory' => array('index.php', 'ListView.php'),
        'Events' => array('DetailView.php'),
        'Feeds' => array('Save.php'),
        'Forecasts' => array('Save.php'),
        'Forums' => array('ListView.php'),
        'PrepaidAmounts' => array('index.php'),
        'Recurrence' => array('Popup.php'),
        'Reports' => array('DetailView.php'),
        'UserPreferences' => array('index.php'),
    );

	deleteModFiles($files_for_del, $exclude_modules, $exclude_files);
}

/**
 * Delete old ShowDuplicates actions
 */
function deleteOldDuplicateActions() {
    $modules = array('Accounts', 'Contacts', 'Leads', 'Opportunities', 'Prospects');

    for ($i = 0; $i < sizeof($modules); $i++) {
        $file = 'modules/' .$modules[$i] . '/ShowDuplicates';
        upgrade_unlink($file . '.php');
        upgrade_unlink($file . '.html');
    }
}

/**
 * Delete pre-7.0 upgrade and module zips and manifests so they don't confuse the upgrade loader
 */
function deleteUpgradeFiles() {
	$dirs = array('full', 'langpack', 'module', 'patch', 'theme');
	$files = array();
	foreach($dirs as $d) {
		$files = glob("cache/upload/upgrades/$d/*.php", GLOB_NOSORT);
		deleteFilesGroup($files);
		$files = glob("cache/upload/upgrades/$d/*.zip", GLOB_NOSORT);
		deleteFilesGroup($files);
	}
}


function upgrade_unlink($file) {
    if (strpos($file, 'module_info.php') === false && file_exists($file))
        unlink($file);
}

function deleteFoldersGroup($folders) {
    for ($i = 0; $i < sizeof($folders); $i++) {
    	if(file_exists($folders[$i]))
			rmdir_recursive($folders[$i]);
    }
}

function deleteFilesGroup($files) {
	if(is_array($files))
    for ($i = 0; $i < sizeof($files); $i++) {
        $file_name_pos = strrpos($files[$i], '/') + 1;
        $file_name = substr($files[$i], $file_name_pos);
        if ($file_name != 'CalendarViewerDefs.php' && $file_name != 'gen.lang.php')
            upgrade_unlink($files[$i]);
    }
}

function deleteModFiles($files_for_del, $exclude_mods = array(), $exclude_files = array()) {
    $mods = glob('modules/*', GLOB_ONLYDIR | GLOB_NOSORT);

	foreach($mods as $mod_path) {
		$mod_name_pos = strrpos($mod_path, '/') + 1;
		$mod_name = substr($mod_path, $mod_name_pos);
		if(in_array($mod_name, $exclude_mods))
			continue;
		
		$files = glob("modules/$mod_name/*", GLOB_NOSORT);
	    foreach($files as $file_path) {
			$file_name_pos = strrpos($file_path, '/') + 1;
			$file_name = substr($file_path, $file_name_pos);

        	if(isset($exclude_files[$mod_name]) && in_array($file_name, $exclude_files[$mod_name]))
				continue;
			elseif (in_array($file_name, $files_for_del))
				upgrade_unlink($file_path);
		}
    }
}


function move_cache_dirs($reverse=false) {
	$map_dirs = array(
		'cache/backups' => 'files/backups',
		'cache/email' => 'files/email',
		'cache/raw_email' => 'files/raw_email',
		'cache/images/directory' => 'files/images/directory',
		'cache/images/logos' => 'files/images/logos',
		'cache/reports' => 'files/reports',
		'cache/upload/upgrades' => 'cache/upgrades',
		'cache/upload/incoming' => 'cache/incoming',
		'cache/upload' => 'files/upload',
	);
	if($reverse) {
		$map_dirs = array_reverse(array_flip($map_dirs));
	}
	foreach($map_dirs as $k => $v) {
		if(! move_cache_dir($k, $v, $errmsg)) {
			pr2("Error moving $k to $v: $errmsg");
			return false;
		}
	}
	return true;
}

function is_empty_cache_dir($target) {
	$ignore = array('.', '..', 'index.html', 'Thumbs.db', '.DS_Store');
	if(is_dir($target) && ($d = opendir($target)) ) {
		while (false !== ($entry = readdir($d))) {
			if(! in_array($entry, $ignore))
				return false;
		}
		return true;
	}
	return false;
}

function move_cache_dir($source, $target, &$err) {
	if(! is_dir($source)) {
		// nothing to move
		return true;
	}
	if(! is_writable($source)) {
		$err = 'source not writable';
		return false;
	}
	if(is_file($target)) {
		$err = 'target is an existing file, not a directory';
		return false;
	}
	
	if(is_empty_cache_dir($target)) {
		// delete directory and move entire source over
		if(! rmdir_recursive($target)) {
			$err = 'failed to remove empty target directory';
			return false;
		}
	}

	if(! file_exists($target)) {
		$path = explode('/', $target);
		$tg_name = array_pop($path);
		$parent = implode('/', $path);
		if($parent && (is_dir($parent) || mkdir_recursive($parent))) {
			if(rename($source, $target))
				return true;
			else {
				$err = 'failed to move source to target path';
				return false;
			}
		} else {
			$err = 'error creating parent directory for target';
			return false;
		}
	}

	// move whatever contents we can
	$d = dir($source);
	$status = true;
	while($f = $d->read()){
		if($f == "." || $f == "..")
			continue;
		$src = "$source/$f";
		$targ = "$target/$f";
		if(! file_exists($targ))
			$status &= rename($src, $targ);
		else if($f == 'index.html' || $f == 'Thumbs.db' || $f == '.DS_Store')
			continue;
		else {
			$err = 'remove dir';
			$status = false;
		}
	}
	$d->close();
	if($status && is_empty_cache_dir($source)) {
		// remove directory if nothing left
		rmdir_recursive($source);
	}
	return $status;
}


function cleanupOldIndexes() {
	$old = array(
		'accounts_bugs' => array('idx_account_bug'),
		'accounts_cases' => array('idx_acc_case_acc'),
		'accounts_contacts' => array('idx_account_contact'),
		'accounts_opportunities' => array('idx_account_opportunity'),
		'accounts_threads' => array('idx_accounts_threads'),
		'acl_actions' => array('idx_aclaction_id_del'),
		'acl_roles_actions' => array('idx_acl_role_id', 'idx_acl_action_id', 'idx_aclrole_action'),
		'acl_roles_users' => array('idx_aclrole_user'),
		'bugs_threads' => array('idx_bugs_threads'),
		'calls_contacts' => array('idx_con_call_call', 'idx_call_contact'),
		'calls_users' => array('idx_usr_call_call', 'idx_call_users'),
		'cases_bugs' => array('idx_case_bug'),
		'cases_invoices' => array('cases_invoices_nodup'),
		'cases_skills' => array('idx_case_skill'),
		'cases_threads' => array('idx_cases_threads'),
		'config' => array('idx_config_cat'),
		'contacts_bugs' => array('idx_contact_bug'),
		'contacts_cases' => array('idx_contacts_cases'),
		'contacts_users' => array('idx_contacts_users'),
		'custom_fields' => array('idx_beanid_set_num'),
		'discounts_products' => array('idx_prod', 'idx_disc_product', 'idx_product_discount'),
		'document_relations' => array('document_relations_type'),
		'email_marketing_prospect_lists' => array('email_mp_prospects'),
		'emails' => array('idx_email_date_mod', 'idx_email_fldr', 'idx_email_fldr_read'),
		'emails_accounts' => array('idx_acc_email_email'),
		'emails_bugs' => array('idx_bug_email_email'),
		'emails_cases' => array('idx_case_email_email', 'idx_case_email_alt'),
		'emails_contacts' => array('idx_con_email_email'),
		'emails_invoices' => array('idx_invoice_email_email'),
		'emails_leads' => array('idx_lead_email_email'),
		'emails_opportunities' => array('idx_opp_email_email'),
		'emails_project_tasks' => array('idx_ept_email'),
		'emails_projects' => array('idx_project_email_email'),
		'emails_prospects' => array('idx_prospect_email_email'),
		'emails_purchaseorders' => array('idx_po_email_email'),
		'emails_quotes' => array('idx_quote_email_email'),
		'emails_salesorders' => array('idx_so_email_email'),
		'emails_tasks' => array('idx_task_email_email'),
		'emails_uidl' => array('uidl_boxhash'),
		'emails_users' => array('idx_usr_email_email'),
		'events_attendance' => array('idx_events_attendance'),
		'google_sync' => array('google_sync_ix'),
		'importdb_entities' => array('importdb_ent_prf', 'importdb_ent_del'),
		'importdb_history' => array('importdb_hist_ent_prf', 'importdb_hist_ent_scr', 'importdb_hist_ent_gen'),
		'inbound_email_autoreply' => array('idx_ie_autoreplied_to'),
		'invoice' => array('idx_refunded_invoice_id'),
		'invoices_payments' => array('idx_inv_pay_inv', 'idx_invoice_pay'),
		'kb_article_relations' => array('kb_article_relations_type'),
		'kb_articles_tags' => array('idx_kb_articles_tags'),
		'mail_threads' => array('mail_threads_thread_id'),
		'meetings_contacts' => array('idx_con_mtg_mtg', 'idx_meeting_contact'),
		'meetings_resources' => array('meetings_resources_ix1'),
		'meetings_users' => array('idx_usr_mtg_mtg', 'idx_meeting_users'),
		'opportunities_contacts' => array('idx_opportunities_contacts'),
		'opportunities_threads' => array('idx_opportunities_threads'),
		'products_assemblies' => array('idx_product_assembly'),
		'products_cases' => array('idx_product_case'),
		'products_suppliers' => array('idx_product_supplier'),
		'products_warehouses' => array('idx_product_warehaouse'),
		'project_relation' => array('idx_project_relation'),
		'project_threads' => array('idx_project_threads'),
		'projects_invoices' => array('projects_invoices_pid', 'projects_invoices_uniq', 'projects_invoices_nodup'),
		'projecttasks_users' => array('projecttasks_users_tid', 'projecttasks_users', 'projecttasks_users_nodup'),
		'prospect_list_campaigns' => array('idx_prospect_list_campaigns'),
		'prospect_lists_prospects' => array('idx_plp_rel_id'),
		'reports_users' => array('reports_users_rid', 'reports_users_uniq', 'reports_users_nodup'),
		'roles_modules' => array('idx_role_id'),
		'roles_users' => array('idx_ru_user_id'),
		'securitygroups_records' => array('securitygroups_records_rec_idx'),
		'securitygroups_users' => array('securitygroups_users_idxa', 'securitygroups_users_idxc'),
		'services_invoices' => array('services_invoices_pid', 'services_invoices_uniq', 'services_invoices_nodup'),
		'taxrates_history' => array('taxrates_history_id'),
		'users_activity' => array('user_activity_uid'),
		'users_feeds' => array('idx_ud_user_id'),
		'users_signatures' => array('idx_usersig_uid'),
		'users_skills' => array('idx_cas_skill_cas', 'idx_user_skill'),
		'users_threads' => array('idx_users_threads'),
		'workflow_data_audit' => array('related_id_idx', 'field_name_idx', 'workflow_audit_ix'),
		'workflow_data_audit_link' => array('workflow_link_ix'),
	);
	removeIndexes($old);
}


/**
 * Get old indexes (odd in 7.0)
 *
 * @return array
 */
function getOddIndexes() {
    $meta = AppConfig::setting("model.index.by_type");
    $new_version_indices = array();
    $dictionary = array();

    foreach ($meta as $type => $models) {
        for ($i = 0; $i < sizeof($models); $i++) {
            $indices = AppConfig::setting("model.indices.{$models[$i]}");
            if ($indices) {
                $table_name = AppConfig::setting("model.detail.$models[$i].table_name");
                foreach ($indices as $name => $details) {
                    if ($details['type'] != 'primary')
                        $new_version_indices[$table_name][] = $name;
                }
            }

            $dictionary[$models[$i]] = array();
            $module_dir = AppConfig::setting("model.detail.$models[$i].module_dir");

            if ($module_dir) {
                $paths = array(
                    'modules/'. $module_dir . '/vardefs.php',
                    'custom/modules/'. $module_dir . '/Ext/Vardefs/vardefs.ext.php',
                );
                foreach($paths as $p) {
                    if(file_exists($p))
                        include($p);
                }
            }
        }
    }

    $bean_dictionary = $dictionary;
    unset($dictionary);
    require_once('modules/TableDictionary.php');
    $table_dictionary = $dictionary;

    $odd_indices = array();
    fillOddIndexes($bean_dictionary, $new_version_indices, $odd_indices);
    fillOddIndexes($table_dictionary, $new_version_indices, $odd_indices);

    return $odd_indices;
}

/**
 * Fill odd indexes array
 *
 * @param array $dictionary
 * @param array $new_indices
 * @param array $odd_indices
 */
function fillOddIndexes($dictionary, $new_indices, &$odd_indices) {
    foreach ($dictionary as $model => $details) {
        if (isset($details['indices'])) {
            for ($i = 0; $i < sizeof($details['indices']); $i++) {
                if ($details['indices'][$i]['type'] != 'primary' && ! @in_array($details['indices'][$i]['name'], $new_indices[$details['table']])) {
                    $odd_indices[$details['table']][] = $details['indices'][$i]['name'];
                }
            }
        }
    }
}

/**
 * Remove tables indexes
 *
 * @param array $indexes: array('table_name' => 'indexes')
 */
function removeIndexes($indexes) {
    global $db;

    foreach ($indexes as $table => $list) {
        for ($i = 0; $i < sizeof($list); $i++) {
            $show_query = "SHOW INDEX FROM `" .$table. "` WHERE Key_name='" .$list[$i]. "'";
            $r = $db->query($show_query, false);
            if ($r && $db->getRowCount($r)) {
                $drop_query = "ALTER TABLE `" .$table. "` DROP INDEX " .$list[$i];
                $db->query($drop_query, false);
            }
        }
    }
}


function fillCaseAccounts() {
	global $db;
	$q = "SELECT id case_id, account_id FROM cases WHERE (SELECT COUNT(*) FROM accounts_cases ac WHERE ac.case_id=cases.id AND NOT ac.deleted) = 0 AND account_id";
	$r = $db->query($q, false);
	while( ($row = $db->fetchByAssoc($r, -1, false)) ) {
		$upd = RowUpdate::blank_for_model('accounts_cases');
		$upd->set($row);
		$upd->putRow();
	}
}


?>
