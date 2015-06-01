<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Leads/Lead.php
	audit_enabled: true
	activity_log_enabled: true
	unified_search: true
	duplicate_merge: true
	optimistic_locking: true
	comment: Leads are persons of interest early in a sales cycle
	table_name: leads
	primary_key: id
	default_order_by: date_entered DESC
	importable: LBL_LEADS
	reportable: true
hooks
	new_record
		--
			class_function: init_record
		--
			class_function: init_from_email
	notify
		--
			class_function: send_notification
	after_save
		--
			class_function: set_related
		--
			class_function: update_prospect
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
	converted
		vname: LBL_CONVERTED
		type: bool
		required: true
		default: 0
		comment:
			Has Lead been converted to a Contact (and other Sugar
			objects)
		massupdate: true
	categories
		vname: LBL_CATEGORY
		vname_list: LBL_LIST_CATEGORY
		type: multienum
		options: contact_category_dom
		len: 40
		multi_select_group: category
		multi_select_count: 10
		massupdate: true
	temperature
		vname: LBL_TEMPERATURE
		type: enum
		options: temperature_dom
		len: 40
		massupdate: true
	salutation
		vname: LBL_SALUTATION
		type: enum
		options: salutation_dom
		massupdate: false
		len: 20
		comment: Salutation (ex: Mr, Mrs, Ms)
	first_name
		vname: LBL_FIRST_NAME
		type: varchar
		len: 25
		unified_search: true
		comment: First name of Lead
		merge_filter: selected
	last_name
		vname: LBL_LAST_NAME
		type: name
		len: 25
		unified_search: true
		comment: Last name of Lead
		merge_filter: selected
		required: true
	title
		vname: LBL_TITLE
		type: varchar
		len: 100
		comment: Lead title (ex: Director))
	refered_by
		vname: LBL_REFERED_BY
		type: varchar
		len: 100
		comment: Identifies who refered the lead
		merge_filter: enabled
	lead_source
		vname: LBL_LEAD_SOURCE
		type: enum
		options: lead_source_dom
		len: 100
		audited: true
		comment: Lead source (ex: Web, print)
		merge_filter: enabled
		massupdate: true
	lead_source_description
		vname: LBL_LEAD_SOURCE_DESCRIPTION
		type: text
		width: 30
		height: 3
		comment: Description of the lead source
	status
		vname: LBL_STATUS
		type: enum
		len: 100
		options: lead_status_dom
		audited: true
		comment: Status of the lead
		merge_filter: enabled
		vname_list: LBL_LIST_STATUS
		required: true
		default: New
		massupdate: true
	status_description
		vname: LBL_STATUS_DESCRIPTION
		type: text
		width: 30
		height: 3
		comment: Description of the status of the lead
	department
		vname: LBL_DEPARTMENT
		type: varchar
		len: 100
		comment: Department the lead belongs to
		merge_filter: enabled
	reports_to
		vname: LBL_REPORTS_TO
		type: ref
		bean_name: Contact
		massupdate: true
	do_not_call
		vname: LBL_DO_NOT_CALL
		type: bool
		dbType: varchar
		len: 3
		default: 0
		audited: true
		comment: Do Not Call indicator
		massupdate: true
	phone_home
		vname: LBL_HOME_PHONE
		type: phone
		unified_search: true
		comment: Home phone number
		merge_filter: enabled
	phone_mobile
		vname: LBL_MOBILE_PHONE
		type: phone
		unified_search: true
		comment: Mobile or cell phone number
		merge_filter: enabled
	phone_work
		vname: LBL_OFFICE_PHONE
		type: phone
		audited: true
		unified_search: true
		comment: Office phone number
		merge_filter: enabled
		vname_list: LBL_LIST_PHONE
	phone_other
		vname: LBL_OTHER_PHONE
		type: phone
		unified_search: true
		comment: Other phone number
		merge_filter: enabled
	phone_fax
		vname: LBL_FAX_PHONE
		type: phone
		unified_search: true
		comment: Fax phone number
		merge_filter: enabled
	email1
		vname: LBL_EMAIL_ADDRESS
		type: email
		audited: true
		unified_search: true
		comment: Main email address of lead
		merge_filter: enabled
		vname_list: LBL_LIST_EMAIL_ADDRESS
	email2
		vname: LBL_OTHER_EMAIL_ADDRESS
		type: email
		unified_search: true
		comment: Secondary email address of lead
		merge_filter: enabled
		vname_list: LBL_LIST_EMAIL_ADDRESS
	email_opt_out
		vname: LBL_EMAIL_OPT_OUT
		type: bool
		dbType: varchar
		len: 3
		default: 0
		audited: true
		comment:
			Indicator signaling if lead elects to opt out of email
			campaigns
		massupdate: true
	website
		vname: LBL_WEBSITE
		type: url
		len: 255
		comment: URL of website for the lead
		massupdate: true
	primary_address_street
		vname: LBL_PRIMARY_ADDRESS_STREET
		type: varchar
		len: 150
		comment: Primary street address
		merge_filter: enabled
	primary_address_city
		vname: LBL_PRIMARY_ADDRESS_CITY
		vname_list: LBL_CITY
		type: varchar
		len: 100
		comment: Primary address city
		merge_filter: enabled
	primary_address_state
		vname: LBL_PRIMARY_ADDRESS_STATE
		vname_list: LBL_STATE
		type: varchar
		len: 100
		comment: Primary address state
		merge_filter: enabled
	primary_address_postalcode
		vname: LBL_PRIMARY_ADDRESS_POSTALCODE
		type: varchar
		len: 20
		comment: Primary address postal code
		merge_filter: enabled
	primary_address_country
		vname: LBL_PRIMARY_ADDRESS_COUNTRY
		type: varchar
		len: 100
		comment: Primary address country
		merge_filter: enabled
	alt_address_street
		vname: LBL_ALT_ADDRESS_STREET
		type: varchar
		len: 150
		comment: Alternate street address
		merge_filter: enabled
	alt_address_city
		vname: LBL_ALT_ADDRESS_CITY
		type: varchar
		len: 100
		comment: Alternate address city
		merge_filter: enabled
	alt_address_state
		vname: LBL_ALT_ADDRESS_STATE
		type: varchar
		len: 100
		comment: Alternate address state
		merge_filter: enabled
	alt_address_postalcode
		vname: LBL_ALT_ADDRESS_POSTALCODE
		type: varchar
		len: 20
		comment: Alternate address postal code
		merge_filter: enabled
	alt_address_country
		vname: LBL_ALT_ADDRESS_COUNTRY
		type: varchar
		len: 100
		comment: Alternate address country
		merge_filter: enabled
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Lead description
	account_name
		vname: LBL_ACCOUNT_NAME
		type: varchar
		len: 150
		unified_search: true
		comment: Account name for lead
		vname_list: LBL_LIST_ACCOUNT_NAME
	account_description
		vname: LBL_ACCOUNT_DESCRIPTION
		type: text
		comment: Description of lead account
	contact
		type: ref
		vname: LBL_CONVERTED_CONTACT
		comment: If converted, Contact resulting from the conversion
		bean_name: Contact
		massupdate: true
	account
		type: ref
		vname: LBL_CONVERTED_ACCOUNT
		comment: If converted, Account resulting from the conversion
		bean_name: Account
		massupdate: true
	opportunity
		type: ref
		vname: LBL_CONVERTED_OPP
		comment: If converted, Opportunity resulting from the conversion
		bean_name: Opportunity
		massupdate: true
	opportunity_name
		vname: LBL_OPPORTUNITY_NAME
		type: varchar
		len: 255
		comment: Opportunity name associated with lead
	opportunity_amount
		vname: LBL_OPPORTUNITY_AMOUNT
		type: varchar
		len: 50
		comment: Amount of the opportunity
	campaign
		type: ref
		vname: LBL_CAMPAIGN
		comment: Campaign that generated lead
		bean_name: Campaign
		massupdate: true
	portal_active
		vname: LBL_PORTAL_ACTIVE
		type: bool
		default: 0
		comment: Indicator whether this lead is a portal user
		massupdate: true
	portal_name
		vname: LBL_PORTAL_NAME
		type: varchar
		len: 255
		comment: Portal user name when lead created via lead portal
	portal_app
		vname: LBL_PORTAL_APP
		type: varchar
		len: 255
		comment: Portal application that resulted in created of lead
	invalid_email
		vname: LBL_INVALID_EMAIL
		type: bool
		comment: Indicator that email address for lead is invalid
		massupdate: true
	partner
		vname: LBL_PARTNER
		type: ref
		bean_name: Partner
		massupdate: true
	date_converted
		vname: LBL_DATE_CONVERTED
		type: datetime
		massupdate: true
	last_activity_date
		vname: LBL_LAST_ACTIVITY
		type: datetime
		massupdate: true
