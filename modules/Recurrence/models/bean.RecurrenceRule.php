<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Recurrence/RecurrenceRule.php
	unified_search: false
	duplicate_merge: false
	table_name: recurrence_rules
	primary_key: id
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	parent_type
		vname: LBL_PARENT_TYPE
		required: true
		type: module_name
	parent
		vname: LBL_PARENT_ID
		required: true
		type: ref
		dynamic_module: parent_type
		massupdate: true
	freq
		vname: LBL_FREQUENCY
		required: true
		type: enum
		options: recur_frequency_dom
		len: 30
		massupdate: true
	freq_interval
		vname: LBL_INTERVAL
		required: true
		type: int
		default: 1
	until
		vname: LBL_UNTIL
		type: datetime
		massupdate: true
	limit_count
		vname: LBL_COUNT
		type: int
	rule
		vname: LBL_RULE
		type: varchar
		len: 255
	is_restriction
		vname: LBL_IS_RESTRICTION
		required: true
		type: bool
		default: 0
		massupdate: true
	instance_count
		vname: LBL_INSTANCE_COUNT
		type: int
	date_last_instance
		vname: LBL_LAST_INSTANCE
		type: datetime
		massupdate: true
	forward_times
		vname: LBL_INTERVAL
		type: text
indices
	idx_recurrence_parent
		type: alternate_key
		fields
			- parent_type
			- parent_id
