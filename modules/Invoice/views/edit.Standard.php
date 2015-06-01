<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	sections
		--
			id: main
			elements
				- name
				- opportunity
				- full_number
				- cancelled
				- event
				- shipping_stage
                - from_quote
				- amount_due
				- from_so
				- partner
				- purchase_order_num
				--
                    name: terms
                    onchange: setDateFromTerm(this.form);
				- assigned_user
                --
                    name: invoice_date
                    onchange: setDateFromTerm(this.form);
                -
                - due_date
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
