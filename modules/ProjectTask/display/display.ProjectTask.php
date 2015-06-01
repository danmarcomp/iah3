<?php return; /* no output */ ?>

list
	default_order_by: order_number
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
basic_filters
	view_closed
auto_filters
    status
    project_phase
    user_id
    user_status
filters
	view_closed
		default_value: false
		vname: LBL_VIEW_CLOSED_ITEMS
		type: flag
		negate_flag: true
		field: status
		operator: not_eq
		value: Completed
	parent_name
		vname: LBL_PROJECT_NAME
		field: project
    user_id
        field: booking_users~join.user_id
        type: id
        operator: eq
    user_status
        field: booking_users~join.booking_status
        type: varchar
        operator: eq
    project_phase
        field: parent.project_phase
        type: varchar
        operator: eq
fields
	any_user_id
		vname: LBL_ANY_USER_ID
		type: id
		source
			type: field
			field: booking_users~join.user_id
			subselect_filter: true
widgets
	CostsWidget
		type: section
		path: modules/ProjectTask/widgets/CostsWidget.php
