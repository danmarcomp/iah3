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
                - code
                - related_account
                - 
                - date_start
                - date_end
                - lead_exclusivity
                - lead_revenue_sharing
                - commission_rate
                - date_entered
                - assigned_user
                - date_modified
                --
                    name: description
                    colspan: 2            
    subpanels
        - leads
        - opportunities
        - accounts
        - contacts
        - invoices
        - securitygroups
