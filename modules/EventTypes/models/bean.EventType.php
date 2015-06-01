<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/EventTypes/EventType.php
	unified_search: false
	duplicate_merge: false
	table_name: event_types
	primary_key: id
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	name
		vname: LBL_NAME
		type: name
		len: 50
		required: true
		unified_search: true
indices
	idx_event_types
		fields
			- name
			- deleted
