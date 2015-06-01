<?php return; /* no output */ ?>

detail
	type: table
	unified_search: false
	duplicate_merge: false
	comment: System table containing system-wide definitions
	table_name: config
	primary_key: [category, name]
fields
	category
		vname: LBL_CONFIG_CATEGORY
		type: varchar
		len: 32
		charset: ascii
		comment: Settings are grouped under this category
		required: true
	name
		vname: LBL_CONFIG_NAME
		type: varchar
		len: 32
		charset: ascii
		comment: The name given to the setting
		required: true
	value
		vname: LBL_CONFIG_VALUE
		type: text
		comment: The value given to the setting
