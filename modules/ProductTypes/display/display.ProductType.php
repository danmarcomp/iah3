<?php return; /* no output */ ?>

list
	default_order_by: name
basic_filters
    category
filters
	name
	category_id
		operator: =
		options_function: get_search_categories
		default_value: ""
edit
	quick_create
		via_ref_input: true
