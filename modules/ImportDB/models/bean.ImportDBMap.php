<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ImportDB/ImportDBMap.php
	unified_search: false
	duplicate_merge: false
	comment: ImportDB mapping control table
	table_name: importdb_maps
	primary_key: id
fields
	app.id
	app.deleted
	app.assigned_user
	name
		vname: LBL_NAME
		type: varchar
		len: 32
		required: true
		comment: Name of import map
	module
		vname: LBL_MODULE
		type: id
		required: true
		comment: Module used for import
	mapping
		vname: LBL_CONTENT
		type: text
		required: true
		comment: Mappings for all columns
	has_header
		vname: LBL_HAS_HEADER
		type: bool
		default: 1
		required: true
		comment: Indicator if source file contains a header row
	delimiter
		vname: LBL_DELIMITER
		type: varchar
		len: 10
		required: true
		comment: CSV delimiter
indices
	idx_owner_module_name
		fields
			- assigned_user_id
			- module
			- deleted
