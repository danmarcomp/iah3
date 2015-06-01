<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                - name
                - assigned_user
                - status
                -
                - start_date
                - date_modified
                - end_date
                - date_entered
                - campaign_type
                - frequency
                - 
                -
                - currency
                -
                - budget
                - impressions
                - expected_cost
                - opportunities_won
                - actual_cost
                - cost_per_impression
                - expected_revenue
                - cost_per_click
        --
            id: roi_chart
            widget: CampaignWidget
