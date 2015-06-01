<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Vacations/Vacation.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: vacations
	primary_key: id
hooks
	new_record
		--
			class_function: init_record
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
		updateable: false
	app.created_by_user
	app.deleted
	description
		vname: LBL_DESCRIPTION
		type: text
		audited: true
	days
		type: double
		required: true
		vname: LBL_DAYS
		audited: true
		validation
			type: range
			min: 0.25
			max: 100
		vname_list: LBL_LIST_DAYS
	status
		type: enum
		len: 20
		required: true
		options: leave_status_dom
		vname: LBL_LEAVE_STATUS
		audited: true
		vname_list: LBL_LIST_STATUS
		default: planned
		massupdate: true
	leave_type
		type: enum
		len: 20
		required: true
		vname: LBL_LEAVE_TYPE
		options: leave_type_dom
		audited: true
		vname_list: LBL_LIST_TYPE
		default: vacation
		massupdate: true
	date_start
		vname: LBL_DATE_START
		type: date
		required: true
		massupdate: false
		audited: true
		vname_list: LBL_LIST_DATE
	date_end
		vname: LBL_DATE_END
		type: date
		required: true
		massupdate: false
		audited: true
		vname_list: LBL_LIST_DATE_END
