<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Project/ProjectFinancials.php
	unified_search: false
	duplicate_merge: false
	table_name: project_financials
	primary_key
		- project_id
		- period
fields
	app.date_modified
	app.deleted
	project
		type: ref
		vname: LBL_PROJECT
		required: true
		bean_name: Project
	period
		vname: LBL_PERIOD
		type: date
		format: yearmonth
		required: true
	expected_cost
		vname: LBL_EXPECTED_COST
		type: currency
		display_converted: true
	expected_revenue
		vname: LBL_EXPECTED_REVENUE
		type: currency
		display_converted: true
	actual_cost
		vname: LBL_ACTUAL_COST
		type: currency
		display_converted: true
	actual_revenue
		vname: LBL_ACTUAL_REVENUE
		type: currency
		display_converted: true