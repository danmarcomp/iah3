<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
    form_autofocus_fields: amount
	sections
		--
            id: main
            elements
            	- full_number
            	--
                    name: account
                    onchange: PaymentEditor.changeAccountFilter(this.getValue(), this.form);
                - amount
                - currency
                - payment_type
                - customer_reference
                - payment_date
                - assigned_user
                --
                    name: notes
                    colspan: 2
        --
            id: payment_items
            widget: PaymentItemsWidget
