<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
	sections
		--
			id: main
			elements
				- name
                - supplier
				- full_number
				- receiving_stage
                - assigned_user
                - date_shipped
				- po
                -
				- packing_slip_num
                - warehouse
                - receiving_cost
                - num_packages
                - #credit_memo (FIXME issue with po_id)
                -
		--
			id: price_ship
			column_titles
				- LBL_PRICING_INFO
				- LBL_SHIPPING_INFO
			elements
				- currency
				- shipping_provider
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
				- description
