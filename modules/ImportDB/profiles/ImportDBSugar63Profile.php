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
require_once 'modules/ImportDB/profiles/ImportDBSugar62Profile.php';

class ImportDBSugar63Profile extends ImportDBSugar62Profile {
	public function getTitle() {
		return 'SugarCRM 6.3.x/6.4.x';
	}
	
	protected function getModulesList()
	{
		$list = parent::getModulesList();
		
		unset($list['Accounts']['columns']['account_name']);

		unset($list['Cases']['columns']['assigned_user_name']);

		unset($list['Tasks']['columns']['date_due_flag']);
		unset($list['Tasks']['columns']['date_start_flag']);
		
		unset($list['Notes']['columns']['file_mime_type']);
		unset($list['Notes']['columns']['embed_flag']);
		unset($list['Notes']['columns']['first_name']);
		unset($list['Notes']['columns']['last_name']);

		return $list;
	}

	protected function getHeaderIdx($csv_file_handle, $module = null)
	{
		static $moduleHeaders = array(
			'Users' => array(
				0 => 'first_name',
				1 => 'last_name',
				2 => 'id',
				3 => 'title',
				4 => 'department',
				5 => 'phone_mobile',
				6 => 'phone_work',
				7 => 'phone_home',
				8 => 'phone_other',
				9 => 'phone_fax',
				10 => 'description',
				11 => 'reports_to_id',
				12 => 'date_entered',
				13 => 'date_modified',
				14 => 'modified_user_id',
				15 => 'created_by',
				16 => 'user_name',
				17 => 'is_admin',
				18 => 'address_street',
				19 => 'address_city',
				20 => 'address_state',
				21 => 'address_postalcode',
				22 => 'address_country',
				23 => 'portal_only',
				24 => 'status',
				25 => 'receive_notifications',
				26 => 'employee_status',
				27 => 'messenger_id',
				28 => 'messenger_type',
				29 => 'is_group',
			),
			'Accounts' => array(
				0 => 'name',
				1 => 'id',
				2 => 'website',
				3 => 'email_address',
				4 => 'phone_office',
				5 => 'phone_alternate',
				6 => 'phone_fax',
				7 => 'billing_address_street',
				8 => 'billing_address_city',
				9 => 'billing_address_state',
				10 => 'billing_address_postalcode',
				11 => 'billing_address_country',
				12 => 'shipping_address_street',
				13 => 'shipping_address_city',
				14 => 'shipping_address_state',
				15 => 'shipping_address_postalcode',
				16 => 'shipping_address_country',
				17 => 'description',
				18 => 'account_type',
				19 => 'industry',
				20 => 'annual_revenue',
				21 => 'employees',
				22 => 'sic_code',
				23 => 'ticker_symbol',
				24 => 'parent_id',
				25 => 'ownership',
				26 => 'campaign_id',
				27 => 'rating',
				28 => 'assigned_user_name',
				29 => 'assigned_user_id',
				30 => 'date_entered',
				31 => 'date_modified',
				32 => 'modified_user_id',
				33 => 'created_by',
				34 => 'deleted',
			),
			'Leads' => array(
				0 => 'first_name',
				1 => 'last_name',
				2 => 'id',
				3 => 'salutation',
				4 => 'title',
				5 => 'department',
				6 => 'account_name',
				7 => 'account_description',
				8 => 'website',
				9 => 'email_address',
				10 => 'phone_mobile',
				11 => 'phone_work',
				12 => 'phone_home',
				13 => 'phone_other',
				14 => 'phone_fax',
				15 => 'primary_address_street',
				16 => 'primary_address_city',
				17 => 'primary_address_state',
				18 => 'primary_address_postalcode',
				19 => 'primary_address_country',
				20 => 'alt_address_street',
				21 => 'alt_address_city',
				22 => 'alt_address_state',
				23 => 'alt_address_postalcode',
				24 => 'alt_address_country',
				25 => 'status',
				26 => 'status_description',
				27 => 'lead_source',
				28 => 'lead_source_description',
				29 => 'description',
				30 => 'converted',
				31 => 'opportunity_name',
				32 => 'opportunity_amount',
				33 => 'refered_by',
				34 => 'campaign_id',
				35 => 'do_not_call',
				36 => 'portal_name',
				37 => 'portal_app',
				38 => 'reports_to_id',
				39 => 'assistant',
				40 => 'assistant_phone',
				41 => 'birthdate',
				42 => 'contact_id',
				43 => 'account_id',
				44 => 'opportunity_id',
				45 => 'assigned_user_name',
				46 => 'assigned_user_id',
				47 => 'date_entered',
				48 => 'date_modified',
				49 => 'created_by',
				50 => 'modified_user_id',
				51 => 'deleted',

			),
			'Contacts' => array(
				0 => 'first_name',
				1 => 'last_name',
				2 => 'id',
				3 => 'salutation',
				4 => 'title',
				5 => 'department',
				6 => 'account_name',
				7 => 'email_address',
				8 => 'phone_mobile',
				9 => 'phone_work',
				10 => 'phone_home',
				11 => 'phone_other',
				12 => 'phone_fax',
				13 => 'primary_address_street',
				14 => 'primary_address_city',
				15 => 'primary_address_state',
				16 => 'primary_address_postalcode',
				17 => 'primary_address_country',
				18 => 'alt_address_street',
				19 => 'alt_address_city',
				20 => 'alt_address_state',
				21 => 'alt_address_postalcode',
				22 => 'alt_address_country',
				23 => 'description',
				24 => 'birthdate',
				25 => 'lead_source',
				26 => 'campaign_id',
				27 => 'do_not_call',
				28 => 'reports_to_id',
				29 => 'assistant',
				30 => 'assistant_phone',
				31 => 'assigned_user_name',
				32 => 'assigned_user_id',
				33 => 'date_entered',
				34 => 'date_modified',
				35 => 'modified_user_id',
				36 => 'created_by',
				37 => 'deleted',
			),
			'Opportunities' => array(
				0 => 'name',
				1 => 'id',
				2 => 'amount',
				3 => 'currency_id',
				4 => 'date_closed',
				5 => 'sales_stage',
				6 => 'probability',
				7 => 'next_step',
				8 => 'opportunity_type',
				9 => 'account_name',
				10 => 'description',
				11 => 'amount_usdollar',
				12 => 'lead_source',
				13 => 'campaign_id',
				14 => 'assigned_user_name',
				15 => 'assigned_user_id',
				16 => 'date_entered',
				17 => 'date_modified',
				18 => 'created_by',
				19 => 'modified_user_id',
				20 => 'deleted',
			),
			'Cases' => array(
				0 => 'case_number',
				1 => 'id',
				2 => 'name',
				3 => 'description',
				4 => 'status',
				5 => 'type',
				6 => 'priority',
				7 => 'resolution',
				8 => 'work_log',
				9 => 'account_name',
				10 => 'account_id',
				11 => 'assigned_user_id',
				12 => 'date_entered',
				13 => 'date_modified',
				14 => 'created_by',
				15 => 'modified_user_id',
				16 => 'deleted',
			),
			'Notes' => array(
				0 => 'name',
				1 => 'id',
				2 => 'description',
				3 => 'filename',
				4 => 'parent_type',
				5 => 'parent_id',
				6 => 'contact_id',
				7 => 'portal_flag',
				8 => 'assigned_user_name',
				9 => 'assigned_user_id',
				10 => 'date_entered',
				11 => 'date_modified',
				12 => 'created_by',
				13 => 'modified_user_id',
				14 => 'deleted',
			),
			'Calls' => array(
				0 => 'name',
				1 => 'id',
				2 => 'description',
				3 => 'status',
				4 => 'direction',
				5 => 'date_start',
				6 => 'date_end',
				7 => 'duration_hours',
				8 => 'duration_minutes',
				9 => 'reminder_time',
				10 => 'parent_type',
				11 => 'parent_id',
				12 => 'outlook_id',
				13 => 'assigned_user_name',
				14 => 'assigned_user_id',
				15 => 'date_entered',
				16 => 'date_modified',
				17 => 'created_by',
				18 => 'modified_user_id',
				19 => 'deleted',
			),
			'Meetings' => array(
				0 => 'name',
				1 => 'id',
				2 => 'description',
				3 => 'status',
				4 => 'location',
				5 => 'date_start',
				6 => 'date_end',
				7 => 'duration_hours',
				8 => 'duration_minutes',
				9 => 'reminder_time',
				10 => 'type',
				11 => 'external_id',
				12 => 'password',
				13 => 'join_url',
				14 => 'host_url',
				15 => 'displayed_url',
				16 => 'creator',
				17 => 'parent_type',
				18 => 'parent_id',
				19 => 'outlook_id',
				20 => 'assigned_user_name',
				21 => 'assigned_user_id',
				22 => 'date_entered',
				23 => 'date_modified',
				24 => 'created_by',
				25 => 'modified_user_id',
				26 => 'deleted',
				27 => 'sequence',
			),
			'Tasks' => array(
				0 => 'name',
				1 => 'id',
				2 => 'description',
				3 => 'status',
				4 => 'date_start',
				5 => 'date_due',
				6 => 'priority',
				7 => 'parent_type',
				8 => 'parent_id',
				9 => 'contact_id',
				10 => 'assigned_user_name',
				11 => 'assigned_user_id',
				12 => 'date_entered',
				13 => 'date_modified',
				14 => 'created_by',
				15 => 'modified_user_id',
				16 => 'deleted',
			),

			'Prospects' => array(
				'first_name',
				'last_name',
				'id',
				'salutation',
				'title',
				'department',
				'account_name',
				'email1',
				'phone_mobile',
				'phone_work',
				'phone_home',
				'phone_other',
				'phone_fax',
				'primary_address_street',
				'primary_address_city',
				'primary_address_state',
				'primary_address_postalcode',
				'primary_address_country',
				'alt_address_street',
				'alt_address_city',
				'alt_address_state',
				'alt_address_postalcode',
				'alt_address_country',
				'description',
				'birthdate',
				'assistant',
				'assistant_phone',
				'campaign_id',
				'tracker_key',
				'do_not_call',
				'lead_id',
				'assigned_user_name',
				'assigned_user_id',
				'date_entered',
				'date_modified',
				'modified_user_id',
				'created_by',
				'deleted',
			),
		);

		$header = $this->csvLine($csv_file_handle);
		if ($module && isset($moduleHeaders[$module]))
			$header = $moduleHeaders[$module];
		return array_flip($header);

	}
}
