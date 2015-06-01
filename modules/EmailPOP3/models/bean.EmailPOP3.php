<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EmailPOP3/EmailPOP3.php
	unified_search: false
	duplicate_merge: false
	table_name: emails_pop3
	primary_key: id
hooks
	new_record
		--
			class_function: init_record
fields
	app.id
	app.deleted
	user
		vname: LBL_LIST_USER_NAME
		type: ref
		bean_name: User
		massupdate: true
	name
		vname: LBL_NAME
		type: name
		len: 50
		required: true
		unified_search: true
	leave_on_server
		vname: LBL_LEAVE_ON_SERVER
		type: bool
		required: true
		default: 1
		massupdate: true
	mark_read
		vname: LBL_MARK_READ
		type: bool
		default: 1
		required: true
		massupdate: true
	active
		vname: LBL_ACTIVE
		type: bool
		default: 1
		massupdate: true
	scheduler
		vname: LBL_SCHEDULER
		type: bool
		default: 1
		massupdate: true
	host
		type: varchar
		len: 80
		vname: LBL_HOST
		required: true
	username
		type: varchar
		len: 60
		width: 25
		vname: LBL_USERNAME
		required: true
	password
		type: password
		len: 60
		width: 25
		reportable: false
		vname: LBL_PASSWORD
	email
		type: email
		vname: LBL_EMAIL
		required: true
		unified_search: true
	from_name
		type: varchar
		len: 80
		vname: LBL_FROM_NAME
	last_check
		vname: LBL_LAST_CHECK
		type: datetime
		massupdate: true
	last_id
		type: varchar
		len: 255
		reportable: false
	protocol
		type: enum
		len: 10
		vname: LBL_PROTOCOL
		default: IMAP
		options_function
			- EmailPOP3
			- get_protocol_options
		options_add_blank: false
		massupdate: true
	imap_folder
		type: varchar
		len: 100
		vname: LBL_IMAP_FOLDER
		default: INBOX
	port
		type: int
		required: true
		vname: LBL_PORT
	use_ssl
		vname: LBL_USE_SSL
		type: bool
		required: true
		massupdate: true
	mailbox_type
		vname: LBL_MAILBOX_TYPE
		type: enum
		len: 10
		options_function
			- EmailPOP3
			- get_type_options
		massupdate: true
	template
		vname: LBL_AUTOREPLY
		type: ref
		bean_name: EmailTemplate
		massupdate: true
	ooo_template
		vname: LBL_OOO_TEMPLATE
		type: ref
		bean_name: EmailTemplate
		massupdate: true
	filter_domain
		vname: LBL_FILTER_DOMAIN
		type: varchar
		len: 50
	email_folder
		vname: LBL_DELIVER_TO_FOLDER
		type: ref
		bean_name: EmailFolder
		id_name: email_folder_id
		options
		massupdate: true
	restrict_since
		type: enum
		len: 10
		vname: LBL_RESTRICT_SINCE
		options_function
			- EmailPOP3
			- get_restrict_options
		default: 30d
		options_add_blank: false
		massupdate: true
indices
	idx_user_id
		fields
			- user_id
	idx_name
		fields
			- name
