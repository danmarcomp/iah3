<?php return; /* no output */ ?>

list
    mass_merge_duplicates: true
filters
	name
	current_user_only
		my_items: true
		field: assigned_user_id
	assigned_user
	trigger_module
		operator: =
		options_function: get_search_module_options
	execute_mode
		operator: =
		options_function: get_search_execute_mode_options
fields
	workflow_items
		vname: 
		widget: WorkflowWidget
widgets
	WorkflowWidget
		type: section
		path: modules/Workflow/widgets/WorkflowWidget.php