links
	activities
		relationship: leads_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: leads_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	eventsessions
		relationship: events_leads
		vname: LBL_EVENTS
		bean_name: EventSession
	documents
		relationship: documents_leads
		module: Documents
		bean_name: Document
		vname: LBL_DOCUMENTS
	reports_to_link
		relationship: lead_direct_reports
		link_type: one
		side: right
		vname: LBL_REPORTS_TO
	partners
		relationship: leads_partners
		vname: LBL_PARTNERS
	converted_account
		relationship: lead_converted_account
		vname: LBL_CONVERTED_ACCOUNT
	converted_contact
		relationship: lead_converted_contact
		vname: LBL_CONVERTED_CONTACT
	converted_opportunity
		relationship: lead_converted_opp
		vname: LBL_CONVERTED_OPP
	tasks
		relationship: lead_tasks
		vname: LBL_TASKS
		bean_name: Task
	notes
		relationship: lead_notes
		vname: LBL_NOTES
		bean_name: Note
	meetings
		relationship: lead_meetings
		vname: LBL_MEETINGS
		bean_name: Meeting
	calls
		relationship: lead_calls
		vname: LBL_CALLS
		bean_name: Call
	emails
		relationship: emails_leads_rel
		side: right
		vname: LBL_EMAILS
		bean_name: Email
	campaign_leads
		relationship: lead_campaign
		module: Campaigns
		bean_name: Campaign
		vname: LBL_CAMPAIGNS
	campaigns
		relationship: lead_campaign_log
		module: CampaignLog
		bean_name: CampaignLog
		vname: LBL_CAMPAIGNS
		no_create: true
	prospect_lists
		relationship: prospect_list_leads
		bean_name: ProspectList
		vname: LBL_PROSPECT_LIST
	app.securitygroups
		relationship: securitygroups_leads
