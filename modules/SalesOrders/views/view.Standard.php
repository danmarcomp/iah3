<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        convert_po
        	icon: theme-icon create-PurchaseOrder
			vname: LBL_CREATE_PURCHASE_ORDER
			url: ?module=PurchaseOrders&action=EditView&salesorder_id={RECORD}&{RETURN}&return_panel=purchaseorders
			group: convert
		convert_inv
        	icon: theme-icon create-Invoice
			vname: LBL_CREATE_INVOICE
			url: ?module=Invoice&action=EditView&salesorder_id={RECORD}&{RETURN}&return_panel=invoice
			group: convert
		convert_proj
        	icon: theme-icon create-Project
			vname: LBL_CONV_TO_PROJECT
			widget: ConvertProjectButton
			param_name: salesorder_id
			link_name: project
			group: convert
        convert_ship
            icon: theme-icon create-Shipping
            vname: LBL_CREATE_SHIPPING
            url: ?module=Shipping&action=EditView&salesorder_id={RECORD}&{RETURN}&return_panel=shipping
            hidden: bean.so_stage=Closed - Shipped and Invoiced
            group: convert
        print_order
        	icon: theme-icon bean-SalesOrder
            vname: LBL_PDF_CONFIRMATION
            type: button
            widget: PdfButton
            group: print
	sections
		--
			id: main
			elements
				- name
				- opportunity
				- full_number
				- so_stage
				- purchase_order_num
				- due_date
				- related_quote
				- delivery_date
				-
				- partner
				- assigned_user
				- terms
				- billing_address
				- shipping_address
                - tax_information
                - date_entered
                -
                - date_modified
				--
					name: description
					colspan: 2

		- line_items
		--
			id: pdf_print
			vname: LBL_PDF_OUTPUT_OPTIONS
			elements
				- show_components
				- show_list_prices
    subpanels
        - activities
        - history
        - invoice
        - purchaseorders
        - shipping
        - securitygroups
