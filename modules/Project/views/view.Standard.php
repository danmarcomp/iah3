<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        gantt
            vname: LBL_GANTT_BUTTON_LABEL
            type: button
			perform: "var w = window.open('index.php?module=Project&action=gantt&to_pdf=true&project_id={RECORD}&image=1', 'GanttChart', 'width=1000,height=600,resizable=1,scrollbars=1'); w.focus();"
        support_contract
            vname: LBL_PRODUCTS_BUTTON_LABEL
			type: button
			perform:
				var form_params = {module: 'Project', action: 'SupportContracts', record: '{RECORD}'};
		        var popup = new SUGAR.ui.Dialog('support_contract', {modal: true, title : SUGAR.language.getString('LBL_PRODUCTS_BUTTON_LABEL')});
		        popup.fetchContent('async.php', {postData : form_params}, null, true);
        show_calendar
            type: button
            order: 69
            widget: CalendarButton
            hidden: ! bean.date_starting
            date_field: date_starting
	sections
		--
			id: main
			elements
                - name
                - amount
                - account
                - date_starting
                - project_type
                - date_ending
                - lead_source
                - project_phase
                - assigned_user
                - date_modified
                - use_timesheets
                - date_entered
                - percent_complete
                -
                --
                    name: description
                    colspan: 2            
		--
			id: project_financial
			vname: LBL_FINANCIAL_INFORMATION
			widget: FinancialWidget
    subpanels
        - project_tasks
        - activities
        - history
		- documents
        - contacts
        - opportunities
		- assets
        - supportedassemblies
        - booked_hours
        - invoices
        - threads
        - expensereports
        - securitygroups
