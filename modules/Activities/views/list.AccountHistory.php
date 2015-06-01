<?php return; /* no output */ ?>

detail
	type: list
	title: LBL_HISTORY
	filter_name: Summary
layout
	buttons
		create_note
			widget: SubpanelCreateButton
			module: Notes
			type_field: parent_type
			id_field: parent_id
			vname: LNK_NEW_NOTE
			vname_module: Activities
			params
				toplevel: true
		create_email
			widget: SubpanelCreateButton
			module: Emails
			type_field: parent_type
			id_field: parent_id
			vname: LNK_NEW_EMAIL
			vname_module: Activities
			params
				toplevel: true
			query_params
				type: archived
		show_summary
			widget: ShowActivitiesButton
			module: Calls
			params
				toplevel: true
		show_history
			widget: ShowActivitiesButton
			module: Calls
			params
				toplevel: true
				total_history: true
    columns
		--
			field: name
			show_icon: true
			dynamic_module: query_module
			width: 35
			no_sort: true
		--
			field: status
			width: 20
		- parent
		- rel_contact
		--
			field: activity_date
			width: 25
		--
			field: assigned_user
			width: 25
