<?php return; /* no output */ ?>

detail
	type: link
	table_name: securitygroups_default
	primary_key: [securitygroup_id, module]
fields
	app.date_modified
	app.deleted
	securitygroup_id
		type: char
		len: 36
	module
		type: module_name
