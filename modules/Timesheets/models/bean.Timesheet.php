<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Timesheets/Timesheet.php
	unified_search: false
	duplicate_merge: false
	table_name: timesheets
	primary_key: id
    reportable: true
hooks
    new_record
        --
            class_function: init_record
    before_save
        --
            class_function: set_name
fields
	app.id
	app.date_entered
	app.date_modified
	app.assigned_user
	app.modified_user
	app.created_by_user
	app.deleted
	name
		vname: LBL_TIMESHEET_NAME
		type: name
		len: 255
		vname_list: LBL_LIST_NAME
        unified_search: true
	description
		vname: LBL_DESCRIPTION
		type: text
	date_starting
		vname: LBL_DATE_STARTING
		type: date
		required: true
		massupdate: false
		vname_list: LBL_LIST_DATE_STARTING
		updateable: false
	date_ending
		vname: LBL_DATE_ENDING
		type: date
		required: true
		massupdate: false
		vname_list: LBL_LIST_DATE_ENDING
		updateable: false
	timesheet_period
		vname: LBL_TIMESHEET_PERIOD
		type: enum
		options: timesheet_period_dom
		dbType: int
		default: 1
		required: true
		massupdate: false
		editable: false
	status
		vname: LBL_STATUS
		type: enum
		options: timesheet_status_dom
		len: 30
		required: true
		massupdate: false
		vname_list: LBL_LIST_STATUS
		default: draft
	total_hours
		vname: LBL_TOTAL_HOURS
		type: float
		dbType: double
		massupdate: false
		vname_list: LBL_LIST_TOTAL_HOURS
links
	booked_hours
		relationship: booked_hours_timesheet
		bean_name: BookedHours
		vname: LBL_BOOKED_HOURS
fields_compat
	assigned_user_name
		rname: user_name
		id_name: assigned_user_id
		vname: LBL_ASSIGNED_TO
		type: relate
		table: users
		module: Users
		dbType: varchar
		link: users
		len: 255
		source: non-db
		massupdate: false
		reportable: false
indices
	timesheet_name_index
		fields
			- name
