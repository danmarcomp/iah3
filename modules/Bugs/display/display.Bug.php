<?php return; /* no output */ ?>

list
	default_order_by: bug_number DESC
    mass_merge_duplicates: true
    show_favorites: true
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: status
		operator: not_eq
		value
			- Closed
			- Rejected