indices
	idx_lead_last_first
		fields
			- last_name
			- first_name
			- deleted
	idx_lead_del_stat
		fields
			- last_name
			- status
			- deleted
			- first_name
	idx_lead_opp_del
		fields
			- opportunity_id
			- deleted
	idx_leads_acct_del
		fields
			- account_id
			- deleted
	idx_lead_email1
		fields
			- email1
			- deleted
	idx_lead_email2
		fields
			- email2
			- deleted
	idx_lead_assigned
		fields
			- assigned_user_id
	idx_lead_contact
		fields
			- contact_id
relationships
	lead_direct_reports
		key: id
		target_bean: Lead
		target_key: reports_to_id
		relationship_type: one-to-many
	lead_tasks
		key: id
		target_bean: Task
		target_key: parent_id
		role_column: parent_type
		role_value: Leads
		relationship_type: one-to-many
	lead_notes
		key: id
		target_bean: Note
		target_key: parent_id
		role_column: parent_type
		role_value: Leads
		relationship_type: one-to-many
	lead_meetings
		key: id
		target_bean: Meeting
		target_key: parent_id
		role_column: parent_type
		role_value: Leads
		relationship_type: one-to-many
	lead_calls
		key: id
		target_bean: Call
		target_key: parent_id
		role_column: parent_type
		role_value: Leads
		relationship_type: one-to-many
	lead_emails
		key: id
		target_bean: Email
		target_key: parent_id
		role_column: parent_type
		role_value: Leads
		relationship_type: one-to-many
	lead_campaign_log
		key: id
		target_bean: CampaignLog
		target_key: target_id
		relationship_type: one-to-many
	lead_campaign
		key: id
		target_bean: Campaign
		target_key: id
		relationship_type: one-to-many
	leads_partners
		key: partner_id
		target_bean: Partner
		target_key: id
		relationship_type: one-to-many
	lead_converted_account
		key: account_id
		target_bean: Account
		target_key: id
		relationship_type: one-to-many
	lead_converted_contact
		key: contact_id
		target_bean: Contact
		target_key: id
		relationship_type: one-to-many
	lead_converted_opp
		key: opportunity_id
		target_bean: Opportunity
		target_key: id
		relationship_type: one-to-many
	leads_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: lead_meetings
			calls
				relationship: lead_calls
			tasks
				relationship: lead_tasks
	leads_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: lead_meetings
			calls
				relationship: lead_calls
			tasks
				relationship: lead_tasks
			notes
				relationship: lead_notes
			emails
				relationship: emails_leads_rel
