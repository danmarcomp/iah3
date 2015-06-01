<?php return; /* no output */ ?>

detail
	type: list
layout
	buttons
		create_task
			widget: SubpanelCreateButton
			module: Tasks
			type_field: parent_type
			id_field: parent_id
			vname: LNK_NEW_TASK
			vname_module: Activities
			quick_create_layout: Standard
			params
				toplevel: true
		create_meeting
			widget: ScheduleActivityButton
			module: Meetings
			params
				toplevel: true
		create_call
			widget: ScheduleActivityButton
			module: Calls
			params
				toplevel: true
		create_email
			widget: SubpanelCreateButton
			module: Emails
			type_field: parent_type
			id_field: parent_id
			vname: LNK_COMPOSE_EMAIL
			vname_module: Activities
			params
				toplevel: true
    columns
		- close_activity
		--
			field: name
			show_icon: true
			dynamic_module: query_module
			width: 35
		--
			field: status
			width: 20
		#- contact
		- parent
		--
			field: activity_date
			width: 25
		--
			field: assigned_user
			width: 25
