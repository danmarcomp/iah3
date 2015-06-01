<?php return; /* no output */ ?>

list
	default_order_by: name
fields
	rate_or_amount
		vname: LBL_LIST_RATE
		source
			type: rate_or_amount
			fields: [rate, discount_type, fixed_amount, fixed_amount_usdollar, currency_id, exchange_rate]		
