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
				- opportunity
				- full_number
				- quote_stage
				- purchase_order_num
				- valid_until
				- assigned_user
				- terms
				- budgetary_quote
				- partner
		--
			id: address_info
			elements
				--
					name: billing_address
					copy_to: shipping_address
				--
					name: shipping_address
					copy_to: billing_address
		--
			id: price_ship
			column_titles
				- LBL_PRICING_INFO
				- LBL_SHIPPING_INFO
			elements
				- currency
				- shipping_provider
				- tax_information
				--
                    name: discount_before_taxes
                    onchange: "TallyEditor.tax_discount_changed(this);"
				--
                    name: tax_exempt
                    onchange: "TallyEditor.tax_exempt_changed(this);"
				-
				- gross_profit
				-
				- gross_profit_pc
				-
		--
			id: approval
			vdesc: LBL_APPROVAL_CAUTION
			vname: LBL_APPROVAL
			hidden
				type: hook
				hook
					class: Quote
					class_function: approvalEnabled
					file: modules/Quotes/Quote.php
			elements
				- approval
				-
				--
					name: approval_problem
					colspan: 2
		- line_items
		--
			id: pdf_print
			vname: LBL_PDF_OUTPUT_OPTIONS
			elements
				- show_components
				- show_list_prices
		--
			id: other
			vname: LBL_DESCRIPTION_INFORMATION
			elements
				--
					name: description
					colspan: 2
