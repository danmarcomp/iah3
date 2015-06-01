<?php return; /* no output */ ?>

detail
	type: union
	primary_key: id
	default_order_by: name
	reportable: true
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	query_module
		type: module_name
		vname: LBL_MODULE
		source
			type: module_name
		options
			- Contacts
			- Users
	accept_status
		type: varchar
		vname: LBL_ACCEPT_STATUS
		vname_module: Meetings
		union_source
			contacts: ~join.accept_status
			users: ~join.accept_status
	name
		type: name
		vname: LBL_NAME
		vname_list: LBL_LIST_NAME
		width: 40
		dynamic_module: query_module
		union_source
			contacts: name
			users: full_name
	first_name
		type: varchar
		vname: LBL_FIRST_NAME
	last_name
		type: varchar
		vname: LBL_LAST_NAME
	email1
		type: email
		vname: LBL_EMAIL_ADDRESS
	email2
		type: email
		vname: LBL_ALT_EMAIL_ADDRESS
	phone_work
		type: phone
		vname: LBL_OFFICE_PHONE
		vname_list: LBL_PHONE
	phone_home
		type: phone
		vname: LBL_HOME_PHONE
	phone_mobile
		type: phone
		vname: LBL_MOBILE_PHONE
	phone_other
		type: phone
		vname: LBL_OTHER_PHONE
	phone_fax
		type: phone
		vname: LBL_FAX_PHONE
sources
	contacts
		model: Contact
	users
		model: User
relationships
	meetings_invitees
		target_bean: Meeting
		target_key: id
		sources
			contacts
				relationship: meetings_contacts
			users
				relationship: meetings_users
	calls_invitees
		target_bean: Call
		target_key: id
		sources
			contacts
				relationship: calls_contacts
			users
				relationship: calls_users
