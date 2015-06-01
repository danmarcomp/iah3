<?php return; /* no output */ ?>

list
	default_order_by: start_date
    item_selector
        disabled: true
    mass_update
        disabled: true
    show_create_button: false
	field_formatter
		file: modules/Forecasts/ListFieldFormatter.php
		class: ForecastListFieldFormatter
	#layouts
	#	Browse
	#		vname: LBL_FORECASTS
	#		view_name: Standard
	#		override_filters
	#			type: personal
	#	TeamIndiv
	#		vname: LBL_TEAM_FORECASTS
	#		view_name: Standard
	#		override_filters
	#			type: team_individual
     #   TeamRollup
      #      vname: LBL_TEAM_ROLLUP_FORECASTS
       #     view_name: Standard
		#	override_filters
		#		type: team_rollup
basic_filters
    period_start
    type
filters
    period_start
        type: enum
        options_function: [Forecast, get_period_options]
        icon: theme-icon module-Calendar
        field: start_date
        vname: LBL_FORECAST_TIME_PERIOD
        default_value: null
    type
        name: type
        vname: LBL_TYPE
        default_value: personal
        type: enum
		options_function: [Forecast, get_type_options]
		required: true
		var_width: true
widgets
	ForecastButton
		type: form_button
		path: modules/Forecasts/widgets/ForecastButton.php

