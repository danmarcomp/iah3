<?php return; /* no output */ ?>

list
	default_order_by: account
	massupdate_handlers
		--
			name: HandleMassUpdate
			class: Project
			file: modules/Project/Project.php
hooks
    edit
        --
            class_function: init_form
auto_filters
    account_id
filters
	name
	status
	product_category_id
		operator: =
		options_function: get_search_categories
		default_value: ""
	product_type_id
		operator: =
		options_function: get_search_types
		default_value: ""
	manufacturer_name
		vname: LBL_PRODUCT_MANUFACTURER_NAME
		field: manufacturers.name
	account_name
		vname: LBL_ACCOUNT_NAME
		field: accounts.name
	supplier_name
		vname: LBL_PRODUCT_SUPPLIER_NAME
		field: suppliers.name
	model_name
		vname: LBL_MODEL_NAME
		field: models.name
	serial_no
		vname: LBL_SEARCH_SERIAL_NO
		field: serial_numbers.serial_no
	vendor_part_no
	current_user_only
		my_items: true
		vname: LBL_CURRENT_USER_FILTER
		field: assigned_user_id
fields
	weight
		vname: LBL_PRODUCT_WEIGHT
		type: weight
		source
            fields: [weight_1, weight_2]
    product_name
        vname: LBL_PRODUCT_NAME
        type: ref
        id_name: name
        bean_name: Product
        allow_custom: true
        allow_rename: true
        onchange: set_from_main
        required: true
        source
            type: name
