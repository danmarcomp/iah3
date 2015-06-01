<?php return; /* no output */ ?>

list
	default_order_by: name
    mass_merge_duplicates: true
    show_favorites: true
    layouts
        Duplicates
            vname: LBL_DUPLICATES
            view_name: Duplicates
            hidden: true
	massupdate_hooks
		--
			name: FillProbability
			class_function: mass_fill_probability
			class: Opportunity
			file: modules/Opportunities/Opportunity.php
hooks
    view
    	--
    		class_function: add_convert_project
    edit
        --
            class_function: add_additional_hiddens
basic_filters
	view_closed
filters
	view_closed
		default_value: false
		type: flag
		negate_flag: true
		vname: LBL_VIEW_CLOSED_ITEMS
		field: sales_stage
		operator: not_like
		match: prefix
		value: Closed
	probability
		operator: >=
		options_function: get_search_prob_options
	from_date_closed
		operator: >=
		vname: LBL_FROM_DATE_CLOSED
		field: date_closed
	to_date_closed
		operator: <=
		vname: LBL_TO_DATE_CLOSED
		field: date_closed
    stage_multy
        type: multienum
        field: sales_stage
widgets
    CampaignNameWidget
        type: field
        path: modules/Opportunities/widgets/CampaignNameWidget.php
