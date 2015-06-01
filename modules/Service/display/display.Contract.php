<?php return; /* no output */ ?>

list
    show_favorites: true
hooks
    view
        --
            class_function: add_view_popups
            required_fields: [account_id]
filters
	contract_no
	account_name
		vname: LBL_ACCOUNT_NAME
		field: accounts.name
	account_phone
		vname: LBL_ACCOUNT_PHONE
        type: phone
		operator: inner_like
		field: accounts.phone_office
	is_active
		operator: =
		options_add_blank: true
fields
	status
		vname: LBL_STATUS
		type: status_color
		source
			type: subselect
			class: Contract
			function: status_subselect
	total_purchase
		vname: LBL_SUBC_TOTAL_PURCHASE
		type: formula
		formula
			type: function
			class: Contract
			function: calc_total_purchase
			fields: [id]
