<?php return; /* no output */ ?>

list
	default_order_by: type
    view_in_popup: edit
	show_create_button: false
fields
	run_interval
		widget: RunIntervalInput
	show_type
		vname: LBL_TYPE
		type: varchar
		detail_link: true
		source
			value_function: [Schedule, translate_type]
			fields: [type]
	show_description
		vname: LBL_DESCRIPTION
		type: text
		source
			value_function: [Schedule, translate_description]
			fields: [type]
	status_color
		vname: LBL_STATUS_COLOR
		type: status_color
		source
			type: subselect
			class: Schedule
			function: status_color_sql
	status_text
		vname: LBL_STATUS
		type: varchar
		source
			value_function: [Schedule, status_text]
			fields: [enabled, status, last_run]
	interval_text
		vname: LBL_RUN_INTERVAL
		type: varchar
		source
			value_function: [Schedule, get_interval_text]
            fields: [run_interval, run_interval_unit]
basic_filters
    show_hidden
        hidden: true
filters
    show_hidden
        default_value: false
        type: flag
        negate_flag: true
        vname: LBL_VIEW_HIDDEN
        field: hidden
		operator: eq
