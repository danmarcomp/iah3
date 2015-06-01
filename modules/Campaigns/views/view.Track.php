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
                - budget
                - actual_cost
                - expected_revenue
                - expected_cost
                -
                -
                --
                    name: objective
                    colspan: 2
                --
                    name: content
                    colspan: 2
        --
            id: track_chart
            widget: CampaignWidget
    subpanels
		#FIXME add all subpanels
		- sent_emails
		- invalid_emails
		- errored_emails
		- link_clicked_emails
		- viewd_emails
		- removed_emails
		- created_leads
		- created_contacts
        - leads
        - opportunities
		- securitygroups
