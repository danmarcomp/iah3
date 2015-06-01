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
class ImportDBSugar60Profile extends ImportDBSugarGenericProfile {
	public function getTitle() {
		return 'SugarCRM 6.0.x/6.1.x';
	}

	protected function getModulesList() {
		$user_bean = $this->std_bean;
		unset($user_bean['id']);
		unset($user_bean['deleted']);

		return array(
			'Users' => array(
				'title' => $this->app_strings['LBL_USERS'],
				'module' => 'Users',
				'title_field' => 'user_name',
				'columns' => $user_bean + array(
					'id' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'userId',
						),
					),
					'user_name' => array(),
					'first_name' => array(),
					'last_name' => array(),
					'title' => array(),
					'department' => array(),
					'is_admin' => array(),
					'phone_home' => array(),
					'phone_mobile' => array(),
					'phone_work' => array(),
					'phone_other' => array(),
					'phone_fax' => array(),
					'address_street' => array(),
					'address_city' => array(),
					'address_state' => array(),
					'address_postalcode' => array(),
					'address_country' => array(),
					'reports_to_id' => array('action' => 'skip'),
					'portal_only' => array(),
					'status' => array(),
					'receive_notifications' => array(),
					'employee_status' => array(),
					'messenger_id' => array(),
					'messenger_type' => array(),
					'is_group' => array(),
				),
				'bean_extra_fields' => array(
					'user_hash' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'userHash',
						),
					),
				),
			),

			'Accounts' => array(
				'title' => $this->app_strings['LBL_ACCOUNTS'],
				'module' => 'Accounts',
				'title_field' => 'name',
				'columns' => $this->std_bean + array(
					'name' => array(),
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'account_type' => array(),
					'industry' => array(),
					'annual_revenue' => array(),
					'phone_fax' => array(),
					'billing_address_street' => array(),
					'billing_address_city' => array(),
					'billing_address_state' => array(),
					'billing_address_postalcode' => array(),
					'billing_address_country' => array(),
					'rating' => array(),
					'phone_office' => array(),
					'phone_alternate' => array(),
					'website' => array(),
					'ownership' => array(),
					'employees' => array(),
					'ticker_symbol' => array(),
					'shipping_address_street' => array(),
					'shipping_address_city' => array(),
					'shipping_address_state' => array(),
					'shipping_address_postalcode' => array(),
					'shipping_address_country' => array(),
					'parent_id' => array('action' => 'skip'),
					'sic_code' => array(),
					'campaign_id' => array('action' => 'skip'),
					'email1' => array(),
					'account_name' => array('action' => 'skip'),
					'assigned_user_name' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'name',
							'id_field' => 'assigned_user_id',
						),
					),
				),
				'bean_extra_fields' => array(
					'is_supplier' => array(
						'action' => 'copy_exists',
						'params' => array(
							'source' => 'is_supplier',
							'default' => 0,
						),
					),
				),
			),

			'Opportunities' => array(
				'title' => $this->app_strings['LBL_OPPORTUNITIES'],
				'module' => 'Opportunities',
				'title_field' => 'name',
				'columns' => $this->std_bean + array(
					'name' => array(),
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'opportunity_type' => array(),
					'campaign_id' => array('action' => 'skip'),
					'lead_source' => array(),
					'amount' => array(),
					'amount_usdollar' => array(),
					'currency_id' => array(),
					'date_closed' => array('action' => 'copy_date', 'format' => 'date'),
					'next_step' => array(),
					'sales_stage' => array(),
					'probability' => array(),
					'account_name' => array('action' => 'skip'),
					'assigned_user_name' => array('action' => 'skip'),
				),
				'bean_extra_fields' => array(
					'account_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'AccountsNameMap',
							'bean' => 'Account',
							'field' => 'id',
							'id_field' => 'account_name',
							'id_modifier' => 'md5',
						),
					),
				),
				'postsave' => array('opportunityAccName'),
			),

			'Contacts' => array(
				'title' => $this->app_strings['LBL_CONTACTS'],
				'module' => 'Contacts',
				'title_field' => array('first_name', 'last_name'),
				'columns' => $this->std_bean + array(
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'salutation' => array(),
					'first_name' => array(),
					'last_name' => array(),
					'title' => array(),
					'department' => array(),
					'do_not_call' => array(),
					'phone_home' => array(),
					'phone_mobile' => array(),
					'phone_work' => array(),
					'phone_other' => array(),
					'phone_fax' => array(),
					'primary_address_street' => array(),
					'primary_address_city' => array(),
					'primary_address_state' => array(),
					'primary_address_postalcode' => array(),
					'primary_address_country' => array(),
					'alt_address_street' => array(),
					'alt_address_city' => array(),
					'alt_address_state' => array(),
					'alt_address_postalcode' => array(),
					'alt_address_country' => array(),
					'assistant' => array(),
					'assistant_phone' => array(),
					'lead_source' => array(),
					'reports_to_id' => array('action' => 'skip'),
					'birthdate' => array('action' => 'copy_date', 'format' => 'date'),
					'campaign_id' => array('action' => 'skip'),
					'email1' => array(),
					'account_name' => array('action' => 'skip'),
					'assigned_user_name' => array('action' => 'skip'),
				),
				'bean_extra_fields' => array(
					'primary_account_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'AccountsNameMap',
							'bean' => 'Account',
							'field' => 'id',
							'id_field' => 'account_name',
							'id_modifier' => 'md5',
						),
					),
				),
				'postsave' => array('contactAccName', 'addToProspectsList'),
			),

			'Leads' => array(
				'title' => $this->app_strings['LBL_LEADS'],
				'module' => 'Leads',
				'title_field' => array('first_name', 'last_name'),
				'columns' => $this->std_bean + array(
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'salutation' => array(),
					'first_name' => array(),
					'last_name' => array(),
					'title' => array(),
					'department' => array(),
					'do_not_call' => array(),
					'phone_home' => array(),
					'phone_mobile' => array(),
					'phone_work' => array(),
					'phone_other' => array(),
					'phone_fax' => array(),
					'primary_address_street' => array(),
					'primary_address_city' => array(),
					'primary_address_state' => array(),
					'primary_address_postalcode' => array(),
					'primary_address_country' => array(),
					'alt_address_street' => array(),
					'alt_address_city' => array(),
					'alt_address_state' => array(),
					'alt_address_postalcode' => array(),
					'alt_address_country' => array(),
					'assistant' => array('action' => 'skip'),
					'assistant_phone' => array('action' => 'skip'),
					'converted' => array(),
					'refered_by' => array(),
					'lead_source' => array(),
					'lead_source_description' => array(),
					'status' => array(),
					'status_description' => array(),
					'reports_to_id' => array('action' => 'skip'),
					'account_name' => array(),
					'account_description' => array(),
					'contact_id' => array('action' => 'skip'),
					'account_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Accounts',
							'field' => 'id',
							'id_field' => 'account_id',
						),
					),
					'opportunity_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Opportunities',
							'field' => 'id',
							'id_field' => 'opportunity_id',
						),
					),
					'opportunity_name' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Opportunities',
							'field' => 'name',
							'id_field' => 'opportunity_id',
						),
					),
					'opportunity_amount' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Opportunities',
							'field' => 'amount',
							'id_field' => 'opportunity_id',
						),
					),
					'campaign_id' => array('action' => 'skip'),
					'birthdate' => array('action' => 'skip'),
					'portal_name' => array(),
					'portal_app' => array(),
					'website' => array(),
					'email1' => array(),
					'assigned_user_name' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'name',
							'id_field' => 'assigned_user_id',
						),
					),
				),
				'postsave' => array('addToProspectsList'),
			),

			'Cases' => array(
				'title' => $this->app_strings['LBL_CASES'],
				'module' => 'Cases',
				'title_field' => 'name',
				'columns' => $this->std_bean + array(
					'name' => array(),
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'case_number' => array(),
					'type' => array(),
					'status' => array(),
					'priority' => array(),
					'resolution' => array(),
					'work_log' => array('action' => 'skip'),
					'account_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Accounts',
							'field' => 'id',
							'id_field' => 'account_id',
						),
					),
					'account_name' => array('action' => 'skip'),
					'assigned_user_name' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'name',
							'id_field' => 'assigned_user_id',
						),
					),
				),
			),

			'Calls' => array(
				'title' => $this->app_strings['LBL_CALLS'],
				'module' => 'Calls',
				'title_field' => 'name',
				'columns' => $this->std_bean + array(
					'name' => array(
						'action' => 'default_on_empty',
						'params' => array(
							'default' => $this->mod_strings['LBL_CALL'],
						),
					),
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'duration_hours' => array(),
					'duration_minutes' => array(),
					'date_start' => array('action' => 'copy_date', 'format' => 'date'),
					'date_end' => array('action' => 'copy_date', 'format' => 'date'),
					'parent_type' => array(),
					'status' => array(),
					'direction' => array(),
					'parent_id' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'calendarParentId',
						),
					),
					'reminder_time' => array(),
					'outlook_id' => array('action' => 'skip'),
				),
			),

			'Meetings' => array(
				'title' => $this->app_strings['LBL_MEETINGS'],
				'module' => 'Meetings',
				'title_field' => 'name',
				'columns' => $this->std_bean + array(
					'name' => array(),
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'location' => array(),
					'duration_hours' => array(),
					'duration_minutes' => array(),
					'date_start' => array('action' => 'copy_date', 'format' => 'date'),
					'date_end' => array('action' => 'copy_date', 'format' => 'date'),
					'parent_type' => array(),
					'status' => array(),
					'parent_id' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'calendarParentId',
						),
					),
					'reminder_time' => array(),
					'outlook_id' => array('action' => 'skip'),
				),
			),

			'Tasks' => array(
				'title' => $this->app_strings['LBL_TASKS'],
				'module' => 'Tasks',
				'title_field' => 'name',
				'columns' => $this->std_bean + array(
					'name' => array(),
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'status' => array(),
					'date_due_flag' => array(),
					'date_due' => array('action' => 'copy_date', 'format' => 'date'),
					'date_start_flag' => array(),
					'date_start' => array('action' => 'copy_date', 'format' => 'date'),
					'parent_type' => array(),
					'parent_id' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'calendarParentId',
						),
					),
					'contact_id' => array('action' => 'get_from_imported',
						'params' => array(
							'module' => 'Contacts',
							'field' => 'id',
							'id_field' => 'contact_id',
						),
					),
					'priority' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'tasksPriority',
						),
					),
				),
			),

			'Prospects' => array(
				'title' => $this->app_strings['LBL_PROSPECTS'],
				'module' => 'Prospects',
				'title_field' => array('first_name', 'last_name'),
				'columns' => $this->std_bean + array(
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'salutation' => array(),
					'first_name' => array(),
					'last_name' => array(),
					'title' => array(),
					'department' => array(),
					'do_not_call' => array(),
					'phone_home' => array(),
					'phone_mobile' => array(),
					'phone_work' => array(),
					'phone_other' => array(),
					'phone_fax' => array(),
					'primary_address_street' => array(),
					'primary_address_city' => array(),
					'primary_address_state' => array(),
					'primary_address_postalcode' => array(),
					'primary_address_country' => array(),
					'alt_address_street' => array(),
					'alt_address_city' => array(),
					'alt_address_state' => array(),
					'alt_address_postalcode' => array(),
					'alt_address_country' => array(),
					'assistant' => array(),
					'assistant_phone' => array(),
					'tracker_key' => array(),
					'birthdate' => array(),
					'lead_id' => array('action' => 'skip'),
					'account_name' => array(),
					'campaign_id' => array(),
					'email1' => array(),
					'assigned_user_name' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'name',
							'id_field' => 'assigned_user_id',
						),
					),
				),
				'bean_extra_fields' => array(
				),
				'postsave' => array('addToProspectsList'),
			),

			'Notes' => array(
				'title' => $this->app_strings['LBL_NOTES'],
				'module' => 'Notes',
				'title_field' => 'name',
				'columns' => $this->std_bean + array(
					'name' => array(),
					'assigned_user_id' => array(
						'action' => 'get_from_imported',
						'params' => array(
							'module' => 'Users',
							'field' => 'id',
							'id_field' => 'assigned_user_id',
						),
					),
					'filename' => array(),
					'file_mime_type' => array(),
					'parent_type' => array(),
					'parent_id' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'calendarParentId',
						),
					),
					'contact_id' => array('action' => 'get_from_imported',
						'params' => array(
							'module' => 'Contacts',
							'field' => 'id',
							'id_field' => 'contact_id',
						),
					),
					'portal_flag' => array(),
					'embed_flag' => array('action' => 'skip'),
					'first_name' => array('action' => 'skip'),
					'last_name' => array('action' => 'skip'),
				),
				'bean_extra_fields' => array(
					'assigned_user_id' => array(
						'action' => 'custom',
						'params' => array(
							'method' => 'noteAssignedTo',
						),
					),
				),
			),
		);
	}
}
