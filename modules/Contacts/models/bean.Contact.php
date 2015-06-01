<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Contacts/Contact.php
	audit_enabled: true
	activity_log_enabled: true
	unified_search: true
	duplicate_merge: true
	optimistic_locking: true
	table_name: contacts
	primary_key: id
	default_order_by: name
	importable: LBL_CONTACTS
	reportable: true
hooks
	new_record
		--
			class_function: init_record
		--
			class_function: init_from_email
	before_save
		--
			class_function: init_contact_for
	after_save
		--
			class_function: account_B2C_update
		--
			class_function: set_related
	notify
		--
			class_function: send_notification
	after_add_link
		--
			class_function: add_linked_account
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
	primary_account
		vname: LBL_PRIMARY_ACCOUNT
		vname_list: LBL_LIST_ACCOUNT_NAME
		type: ref
		bean_name: Account
		importable: false
		massupdate: false
	b2c_account
		vname: LBL_B2C_ACCOUNT
		type: ref
		bean_name: Account
		id_name: primary_contact_for
		importable: false
		massupdate: false
		reportable: false
	categories
		vname: LBL_CATEGORY
		vname_list: LBL_LIST_CATEGORY
		type: multienum
		options: contact_category_dom
		len: 40
		multi_select_group: category
		multi_select_count: 10
		massupdate: true
	business_role
		vname: LBL_BUSINESS_ROLE
		type: enum
		options: contact_business_role_dom
		len: 40
		massupdate: true
	last_activity_date
		vname: LBL_LAST_ACTIVITY
		type: datetime
		massupdate: true
	salutation
		vname: LBL_SALUTATION
		type: enum
		options: salutation_dom
		massupdate: false
		len: 20
		comment: Contact salutation (e.g., Mr, Ms)
	first_name
		vname: LBL_FIRST_NAME
		type: varchar
		len: 100
		unified_search: true
		comment: First name of the contact
	last_name
		vname: LBL_LAST_NAME
		type: varchar
		len: 100
		unified_search: true
		comment: Last name of the contact
		required: true
	lead_source
		vname: LBL_LEAD_SOURCE
		type: enum
		options: lead_source_dom
		len: 100
		comment: How did the contact come about
		massupdate: true
	title
		vname: LBL_TITLE
		type: varchar
		len: 50
		comment: The title of the contact
		vname_list: LBL_LIST_TITLE
	department
		vname: LBL_DEPARTMENT
		type: varchar
		len: 100
		comment: The department of the contact
	reports_to
		vname: LBL_REPORTS_TO
		type: ref
		reportable: false
		comment: The contact this contact reports to
		bean_name: Contact
		massupdate: true
	birthdate
		vname: LBL_BIRTHDATE
		massupdate: false
		type: date
		comment: The birthdate of the contact
	do_not_call
		vname: LBL_DO_NOT_CALL
		type: bool
		dbType: varchar
		len: 3
		default: 0
		audited: true
		comment: An indicator of whether contact can be called
		massupdate: true
	email_accounts
		vname: LBL_EMAIL_ACCOUNTS
		type: bool
		dbType: varchar
		len: 3
		default: 0
		audited: true
		comment:
			An indicator of whether we should email the account invoice
			to this client
		massupdate: true
	phone_home
		vname: LBL_HOME_PHONE
		type: phone
		unified_search: true
		comment: Home phone number of the contact
	phone_mobile
		vname: LBL_MOBILE_PHONE
		type: phone
		unified_search: true
		comment: Mobile phone number of the contact
	phone_work
		vname: LBL_OFFICE_PHONE
		type: phone
		audited: true
		unified_search: true
		comment: Work phone number of the contact
	phone_other
		vname: LBL_OTHER_PHONE
		type: phone
		unified_search: true
		comment: Other phone number for the contact
	phone_fax
		vname: LBL_FAX_PHONE
		type: phone
		unified_search: true
		comment: Contact fax number
	email1
		vname: LBL_EMAIL_ADDRESS
		type: email
		audited: true
		unified_search: true
		comment: Primary email address of the contact
		vname_list: LBL_LIST_EMAIL_ADDRESS
	email2
		vname: LBL_OTHER_EMAIL_ADDRESS
		type: email
		unified_search: true
		comment: Secondary email address of the contact
		vname_list: LBL_LIST_EMAIL_ADDRESS
	assistant
		vname: LBL_ASSISTANT
		type: varchar
		len: 75
		unified_search: true
		comment: Name of the assistant of the contact
	assistant_phone
		vname: LBL_ASSISTANT_PHONE
		type: phone
		unified_search: true
		comment: Phone number of the assistant of the contact
	email_opt_out
		vname: LBL_EMAIL_OPT_OUT
		type: bool
		dbType: varchar
		len: 3
		default: 0
		comment:
			Indicator whether the contact has elected to opt out of
			emails
		massupdate: true
	primary_address_street
		vname: LBL_PRIMARY_ADDRESS_STREET
		type: varchar
		len: 150
		comment: Street address for primary address
	primary_address_city
		vname: LBL_PRIMARY_ADDRESS_CITY
		vname_list: LBL_LIST_CITY
		type: varchar
		len: 100
		comment: City for primary address
	primary_address_state
		vname: LBL_PRIMARY_ADDRESS_STATE
		vname_list: LBL_LIST_STATE
		type: varchar
		len: 100
		comment: State for primary address
	primary_address_postalcode
		vname: LBL_PRIMARY_ADDRESS_POSTALCODE
		type: varchar
		len: 20
		comment: Postal code for primary address
	primary_address_country
		vname: LBL_PRIMARY_ADDRESS_COUNTRY
		type: varchar
		len: 100
		comment: Country for primary address
	alt_address_street
		vname: LBL_ALT_ADDRESS_STREET
		type: varchar
		len: 150
		comment: Street address for alternate address
	alt_address_city
		vname: LBL_ALT_ADDRESS_CITY
		type: varchar
		len: 100
		comment: City for alternate address
	alt_address_state
		vname: LBL_ALT_ADDRESS_STATE
		type: varchar
		len: 100
		comment: State for alternate address
	alt_address_postalcode
		vname: LBL_ALT_ADDRESS_POSTALCODE
		type: varchar
		len: 20
		comment: Postal code for alternate address
	alt_address_country
		vname: LBL_ALT_ADDRESS_COUNTRY
		type: varchar
		len: 100
		comment: Country for alternate address
	description
		vname: LBL_DESCRIPTION
		type: text
		comment: Description of contact
	portal_name
		vname: LBL_PORTAL_NAME
		type: varchar
		len: 255
		comment: Name as it appears in the portal
	portal_active
		vname: LBL_PORTAL_ACTIVE
		type: bool
		required: true
		default: 0
		comment: Indicator whether this contact is a portal user
		massupdate: true
	portal_app
		vname: LBL_PORTAL_APP
		type: varchar
		len: 255
		comment: Reference to the portal
	invalid_email
		vname: LBL_INVALID_EMAIL
		type: bool
		comment: Indicator that contact email address is invalid
		massupdate: true
	partner
		type: ref
		vname: LBL_PARTNER
		bean_name: Partner
		massupdate: true
	campaign
		comment: Campaign that generated lead
		vname: LBL_CAMPAIGN
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
		relationship: contacts_activities
		module: Activities
		bean_name: Activity
		vname: LBL_ACTIVITIES_SUBPANEL_TITLE
	history
		relationship: contacts_history
		module: Activities
		bean_name: ActivityHistory
		layout: History
		vname: LBL_HISTORY_SUBPANEL_TITLE
	documents
		relationship: documents_contacts
		module: Documents
		bean_name: Document
		vname: LBL_DOCUMENTS
	quotes
		relationship: quotes_contacts_shipto
		ignore_role: "true"
		module: Quotes
		bean_name: Quote
		vname: LBL_QUOTES
	converted_lead
		relationship: lead_converted_contact
		vname: LBL_CONVERTED_LEAD
	eventsessions
		relationship: events_contacts
		vname: LBL_EVENTS
		bean_name: EventSession
	accounts
		relationship: accounts_contacts
		link_type: one
		vname: LBL_ACCOUNTS
		duplicate_merge: disabled
		bean_name: Account
	reports_to_link
		relationship: contact_direct_reports
		link_type: one
		side: right
		vname: LBL_REPORTS_TO
	opportunities
		relationship: opportunities_contacts
		module: Opportunities
		bean_name: Opportunity
		vname: LBL_OPPORTUNITIES
	bugs
		relationship: contacts_bugs
		vname: LBL_BUGS
		bean_name: Bug
	calls
		relationship: calls_contacts
		vname: LBL_CALLS
		bean_name: Call
	cases
		relationship: contacts_cases
		vname: LBL_CASES
		bean_name: aCase
	direct_reports
		relationship: contact_direct_reports
		vname: LBL_DIRECT_REPORTS
		bean_name: Contact
	emails
		relationship: emails_contacts_rel
		vname: LBL_EMAILS
		bean_name: Email
	leads
		relationship: contact_leads
		vname: LBL_LEADS
		bean_name: Lead
	meetings
		relationship: meetings_contacts
		vname: LBL_MEETINGS
		bean_name: Meeting
	notes
		relationship: contact_notes
		vname: LBL_NOTES
		bean_name: Note
	project
		relationship: projects_contacts
		vname: LBL_PROJECTS
		bean_name: Project
	tasks
		relationship: contact_tasks
		vname: LBL_TASKS
		bean_name: Task
	user_sync
		relationship: contacts_users
		vname: LBL_USER_SYNC
	campaigns
		relationship: contact_campaign_log
		module: CampaignLog
		bean_name: CampaignLog
		vname: LBL_CAMPAIGNS
		no_create: true
	contact_campaigns
		relationship: contact_campaign
		module: Campaigns
		bean_name: Campaign
		vname: LBL_CAMPAIGNS
	prospect_lists
		relationship: prospect_list_contacts
		bean_name: ProspectList
		vname: LBL_PROSPECT_LIST
	app.securitygroups
		relationship: securitygroups_contacts
