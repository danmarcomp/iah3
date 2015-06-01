<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        receive
            vname: LBL_RECEIVE_BUTTON_LABEL
            accesskey: LBL_RECEIVE_KEY
            url: ?module=Receiving&action=EditView&purchaseorder_id={RECORD}&{RETURN}&return_panel=receiving
        bill
        	icon: theme-icon create-Bill
            vname: LBL_CONVERT_BILL_BUTTON_LABEL
            accesskey: LBL_CONVERT_BILL_BUTTON_KEY
            url: ?module=Bills&action=EditView&from_purchaseorder_id={RECORD}&{RETURN}&return_panel=bills
            group: convert
        print_order
        	icon: theme-icon bean-PurchaseOrder
            vname: LBL_PDF_PURCHASEORDER
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
				- related_invoice
                - cancelled
				- assigned_user
				- terms
				- shipping_stage
				- from_so
                - drop_ship
                -
				--
                    name: shipping_address
                    hidden: ! bean.drop_ship
                -
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
        - receiving
        - bills
        - securitygroups
