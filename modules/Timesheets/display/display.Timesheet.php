<?php return; /* no output */ ?>

list
filters
	name
	contact_name
		fields
			- contacts.first_name
			- contacts.last_name
	assigned_user
		operator: =
		options_function: get_search_user_options
	date_starting
fields
	timesheet_items
		vname: LBL_TIMESHEET_BOOKED_HOURS
		widget: TimesheetItemsWidget
widgets
	TimesheetItemsWidget
		type: section
		path: modules/Timesheets/widgets/TimesheetItemsWidget.php

