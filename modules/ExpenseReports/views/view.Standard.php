<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
    buttons_hook
        class: ExpenseReport
        file: modules/ExpenseReports/ExpenseReport.php
        class_function: mutateDetailButtons
	form_buttons
        unsubmit
            vname: LBL_UNSUBMIT_BUTTON_LABEL
            accesskey: LBL_UNSUBMIT_BUTTON_KEY
            type: submit
			acl: edit
            onclick: "this.form.status_action.value='unsubmit';this.form.action.value='ChangeStatus';"
			hidden
				type: hook
				hook
					file: modules/ExpenseReports/ExpenseReport.php
					class: ExpenseReport
					class_function: actionButtonDisabled
					action: unsubmit
        approve
            vname: LBL_APPROVE_BUTTON_LABEL
            accesskey: LBL_APPROVE_BUTTON_KEY
            type: submit
            onclick: "this.form.status_action.value='approve';this.form.action.value='ChangeStatus';"
			hidden
				type: hook
				hook
					file: modules/ExpenseReports/ExpenseReport.php
					class: ExpenseReport
					class_function: actionButtonDisabled
					action: approve
        reject
            vname: LBL_REJECT_BUTTON_LABEL
            accesskey: LBL_REJECT_BUTTON_KEY
            type: submit
            onclick: "this.form.status_action.value='reject';this.form.action.value='ChangeStatus';"
			hidden
				type: hook
				hook
					file: modules/ExpenseReports/ExpenseReport.php
					class: ExpenseReport
					class_function: actionButtonDisabled
					action: reject
        pay
            vname: LBL_PAY_BUTTON_LABEL
            accesskey: LBL_PAY_BUTTON_KEY
            type: submit
            onclick: "this.form.status_action.value='pay';this.form.action.value='ChangeStatus';"
			hidden
				type: hook
				hook
					file: modules/ExpenseReports/ExpenseReport.php
					class: ExpenseReport
					class_function: actionButtonDisabled
					action: pay
        print_report
        	icon: theme-icon bean-ExpenseReport
            vname: LBL_PDF_REPORT
            type: button
            widget: PdfButton
            group: print
    form_hidden_fields
        status_action: 0 
	sections
		--
			elements
				- full_number
                - assigned_user
                - account
                - parent
                - type
                - status
                - date_submitted
                - date_approved
                - date_paid
                - approved_user
                - total_pretax
                - advance
                - tax
                - balance
                - total_amount
				--
					name: description
					colspan: 2
        - expense_items
    subpanels
        - notes
