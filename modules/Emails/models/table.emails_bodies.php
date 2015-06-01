<?php return; /* no output */ ?>

detail
	type: table
	table_name: emails_bodies
	primary_key: email_id
fields
	app.deleted
	email_id
		type: char
		len: 36
		default: ""
	description
		vname: LBL_BODY
		type: text
		dbType: mediumtext
	description_html
		vname: LBL_HTML_BODY
		type: html
		dbType: mediumtext
	upgraded
		type: bool
		len: 1
		default: 0
		required: true
indices
	emails_bodies_upgraded
		fields
			- upgraded
	emails_bodies_ftext_b
		type: fulltext
		fields
			- description
