<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        print_bill
        	icon: theme-icon bean-Bill
            vname: LBL_PDF_BILL
            type: button
            widget: PdfButton
            group: print
	sections
		--
			id: main
			elements
				- name
				- supplier
				- full_number
				- supplier_contact
				- invoice_reference
				- 
				- from_purchase_order
				- cancelled
				- amount
				- bill_date
				- amount_due
				- terms
				- assigned_user
				- due_date
				- tax_information
                - date_entered
                -
                - date_modified
				--
					name: description
					colspan: 2

		- line_items
    subpanels
        - activities
        - history
        - payments
        - securitygroups
