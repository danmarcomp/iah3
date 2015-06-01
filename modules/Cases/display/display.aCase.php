<?php return; /* no output */ ?>

list
	default_order_by: case_number DESC
    mass_merge_duplicates: true
    show_favorites: true
    buttons
        get_a_case
            icon: theme-icon module-Cases
            vname: LBL_GET_CASE
            perform: window.location.href='index.php?module=Cases&action=GetCase'
            type: button
            hidden: ! config.company.case_queue_user
hooks
    view
        --
            class_function: add_view_popups
            required_fields: [account_id]
basic_filters
	view_closed
auto_filters
    account_id
    view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		operator: OR
		multiple
			--
				field: status
				value: Pending
			--
				field: status
				operator: like
				match: prefix
				value: Active -
fields
	required_skills
		vname: LBL_SKILLS
		widget: SkillsWidget
    activity_time
        vname: LBL_ACTIVITY_TIME
        type: formula
        formula
            type: function
            class: aCase
            function: calc_activity_time
            alias_fields
                effort_actual: effort_actual
                effort_actual_unit: effort_actual_unit
                travel_time: travel_time
                travel_time_unit: travel_time_unit
    effort_actual_display
        vname: LBL_TIME_USED
        type: formula
        formula
            type: function
            class: aCase
            function: format_effort_actual
            alias_fields
                effort_actual: effort_actual
                effort_actual_unit: effort_actual_unit
widgets
	EscalateButton
		type: form_button
		path: modules/Cases/widgets/EscalateButton.php