indices
	idx_cont_last_first
		fields
			- last_name
			- first_name
			- deleted
	idx_contacts_del_last
		fields
			- deleted
			- last_name
	idx_cont_del_reports
		fields
			- deleted
			- reports_to_id
			- last_name
	idx_cont_assigned
		fields
			- assigned_user_id
	idx_cont_email1
		fields
			- email1
	idx_cont_email2
		fields
			- email2
relationships
	contact_direct_reports
		key: id
		target_bean: Contact
		target_key: reports_to_id
		relationship_type: one-to-many
	contact_leads
		key: id
		target_bean: Lead
		target_key: contact_id
		relationship_type: one-to-many
	contact_notes
		key: id
		target_bean: Note
		target_key: contact_id
		relationship_type: one-to-many
	contact_tasks
		key: id
		target_bean: Task
		target_key: contact_id
		relationship_type: one-to-many
	contact_campaign_log
		key: id
		target_bean: CampaignLog
		target_key: target_id
		relationship_type: one-to-many
	contact_campaign
		key: id
		target_bean: Campaign
		target_key: id
		relationship_type: one-to-many
	contacts_partner
		key: partner_id
		target_bean: Partner
		target_key: id
		relationship_type: one-to-many
	contacts_activities
		target_bean: Activity
		target_key: id
		sources
			meetings
				relationship: meetings_contacts
			calls
				relationship: calls_contacts
			tasks
				relationship: contact_tasks
	contacts_history
		target_bean: ActivityHistory
		target_key: id
		sources
			meetings
				relationship: meetings_contacts
			calls
				relationship: calls_contacts
			tasks
				relationship: contact_tasks
			notes
				relationship: contact_notes
			emails
				relationship: emails_contacts_rel
