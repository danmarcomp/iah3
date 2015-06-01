<?php return; /* no output */ ?>

list
	default_order_by: booking_category
filters
	booking_category_id
		operator: =
		options_function: getSearchNameOptions
		options_add_blank: true
	account_name
		field: accounts.name
widgets
	SvcFrequencyWidget
		type: field
		path: modules/MonthlyServices/widgets/SvcFrequencyWidget.php
