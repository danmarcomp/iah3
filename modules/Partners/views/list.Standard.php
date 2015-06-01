<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        --
            field: name
            add_fields: [code]
        - related_account
        - date_start
        - date_end
        - assigned_user
