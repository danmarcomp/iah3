<?php return; /* no output */ ?>

view
	quick_create_layout: Standard
list
    field_formatter
        file: include/layout/HighlightedListFieldFormatter.php
        class: HighlightedListFieldFormatter
basic_filters
	contact
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: status
		operator: not_eq
		value: Completed
fields
	any_contact_id
		vname: LBL_ANY_CONTACT_ID
		type: id
		source
			type: field
			field: cust_contact_id
	any_user_id
		vname: LBL_ANY_USER_ID
		type: id
		source
			type: field
			field: assigned_user_id
	close_activity
		widget: CloseActivityInput
