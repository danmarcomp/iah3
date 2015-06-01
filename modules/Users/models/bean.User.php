<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Users/User.php
	unified_search
		enabled: true
		default: false
	duplicate_merge: false
	table_name: users
	primary_key: id
	display_name: name
	default_order_by: last_name
	export_fields
		- id
		- user_name
		- first_name
		- last_name
		- description
		- date_entered
		- date_modified
		- modified_user_id
		- created_by
		- title
		- department
		- is_admin
		- phone_home
		- phone_mobile
		- phone_work
		- phone_other
		- phone_fax
		- email1
		- email2
		- address_street
		- address_city
		- address_state
		- address_postalcode
		- address_country
		- reports_to_id
		- portal_only
		- status
		- receive_notifications
		- employee_status
		- messenger_id
		- messenger_type
	importable: LBL_USERS
hooks
	before_save
		--
			class_function: before_save
	after_save
		--
			class_function: after_save
	after_add_link
		--
			class_function: update_acl
	after_remove_link
		--
			class_function: update_acl
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	full_name
		vname: LBL_NAME
		type: name
		source
			type: person_name
			fields
				- first_name
				- last_name
		unified_search: true
	user_name
		vname: LBL_USER_NAME
		type: user_name
		len: 60
		vname_list: LBL_LIST_USER_NAME
		unified_search: true
		required: true
	user_hash
		vname: LBL_NEW_PASSWORD1
		type: char
		len: 32
		editable: false
		reportable: false
		protected: true
	authenticate_id
		vname: LBL_AUTHENTICATE_ID
		type: varchar
		len: 100
		reportable: false
	sugar_login
		vname: LBL_SUGAR_LOGIN
		type: bool
		default: 1
		massupdate: false
		reportable: false
	salutation
		vname: LBL_SALUTATION
		type: enum
		options: salutation_dom
		massupdate: false
		len: 20
	first_name
		vname: LBL_FIRST_NAME
		type: name
		len: 30
	last_name
		vname: LBL_LAST_NAME
		type: name
		len: 30
		required: true
	name
		vname: LBL_NAME
		type: name
		source
			type: user_display_name
		reportable: false
	reports_to
		vname: LBL_REPORTS_TO
		type: ref
		reportable: true
		bean_name: User
		massupdate: true
		id_name: reports_to_id
	is_admin
		vname: LBL_IS_ADMIN
		vname_list: LBL_LIST_ADMIN
		type: bool
		default: 0
		massupdate: true
	receive_notifications
		vname: LBL_RECEIVE_NOTIFICATIONS
		type: bool
		default: 1
		reportable: false
		massupdate: true
	description
		vname: LBL_NOTES
		type: text
	title
		vname: LBL_TITLE
		type: varchar
		len: 50
	department
		vname: LBL_DEPARTMENT
		type: enum
		options: departments_dom
		vname_list: LBL_LIST_DEPARTMENT
		massupdate: true
	phone_home
		vname: LBL_HOME_PHONE
		type: phone
	phone_mobile
		vname: LBL_MOBILE_PHONE
		type: phone
	phone_work
		vname: LBL_WORK_PHONE
		type: phone
		vname_list: LBL_LIST_PRIMARY_PHONE
	phone_other
		vname: LBL_OTHER_PHONE
		type: phone
	phone_fax
		vname: LBL_FAX_PHONE
		type: phone
	email1
		vname: LBL_EMAIL
		type: email
		vname_list: LBL_LIST_EMAIL
	email2
		vname: LBL_OTHER_EMAIL
		type: email
	status
		vname: LBL_STATUS
		vname_list: LBL_LIST_STATUS
		type: enum
		len: 25
		options: user_status_dom
		massupdate: true
		required: true
		default: Active
	address_street
		vname: LBL_ADDRESS_STREET
		type: varchar
		len: 150
	address_city
		vname: LBL_ADDRESS_CITY
		type: varchar
		len: 100
	address_state
		vname: LBL_ADDRESS_STATE
		type: varchar
		len: 100
	address_country
		vname: LBL_ADDRESS_COUNTRY
		type: varchar
		len: 25
	address_postalcode
		vname: LBL_ADDRESS_POSTALCODE
		type: varchar
		len: 9
	receive_case_notifications
		vname: LBL_RECEIVE_CASE_NOTIFICATIONS
		type: bool
		default: 1
		reportable: false
		massupdate: true
	location
		vname: LBL_LOCATION
		type: varchar
		len: 30
		reportable: false
	sms_address
		vname: LBL_SMS
		type: varchar
		len: 100
		reportable: false
	photo_filename
		vname: LBL_PHOTO
		type: image_ref
		len: 255
		reportable: false
		upload_dir: {FILES}/images/directory/
	in_directory
		vname: LBL_IN_DIRECTORY
		type: bool
		default: 0
		reportable: false
		massupdate: true
	quotation_mode
		type: enum
		len: 30
		options: quotation_modes
		vname: LBL_QUOTE_CATALOG_MODE
		massupdate: false
		reportable: false
	project_mode
		type: enum
		len: 30
		options: project_modes
		vname: LBL_PROJECT_CATALOG_MODE
		massupdate: false
		reportable: false
	week_start_day
		vname: LBL_WEEK_START_DAY
		type: int
		len: 1
		default: 0
		reportable: false
	day_begin_hour
		vname: LBL_DAY_BEGIN_HOUR
		type: float
		default: 8
		reportable: false
		disable_num_format: true
	day_end_hour
		vname: LBL_DAY_END_HOUR
		type: float
		default: 18
		reportable: false
		disable_num_format: true
	portal_only
		vname: LBL_PORTAL_ONLY
		vname_list: LBL_LIST_PORTAL
		type: bool
		massupdate: true
	employee_status
		vname: LBL_EMPLOYEE_STATUS
		type: enum
		options: employee_status_dom
		len: 25
		massupdate: true
	messenger_id
		vname: LBL_MESSENGER_ID
		type: varchar
		len: 50
	messenger_type
		vname: LBL_MESSENGER_TYPE
		type: varchar
		len: 25
links
	report_notify_link
		relationship: reports_notify_users
		vname: LBL_NOTIFIED_REPORTS
		reportable: false
	calls
		relationship: calls_users
		vname: LBL_CALLS
	meetings
		relationship: meetings_users
		vname: LBL_MEETINGS
	contacts_sync
		relationship: contacts_users
		vname: LBL_CONTACTS_SYNC
	aclroles
		relationship: acl_roles_users
		side: right
		vname: LBL_ROLES
	app.securitygroups
		relationship: securitygroups_users
	prospect_lists
		relationship: prospect_list_users
		module: ProspectLists
		vname: LBL_PROSPECT_LIST
	skills
		relationship: users_skills
		vname: LBL_USER_SKILLS
	mailboxes
		relationship: user_mailboxes
		vname: LBL_MAILBOXES
		layout: User
indices
	user_name_idx
		fields
			- user_name
relationships
	acl_roles_users
		key: id
		target_bean: ACLRole
		target_key: id
		join
			table: acl_roles_users
			source_key: user_id
			target_key: role_id
		relationship_type: many-to-many
	user_mailboxes
		key: id
		target_bean: EmailPOP3
		target_key: user_id
		relationship_type: one-to-many
