<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ImportDB/ImportDBModule.php
	unified_search: false
	duplicate_merge: false
	table_name: importdb_modules
	primary_key: id
fields
	app.id
	app.deleted
	profile
		vname: LBL_PROFILE_ID
		type: ref
		bean_name: ImportDBProfile
		required: true
	name
		vname: LBL_NAME
		type: char
		len: 80
		required: true
indices
	importdb_mod_prf
		type: unique
		fields
			- profile_id
			- name
	importdb_mod_del
		fields
			- deleted
relationships
	importdb_profile_modules
		key: profile_id
		target_bean: ImportDBProfile
		target_key: id
		relationship_type: one-to-many
