<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
                - name
                - currency
                - account
                - amount
                - project_type
                - date_starting
                - lead_source
                - date_ending
                - project_phase
                - assigned_user
                - percent_complete
                --
                    name: use_timesheets
                    onchange: useTimesheets(this.getValue());
                --
                	name: description
                	colspan: 2
                --
                    name: close_opportunity
                    colspan: 2
		--
			id: project_financial
			vname: LBL_FINANCIAL_INFORMATION
			widget: FinancialWidget
