<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Versions/Version.php
	unified_search: false
	duplicate_merge: false
	table_name: versions
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
		type: varchar
		len: 255
		required: true
	file_version
		vname: LBL_FILE_VERSION
		type: varchar
		len: 255
		required: true
	db_version
		vname: LBL_DB_VERSION
		type: varchar
		len: 255
		required: true
indices
	idx_version
		fields
			- name
			- deleted
