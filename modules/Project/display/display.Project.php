<?php return; /* no output */ ?>

list
    show_favorites: true
hooks
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		vname: LBL_VIEW_CLOSED_ITEMS
		type: flag
		negate_flag: true
		field: project_phase
		operator: like
		match: prefix
		value: Active -
fields
    close_opportunity
        vname: LBL_OPP_CONV
        widget: CloseRelOpportunityWidget
widgets
	FinancialWidget
		type: section
		path: modules/Project/widgets/FinancialWidget.php
	ConvertProjectButton
		type: form_button
		path: modules/Project/widgets/ConvertProjectButton.php
    CloseRelOpportunityWidget
        type: column
        path: modules/Project/widgets/CloseRelOpportunityWidget.php
