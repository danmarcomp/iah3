<?php return; /* no output */ ?>

list
	default_order_by: name
	show_create_button: false
    show_favorites: true
filters
	primary_module
		options_function: [Report, get_search_module_options]
fields
	run_interval
		widget: RunIntervalInput
	primary_module_link
		vname: LBL_PRIMARY_MODULE
		type: html
		source
			type: function
			value_function: [Report, render_module_link]
			fields: [primary_module]
