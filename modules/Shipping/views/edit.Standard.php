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
				- 
				- full_number
				- shipping_stage
				- purchase_order_num
				- date_shipped
				- assigned_user
				-
				- weight_1
				- weight_2
                - currency
                - invoice
                - shipping_cost
                - so
                - shipping_provider
                - warehouse
                - tracking_number
                - 
		--
			id: address_info
			elements
				- shipping_address
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
				- description
