<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
    form_hidden_fields
        credit_note : 0
    sections
        --
            id: main
            elements
                --
                    name: name
                    vname: LBL_CREDIT_SUBJECT
                - cancelled
                --
                    name: full_number
                    vname: LBL_CREDIT_NUMBER
                --
                	name: amount_due
                	vname: LBL_AMOUNT
                - assigned_user
                - due_date
                - invoice
                --
                	name: apply_credit_note
                	editable: mode.new_record
                -
		--
			id: address_info
			elements
				--
					name: billing_address
        --
            id: price_ship
            column_titles
                - LBL_PRICING_INFO
            elements
                - currency
                -
                - tax_information
                --
                    name: discount_before_taxes
                    onchange: "TallyEditor.tax_discount_changed(this);"
                --
                    name: tax_exempt
                    onchange: "TallyEditor.tax_exempt_changed(this);"
                -
		- line_items
		--
			id: other
			vname: LBL_DESCRIPTION_INFORMATION
			elements
				--
					name: description
					colspan: 2
