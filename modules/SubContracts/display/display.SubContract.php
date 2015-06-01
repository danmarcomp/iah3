<?php return; /* no output */ ?>

list
hooks
    view
        --
            class_function: add_view_popups
            required_fields: [main_contract.account_id]
    edit
		--
			class_function: init_form
auto_filters
    main_account_id
filters
	is_active
		operator: =
		options_add_blank: true
    main_account_id
        field: main_contract.account_id
        type: id
        operator: eq
fields
    status
        vname: LBL_STATUS
        type: status_color
        source
            type: subselect
            class: SubContract
            function: status_subselect
