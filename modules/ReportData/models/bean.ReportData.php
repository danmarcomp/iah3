<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/ReportData/ReportData.php
	unified_search: false
	duplicate_merge: false
	table_name: reports_data
	primary_key: id
	display_name: full_name
fields
	app.id
	app.date_entered
	app.date_modified
	app.modified_user
	app.assigned_user
	app.created_by_user
	app.deleted
	reportdata_number
		vname: LBL_REPORTDATA_NUMBER
		type: int
		len: 11
		required: true
		auto_increment: true
	full_name
		vname: LBL_NAME_NUMBER
		type: name
		source
			type: prefixed
			fields
				- reportdata_number
				- name
	report
		vname: LBL_REPORT
		type: ref
		required: true
		bean_name: Report
		massupdate: false
	cache_filename
		vname: LBL_CACHE_FILENAME
		type: varchar
		len: 100
	pdf_filename
		vname: LBL_PDF_FILENAME
		type: varchar
		len: 100
	groups
		vname: LBL_GROUPS
		type: text
	sources_spec
		vname: LBL_SOURCES
		type: text
	columns_spec
		vname: LBL_FIELDS
		type: text
	filters_spec
		type: text
		vname: LBL_FILTERS
		reportable: false
	filter_values
		type: text
		vname: LBL_FILTER_VALUES
		reportable: false
	sort_order
		vname: LBL_SORT_ORDER
		type: text
		reportable: false
	archived
		vname: LBL_IS_ARCHIVED
		type: bool
		vname_module: ReportData
		massupdate: true
	name
		vname: LBL_NAME
		type: varchar
		len: 150
	description
		vname: LBL_DESCRIPTION
		type: text
	chart_type
		vname: LBL_CHART_TYPE
		type: varchar
		len: 20
	chart_options
		vname: LBL_CHART_OPTIONS
		type: varchar
		len: 100
		reportable: false
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
	record_count
		vname: LBL_RECORD_COUNT
		type: int
	record_offsets
		vname: LBL_RECORD_OFFSETS
		type: text
		reportable: false
	primary_module
		vname: LBL_MODULE
		type: module_name
	export_ids
		vname: LBL_EXPORT_IDS
		type: bool
		massupdate: false
		vname_module: Reports
indices
	reportdata_report
		fields
			- report_id
			- date_entered
	reportdata_number
		fields
			- reportdata_number
