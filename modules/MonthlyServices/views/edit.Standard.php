<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
    scripts
        --
            file: "modules/MonthlyServices/services.js"
    form_hooks
    	oninit: initRecSvcs({FORM})
	sections
		--
			id: main
			elements
                - instance_number
                - address
                - booking_category
                - start_date
                - account
                - end_date
				- quote
                - paid_until
                --
                	name: frequency
                	widget: SvcFrequencyWidget
                - billing_day
                - invoice_terms
                - assigned_user
                - invoice_value
                - purchase_order_num
                - total_sales
                - contact
                - balance_due
                - cc_user
