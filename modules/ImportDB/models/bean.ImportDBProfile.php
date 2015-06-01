<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ImportDB/ImportDBProfile.php
	unified_search: false
	duplicate_merge: false
	table_name: importdb_profiles
	primary_key: id
fields
	app.id
	app.deleted
	name
		vname: LBL_NAME
		type: varchar
		len: 40
		required: true
indices
	importdb_prf_name
		type: unique
		fields
			- name
	importdb_prf_del
		fields
			- deleted
