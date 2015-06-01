<?php return; /* no output */ ?>

detail
	type: union
	primary_key: id
	default_order_by: activity_date
	reportable: true
fields
	app.id
	app.deleted
	app.date_entered
	app.date_modified
	app.assigned_user
	query_module
		type: module_name
		vname: LBL_MODULE
		source
			type: module_name
		options
			- Calls
			- Meetings
			- Tasks
	name
		type: name
		vname: LBL_SUBJECT
		vname_list: LBL_LIST_SUBJECT
		width: 40
		dynamic_module: query_module
		unified_search: true
	status
		type: varchar
		vname: LBL_STATUS
		vname_list: LBL_LIST_STATUS
		width: 25
	contact
		type: ref
		bean_name: Contact
		vname: LBL_CONTACT
		vname_list: LBL_LIST_CONTACT
		width: 25
	parent
		type: ref
		vname: LBL_RELATED_TO
		vname_list: LBL_LIST_RELATED_TO
		dynamic_module: parent_type
		width: 25
		massupdate: false
	parent_type
		type: module_name
		vname: LBL_RELATED_MODULE
	activity_date
		type: datetime
		vname: LBL_DATE
		vname_list: LBL_LIST_DATE
		width: 20
		union_source
			meetings: date_start
			calls: date_start
			tasks: date_due
		massupdate: false
    is_private
        type: bool
        vname: LBL_PRIVATE
	description
		type: text
		vname: LBL_DESCRIPTION
sources
	meetings
		model: Meeting
		filters
			--
				field: status
				value: Planned
	calls
		model: Call
		filters
			--
				field: status
				value: Planned
	tasks
		model: Task
		filters
			--
				field: status
				value: [Not Started, In Progress, Pending Input]
