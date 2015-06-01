<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EmailTemplates/EmailTemplate.php
	unified_search: false
	duplicate_merge: false
	comment: Templates used in email processing
	table_name: email_templates
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	published
		vname: LBL_PUBLISHED
		type: varchar
		len: 3
		comment: ""
	name
		vname: LBL_NAME
		type: name
		len: 150
		comment: Email template name
		vname_list: LBL_LIST_NAME
		required: true
	description
		vname: LBL_DESCRIPTION
		type: text
        width: 80
        height: 2
		comment: Email template description
		vname_list: LBL_LIST_DESCRIPTION
	subject
		vname: LBL_SUBJECT
		type: varchar
		len: 255
        width: 70
		required: true
		comment: Email subject to be used in resulting email
	body
		vname: LBL_TEXT_BODY
		type: text
		dbType: mediumtext
        width: 100
        height: 20
		comment: Plain text body to be used in resulting email
	body_html
		vname: LBL_BODY
		type: html
		dbType: mediumtext
		comment: HTML formatted email body to be used in resulting email
links
	app.securitygroups
		relationship: securitygroups_emailtemplates
fields_compat
	assigned_user_name
		id_name: assigned_user_id
		type: varchar
		table: users
		source: non-db
		reportable: false
		massupdate: true
		vname: LBL_ASSIGNED_TO_NAME
indices
	idx_email_template_name
		fields
			- name
