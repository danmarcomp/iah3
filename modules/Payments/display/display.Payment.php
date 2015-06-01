<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
    layouts
        Incoming
            vname: LBL_INCOMING_PAYMENTS
            override_filters
                direction: "incoming"
        Outgoing
            vname: LBL_OUTGOING_PAYMENTS
            override_filters
                direction: "outgoing"
        Credit
            vname: LBL_CREDIT_PAYMENTS
            override_filters
                direction: "credit"
view
	pdf_manager
		file: modules/Payments/PaymentPDF.php
		class: PaymentPDF
filters
	payment_id
	payment_date
	account_name
		vname: LBL_ACCOUNT_NAME
		field: accounts.name
widgets
	PaymentItemsWidget
		type: section
		path: modules/Payments/widgets/PaymentItemsWidget.php
