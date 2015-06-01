<?php return; /* no output */ ?>

detail
	type: bean
	bean_file: modules/Forecasts/Forecast.php
	audit_enabled: true
	unified_search: false
	duplicate_merge: false
	table_name: forecasts
	primary_key: id
	reportable: true
	default_order_by: start_date
fields
	app.id
	app.date_entered
	app.created_by_user
	app.date_modified
	app.modified_user
		required: false
	app.deleted
	name
		vname: LBL_FORECAST_TIME_PERIOD
		type: name
		len: 100
	user
		vname: LBL_USER_ID
		type: ref
		bean_name: User
		required: true
		massupdate: true
	start_date
		vname: LBL_START_DATE
		type: date
		required: true
		disable_relative_format: true
		massupdate: true
	end_date
		vname: LBL_END_DATE
		type: date
		required: true
		disable_relative_format: true
		massupdate: true
	opportunities
		vname: LBL_OPPORTUNITIES
		type: int
		len: 11
		required: true
		default: 0
	total
		vname: LBL_TOTAL
		vname_list: LBL_LIST_TOTAL
		type: base_currency
		dbType: int
		len: 11
		required: true
		default: 0
		display_converted: convert
	weighted
		vname: LBL_WEIGHTED
		vname_list: LBL_LIST_WEIGHTED
		type: base_currency
		dbType: int
		len: 11
		required: true
		default: 0
		display_converted: convert
	best_case
		vname: LBL_BEST_CASE
		type: base_currency
		dbType: int
		len: 11
		required: true
		default: 0
		display_converted: convert
	commit
		vname: LBL_COMMIT
		type: base_currency
		dbType: int
		len: 11
		required: true
		default: 0
		display_converted: convert
	actual
		vname: LBL_ACTUAL
		type: base_currency
		dbType: int
		len: 11
		required: true
		default: 0
		display_converted: convert
	forecast
		vname: LBL_FORECAST
		type: base_currency
		dbType: int
		len: 11
		default: 0
		display_converted: convert
	commit_date
		vname: LBL_COMMIT_DATE
		type: date
		disable_relative_format: true
		massupdate: true
	quota
		vname: LBL_QUOTA
		type: base_currency
		dbType: int
		len: 11
		display_converted: convert
	quota_date
		vname: LBL_QUOTA_DATE
		type: date
		disable_relative_format: true
		massupdate: true
	quota_percent
		vname: %
		type: formula
		source: non-db
		formula
			type: function
			class: Forecast
			function: calc_quote_percent
			fields: [actual, quota]
	type
		vname: LBL_TYPE
		type: enum
		options: forecast_type_dom
		len: 25
		audited: false
		default: personal
		massupdate: true
indices
	idx_forecast_uid
		fields
			- user_id
	idx_forecast_date
		fields
			- start_date
	idx_forecast_type
		fields
			- type
relationships
	forecast_user
		key: user_id
		target_bean: User
		target_key: id
		relationship_type: one-to-many
