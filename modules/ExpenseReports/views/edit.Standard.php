<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    form_buttons
        submit
            vname: LBL_SUBMIT_BUTTON_LABEL
            accesskey: LBL_SUBMIT_BUTTON_KEY
            type: submit
			acl: edit
            onclick: "this.form.status_action.value='submit';SUGAR.ui.sendForm(this.form, {record_perform:'save'}, null);this.form.status_action.value='0';return false;"
			hidden
				type: hook
				hook
					file: modules/ExpenseReports/ExpenseReport.php
					class: ExpenseReport
					class_function: actionButtonDisabled
					action: submit
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
                - advance_currency
                - date_submitted
                - advance
                - date_paid
                - currency
                - date_approved
                - total_pretax
                - approved_user
                - taxes
                - balance
                - total_amount
                - 
                --
                    name: description
                    colspan: 2
        - expense_items
