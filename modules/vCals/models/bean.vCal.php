<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/vCals/vCal.php
	unified_search: false
	duplicate_merge: false
	table_name: vcals
	primary_key: id
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	user
		type: ref
		required: true
		reportable: false
		bean_name: User
		massupdate: true
	type
		type: varchar
		len: 25
	source
		type: varchar
		len: 25
	content
		type: text
indices
	idx_vcal
		fields
			- type
			- user_id
