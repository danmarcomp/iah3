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
        - document_revision
        --
            field: document_revision.created_by_user
            vname: LBL_LIST_LAST_REV_CREATOR
            vname_module: Documents
        --
            field: document_revision.date_entered
            vname: LBL_LIST_LAST_REV_DATE
            vname_module: Documents
            format: date_only
        - active_date
