<?php return; /* no output */ ?>

detail
	type: list
layout
    columns
        - document_name
        --
            field: category_id
            add_fields
                --
                    field: subcategory_id
                    list_format: hyphen
        - related_user
        - document_revision
        --
            field: document_revision.created_by_user
            vname: LBL_LIST_LAST_REV_CREATOR
        --
            field: document_revision.date_entered
            vname: LBL_LIST_LAST_REV_DATE
            format: date_only
        - active_date
