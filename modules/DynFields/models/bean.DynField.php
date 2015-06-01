<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/DynFields/DynField.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	optimistic_locking: true
	massupdate: false
	table_name: fields_meta_data
	primary_key: id
	default_order_by: name
fields
	app.id
	app.date_entered
	app.date_modified
	app.deleted
	name
		type: name
		vname: LBL_NAME
		unified_search: false
		audited: true
		required: true
	label
		name: label
		type: varchar
		formula
			class: DynField
			type: function
			function: translate_label
			fields: [label, custom_module]
		vname: COLUMN_TITLE_LABEL
		len: 255
	help
		name: help
		type: varchar
		vname: COLUMN_TITLE_LABEL
		len: 255
	custom_module
		name: custom_module
		type: module_name
		required: true
	custom_bean
		name: custom_bean
		type: char
		len: 100
	data_type
		name: data_type
		vname: COLUMN_TITLE_DATA_TYPE
		type: enum
		options: field_type_dom
		len: 50
		required: true
		massupdate: true
	max_size
		name: max_size
		vname: COLUMN_TITLE_MAX_SIZE
		type: int
		len: 11
		required: false
		validation
			type: range
			min: 1
			max: 255
	required_option
		name: required_option
		type: varchar
		len: 255
	default_value
		name: default_value
		vname: COLUMN_TITLE_DEFAULT_VALUE
		type: varchar
		len: 255
	formula
		name: formula
		vname: COLUMN_TITLE_CALCULATED
		type: varchar
		len: 255
	date_modified
		name: date_modified
		vname: LBL_DATE_MODIFIED
		type: datetime
		massupdate: true
	deleted
		name: deleted
		vname: LBL_DELETED
		type: bool
		default: 0
		massupdate: true
	audited
		name: audited
		vname: LBL_AUDITED
		type: bool
		default: 0
		massupdate: true
	mass_update
		name: mass_update
		vname: COLUMN_TITLE_MASS_UPDATE
		type: bool
		default: 0
		massupdate: true
	duplicate_merge
		name: duplicate_merge
		type: short
		default: 0
	ext1
		name: ext1
		type: varchar
		len: 255
		default: ""
	ext2
		name: ext2
		type: varchar
		len: 255
		default: ""
	ext3
		name: ext3
		type: varchar
		len: 255
		default: ""
	ext4
		name: ext4
		type: varchar
		len: 255
		default: ""
indices
	idx_meta_id_del
		type: index
		fields
			- id
			- deleted
