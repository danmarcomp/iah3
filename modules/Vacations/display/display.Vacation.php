<?php return; /* no output */ ?>

list
basic_filters
    view_closed_items
filters
	current_user_only
		my_items: true
		vname: LBL_CURRENT_USER_FILTER
		field: assigned_user_id
	view_closed_items
        default_value: false
        type: flag
        negate_flag: true
        vname: LBL_VIEW_CLOSED_ITEMS
        field: status
        operator: not_eq
        value
            - cancelled
	date_start
		operator: =
		where_function: search_by_date_start
	date_end
		operator: =
		where_function: search_by_date_end
