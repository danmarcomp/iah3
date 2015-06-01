<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
    scripts
        --
            file: "modules/PurchaseOrders/po.js"
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
				-
		--
			id: address_info
			elements
				--
                    name: drop_ship
                    onchange: addDropShip();
                -
				- shipping_address
                -
		--
			id: price_ship
			column_titles
				- LBL_PRICING_INFO
				- LBL_SHIPPING_INFO
			elements
				- currency
				- shipping_provider
				- tax_information
				-
		- line_items
        --
            id: pdf_print
            vname: LBL_PDF_OUTPUT_OPTIONS
            elements
                - show_components
                -
		--
			id: other
			vname: LBL_DESCRIPTION_INFORMATION
			elements
				--
					name: description
					colspan: 2
