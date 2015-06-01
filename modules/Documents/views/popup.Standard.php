<?php return; /* no output */ ?>

detail
	type: popup
layout
    columns
        --
            field: document_name
            width: 60
        --
            field: department
            width: 40
        --
            field: category_id
            add_fields
                --
                    field: subcategory_id
                    list_format: hyphen
            width: 40
        - document_revision
