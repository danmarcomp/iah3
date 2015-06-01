<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    form_buttons
        convert_proj
            vname: LBL_CONV_TO_PROJECT
            widget: ConvertProjectButton
            link_name: project
            param_name: opportunity_id
            group: convert
        convert_quote
            vname: LBL_CONV_QUOTE
            url: ?module=Quotes&action=EditView&opportunity_id={RECORD}&{RETURN}
            icon: theme-icon create-Quote
            group: convert
        show_forecast
            widget: ForecastButton
            order: 69
	sections
		--
			elements
                - name
                - amount
                - partner
                --
                    name: campaign
                    widget: CampaignNameWidget
                - account
                - date_closed
                - opportunity_type
                - next_step
				- lead_source
				- sales_stage
                - forecast_category
                - probability
                - assigned_user
                - date_modified
                -
                - date_entered
				--
					name: description
					colspan: 2
    subpanels
        - activities
        - history
        - leads
        - contacts
        - accounts
        - documents
        - quotes
        - salesorders
        - invoice
        - project
        - threads
        - expensereports
        - securitygroups
