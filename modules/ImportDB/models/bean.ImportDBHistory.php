<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ImportDB/ImportDBHistory.php
	unified_search: false
	duplicate_merge: false
	table_name: importdb_history
	primary_key: id
fields
	app.id
	app.date_modified
	app.deleted
	module
		vname: LBL_MODULE
		type: ref
		bean_name: ImportDBModule
		required: true
		options_function
			class_function: getModuleOptions
			class: ImportDBHistory
			file: modules/ImportDB/ImportDBHistory.php
		massupdate: false
    profile_name
        vname: LBL_PROFILE
        type: formula
        source: non-db
        massupdate: false
        reportable: false
        formula
            type: function
            class: ImportDBHistory
            function: moduleProfileName
			fields: [module_id]
	source_id
		vname: LBL_SOURCE_ID
		type: id
		massupdate: false
	generated
		vname: LBL_GENERATED_ID
		type: ref
		required: true
		dynamic_module: module
		massupdate: false
indices
	importdb_hist_mod_prf
		fields
			- module_id
	importdb_hist_mod_scr
		fields
			- source_id
	importdb_hist_mod_gen
		fields
			- generated_id
	importdb_hist_del
		fields
			- deleted
relationships
	importtdb_modules_history
		key: module_id
		target_bean: ImportDBProfile
		target_key: id
		relationship_type: one-to-many
