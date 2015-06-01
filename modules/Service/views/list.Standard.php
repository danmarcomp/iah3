<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: contract_no
            add_fields
                --
                    field: status
                    list_position: prefix
        --
            field: account
            add_fields: [account.phone_office]
        --
            field: date_entered
            format: date_only
        --
            field: date_modified
            format: date_only
        - description
