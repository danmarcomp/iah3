<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Reports/Report.php
	audit_enabled: false
	unified_search: false
	duplicate_merge: false
	table_name: reports
	primary_key: id
	default_order_by: name
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.shared_with
	app.created_by_user
	app.deleted
	name
		vname: LBL_REPORT_NAME
		type: name
		len: 80
		width: 40
		required: true
		vname_list: LBL_LIST_NAME
		unified_search: true
	description
		vname: LBL_DESCRIPTION
		type: text
	primary_module
		vname: LBL_PRIMARY_MODULE
		type: module_name
		required: true
		vname_list: LBL_LIST_PRIMARY_MODULE
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
		vname_list: LBL_LIST_RUN_METHOD
		massupdate: false
		required: true
	next_run
		vname: LBL_NEXT_RUN
		type: datetime
		massupdate: false
	run_interval
		vname: LBL_RUN_INTERVAL
		type: int
		default: 1
		width: 3
	run_interval_unit
		vname: LBL_RUN_INTERVAL_UNIT
		type: enum
		default: days
		len: 40
		options: report_run_interval_unit_dom
		options_add_blank: false
		massupdate: false
	chart_name
		vname: LBL_CHART_NAME
		type: varchar
		len: 30
	chart_type
		vname: LBL_CHART_TYPE
		type: enum
		len: 20
		options: chart_type_dom
		massupdate: false
	chart_options
		vname: LBL_CHART_OPTIONS
		type: varchar
		len: 100
		reportable: false
	chart_title
		vname: LBL_CHART_TITLE
		type: varchar
		len: 100
	chart_rollover
		vname: LBL_CHART_ROLLOVER
		type: varchar
		len: 255
	chart_description
		vname: LBL_CHART_DESCRIPTION
		type: text
	chart_series
		vname: LBL_CHART_DATA_SERIES
		type: enum
		len: 255
		options: []
		width: 30
		massupdate: false
	from_template
		vname: LBL_FROM_TEMPLATE_ID
		type: ref
		bean_name: ReportTemplate
		massupdate: false
	last_run
		vname: LBL_LAST_RUN
		type: datetime
		vname_list: LBL_LIST_LAST_RUN
		massupdate: false
	export_ids
		vname: LBL_EXPORT_IDS
		type: bool
		massupdate: false
links
	report_data
		relationship: reports_report_data
		vname: LBL_ARCHIVED_SUBPANEL_TITLE
		module: ReportData
		bean_name: ReportData
		no_create: true
	notify_users
		relationship: reports_notify_users
		vname: LBL_NOTIFY_USERS_SUBPANEL_TITLE
		layout: ReportNotify		
		no_create: true
	app.securitygroups
		relationship: securitygroups_reports
relationships
	reports_report_data
		key: id
		target_bean: ReportData
		target_key: report_id
		relationship_type: one-to-many
