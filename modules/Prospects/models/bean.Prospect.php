<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Prospects/Prospect.php
	unified_search: false
	duplicate_merge: false
	table_name: prospects
	primary_key: id
	reportable: true
	importable: LBL_PROSPECTS
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	name
		vname: LBL_NAME
		type: name
		source
			type: person_name
			fields
				- first_name
				- last_name
				- salutation
		importable: false
	tracker_key
		vname: LBL_TRACKER_KEY
		type: int
		len: 11
		required: true
		auto_increment: true
		importable: false
	salutation
		vname: LBL_SALUTATION
		type: enum
		options: salutation_dom
		massupdate: false
		len: 20
	first_name
		vname: LBL_FIRST_NAME
		type: name
		len: 100
		unified_search: true
	last_name
		vname: LBL_LAST_NAME
		type: name
		len: 100
		unified_search: true
		required: true
	title
		vname: LBL_TITLE
		type: varchar
		len: 25
		vname_list: LBL_LIST_TITLE
	department
		vname: LBL_DEPARTMENT
		type: varchar
		len: 255
	birthdate
		vname: LBL_BIRTHDATE
		massupdate: false
		type: date
	do_not_call
		vname: LBL_DO_NOT_CALL
		type: bool
		dbType: varchar
		len: 3
		default: 0
		massupdate: true
	phone_home
		vname: LBL_HOME_PHONE
		type: phone
		unified_search: true
	phone_mobile
		vname: LBL_MOBILE_PHONE
		type: phone
		unified_search: true
	phone_work
		vname: LBL_OFFICE_PHONE
		type: phone
		unified_search: true
		vname_list: LBL_LIST_PHONE
	phone_other
		vname: LBL_OTHER_PHONE
		type: phone
		unified_search: true
	phone_fax
		vname: LBL_FAX_PHONE
		type: phone
		unified_search: true
	email1
		vname: LBL_EMAIL_ADDRESS
		type: email
		unified_search: true
		vname_list: LBL_LIST_EMAIL_ADDRESS
	email2
		vname: LBL_OTHER_EMAIL_ADDRESS
		type: email
		unified_search: true
	assistant
		vname: LBL_ASSISTANT
		type: varchar
		len: 75
		unified_search: true
	assistant_phone
		vname: LBL_ASSISTANT_PHONE
		type: phone
		unified_search: true
	email_opt_out
		vname: LBL_EMAIL_OPT_OUT
		type: bool
		dbType: varchar
		len: 3
		default: 0
		massupdate: true
	primary_address_street
		vname: LBL_PRIMARY_ADDRESS_STREET
		type: varchar
		len: 150
	primary_address_city
		vname: LBL_PRIMARY_ADDRESS_CITY
		type: varchar
		len: 100
	primary_address_state
		vname: LBL_PRIMARY_ADDRESS_STATE
		type: varchar
		len: 100
	primary_address_postalcode
		vname: LBL_PRIMARY_ADDRESS_POSTALCODE
		type: varchar
		len: 20
	primary_address_country
		vname: LBL_PRIMARY_ADDRESS_COUNTRY
		type: varchar
		len: 100
	alt_address_street
		vname: LBL_ALT_ADDRESS_STREET
		type: varchar
		len: 150
	alt_address_city
		vname: LBL_ALT_ADDRESS_CITY
		type: varchar
		len: 100
	alt_address_state
		vname: LBL_ALT_ADDRESS_STATE
		type: varchar
		len: 100
	alt_address_postalcode
		vname: LBL_ALT_ADDRESS_POSTALCODE
		type: varchar
		len: 20
	alt_address_country
		vname: LBL_ALT_ADDRESS_COUNTRY
		type: varchar
		len: 100
	description
		vname: LBL_DESCRIPTION
		type: text
	invalid_email
		vname: LBL_INVALID_EMAIL
		type: bool
		massupdate: true
	lead
		type: ref
		reportable: false
		vname: LBL_LEAD_ID
		bean_name: Lead
		massupdate: true
	account_name
		vname: LBL_ACCOUNT_NAME
		type: varchar
		len: 150
	last_activity_date
		vname: LBL_LAST_ACTIVITY
		type: datetime
		massupdate: true
	campaign
		comment: Campaign that generated lead
		vname: LBL_CAMPAIGN_ID
		rname: id
		id_name: campaign_id
		type: ref
		table: campaigns
		isnull: true
		module: Campaigns
		reportable: false
		massupdate: false
		duplicate_merge: disabled
		bean_name: Campaign
links
	activities
		relationship: prospects_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: prospects_history
		module: Activities
		bean_name: ActivityHistory
		layout: AccountHistory
		vname: LBL_HISTORY_SUBPANEL_TITLE
	campaigns
		relationship: prospect_campaign_log
		module: CampaignLog
		bean_name: CampaignLog
		vname: LBL_CAMPAIGNS
		no_create: true
	prospect_lists
		relationship: prospect_list_prospects
		module: ProspectLists
		vname: LBL_PROSPECT_LIST
	emails
		relationship: emails_prospects
		vname: LBL_EMAILS
		module: Emails
		bean_name: Email
	meetings
		relationship: meetings_prospects
		module: Meetings
		bean_name: Meeting
		vname: LBL_MEETINGS
	calls
		relationship: calls_prospects
		module: Calls
		bean_name: Call
		vname: LBL_CALLS
	notes
		relationship: notes_prospects
		module: Notes
		bean_name: Note
		vname: LBL_NOTES
	tasks
		relationship: tasks_prospects
		module: Tasks
		bean_name: Task
		vname: LBL_TASKS
	app.securitygroups
		relationship: securitygroups_prospects
fields_compat
	assigned_real_user_name
		vname: LBL_ASSIGNED_TO_NAME
		type: varchar
		source: non-db
	full_name
		rname: full_name
		vname: LBL_NAME
		type: varchar
		source: non-db
		fields
			- first_name
			- last_name
		sort_on: last_name
		len: 510
		db_concat_fields
			- first_name
			- last_name
	name
		rname: name
		vname: LBL_NAME
		type: varchar
		source: non-db
		len: 510
		db_concat_fields
			- first_name
			- last_name
		importable: false
indices
	prospect_auto_tracker_key
		fields
			- tracker_key
	idx_prospects_last_first
		fields
			- last_name
			- first_name
			- deleted
	idx_prospecs_del_last
		fields
			- last_name
			- deleted
relationships
	prospect_campaign_log
		key: id
		target_bean: CampaignLog
		target_key: target_id
		relationship_type: one-to-many
	emails_prospects
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Prospects
		relationship_type: one-to-many
	meetings_prospects
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Prospects
		relationship_type: one-to-many
	calls_prospects
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Prospects
		relationship_type: one-to-many
	notes_prospects
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Prospects
		relationship_type: one-to-many
	tasks_prospects
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Prospects
		relationship_type: one-to-many
	prospects_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: meetings_prospects
			calls
				relationship: calls_prospects
			tasks
				relationship: tasks_prospects
	prospects_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: meetings_prospects
			calls
				relationship: calls_prospects
			tasks
				relationship: tasks_prospects
			notes
				relationship: notes_prospects
			emails
				relationship: emails_prospects
