<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        print_credit
        	icon: theme-icon bean-CreditNote
            vname: LBL_PDF_CREDIT
            type: button
            widget: PdfButton
            group: print
        print_statement
        	icon: theme-icon bean-CreditNote
            vname: LBL_PDF_STATEMENT
            type: button
            widget: PdfButton
            group: print
            params
            	pdf_type: statement
        receive
            vname: LBL_RECEIVE_BUTTON_LABEL
            accesskey: LBL_RECEIVE_KEY
            url: ?module=Receiving&action=EditView&credit_id={RECORD}&{RETURN}&return_panel=receiving
	sections
		--
            id: main
            elements
                --
                    name: name
                    vname: LBL_CREDIT_SUBJECT
                - opportunity
                --
                    name: full_number
                    vname: LBL_CREDIT_NUMBER
                - invoice
                - assigned_user
                - apply_credit_note
                - due_date
                - cancelled
				--
					name: amount_due
					hidden: bean.apply_credit_note
                - date_entered
                - tax_information
                - date_modified
                - billing_address
                -
                --
                    name: description
                    colspan: 2
		- line_items
    subpanels
		--
			name: payments
			hidden: bean.apply_credit_note
        - receiving
        - securitygroups
