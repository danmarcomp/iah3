<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Scheduler/Schedule.php
	unified_search: false
	duplicate_merge: false
	table_name: schedules
	primary_key: id
	default_order_by: date_entered
	display_name: show_type
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	type
		vname: LBL_SCHEDULE_TYPE
		type: varchar
		charset: ascii
		len: 80
		required: true
		updateable: false
	last_run
		vname: LBL_LAST_RUN
		type: datetime
		editable: false
	run_interval_unit
		vname: LBL_RUN_INTERVAL_UNIT
		type: enum
		options: interval_units_dom
		len: 40
		required: true
		massupdate: false
	run_interval
		vname: LBL_RUN_INTERVAL
		type: int
		len: 6
		required: true
		massupdate: false
	options
		vname: LBL_OPTIONS
		type: text
		reportable: false
		editable: false
	next_run
		vname: LBL_NEXT_RUN
		type: datetime
		massupdate: true
	status
		vname: LBL_STATUS
		type: enum
		options: ""
		len: 40
		editable: false
	enabled
		vname: LBL_ENABLED
		type: bool
		default: 1
		massupdate: true
	run_on_user_login
		vname: LBL_RUN_ON_USER_LOGIN
		type: bool
		default: 1
		massupdate: true
	run_on_page_load
		vname: LBL_RUN_ON_PAGE_LOAD
		type: bool
		default: 0
		massupdate: false
	hidden
		vname: LBL_HIDDEN
		type: bool
		default: 0
		massupdate: false
indices
	schedules_next_run
		fields
			- next_run
	schedules_type_uniq
		type: unique
		fields
			- type
