<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ActivityLog/ActivityLog.php
	unified_search: false
	duplicate_merge: false
	table_name: activity_log
	primary_key: id
	default_order_by: date_entered DESC
	reportable: true
fields
	app.id
	app.date_entered
	app.assigned_user
	app.deleted
	module_name
		vname: LBL_ACTIVITY_LOG_MODULE
		type: module_name
		required: true
		options_function: [ActivityLog, get_search_module_options]
	record_item
		vname: LBL_ACTIVITY_LOG_RECORD
		type: ref
		bean_name: ""
		dynamic_module: module_name
		massupdate: true
	primary_account
		vname: LBL_ACTIVITY_LOG_PRIMARY_ACC
		type: ref
		bean_name: Account
		massupdate: true
	primary_contact
		vname: LBL_ACTIVITY_LOG_PRIMARY_CONT
		type: ref
		bean_name: Contact
		massupdate: true
	status
		vname: LBL_ACTIVITY_LOG_STATUS
		type: enum
		options: activity_log_status_dom
		len: 40
		massupdate: true
	moved_to
		vname: LBL_ACTIVITY_LOG_MOVED_TO
		type: datetime
		massupdate: true
	converted_to
		vname: LBL_CONVERTED_TO
		type: ref
		dynamic_module: converted_to_type
		reportable: true
		massupdate: false
	converted_to_type
		vname: LBL_CONVERTED_TO_TYPE
		type: module_name
		massupdate: false
indices
	by_module_record
		fields
			- module_name
			- record_item_id
