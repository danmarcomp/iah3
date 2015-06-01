<?php return; /* no output */ ?>

detail
	type: table
	table_name: inbound_email_autoreply
	primary_key: id
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	autoreplied_to
		vname: LBL_AUTOREPLIED_TO
		type: email
		required: true
		reportable: false
