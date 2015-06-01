<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Reports/ReportTemplate.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: reports_templates
	primary_key: id
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.created_by_user
	app.deleted
	name
		vname: LBL_TEMPLATE_NAME
		type: varchar
		len: 80
		required: true
	description
		vname: LBL_DESCRIPTION
		type: text
	primary_module
		vname: LBL_PRIMARY_MODULE
		type: varchar
		len: 40
		required: true
	sources_spec
		type: text
		vname: LBL_SOURCES
		reportable: false
	columns_spec
		type: text
		vname: LBL_COLUMNS
		reportable: false
	filters_spec
		type: text
		vname: LBL_FILTERS
		reportable: false
	filter_values
		type: text
		vname: LBL_FILTER_VALUES
		reportable: false
	sort_order
		type: text
	run_method
		vname: LBL_RUN_METHOD
		type: enum
		len: 40
		default: manual
		options: report_run_method_dom
		massupdate: true
	run_interval
		type: int
		default: 1
	run_interval_unit
		type: enum
		default: days
		len: 40
		options: report_run_interval_unit_dom
		massupdate: false
	chart_name
		vname: LBL_CHART_NAME
		type: varchar
		len: 30
	chart_type
		vname: LBL_CHART_TYPE
		type: varchar
		len: 20
	chart_title
		vname: LBL_CHART_TITLE
		type: varchar
		len: 80
	chart_rollover
		vname: LBL_CHART_ROLLOVER
		type: varchar
		len: 255
	chart_description
		vname: LBL_CHART_DESCRIPTION
		type: text
	chart_series
		vname: LBL_CHART_SERIES
		type: varchar
		len: 255
	filename
		vname: LBL_FILENAME
		type: varchar
		len: 150
	file_modified
		vname: LBL_FILE_MODIFIED
		type: int
		required: true
