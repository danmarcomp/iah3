<?php return; /* no output */ ?>

list
	default_order_by: name
    show_favorites: true
    layouts
        HR
            vname: LBL_MODULE_NAME_HR
            view_name: HR
            override_filters
                section: hr
                show_hr: true
                only_generic_docs
                    hidden: false
hooks
    edit
        --
            class_function: init_form
auto_filters
    show_hr
    section
basic_filters
    show_hr
        hidden: true
    only_generic_docs
        hidden: true
filters
	document_name
	category_id
	subcategory_id
	active_date
	exp_date
	department
	keywords
		where_function: get_keyword_where
    show_hr
        default_value: false
        type: flag
        negate_flag: true
        vname: LBL_VIEW_CLOSED_ITEMS
        operator: OR
        multiple
            --
                field: section
                operator: !=
                value: hr
            --
                field: section
                operator: "null"
fields
    related_user
        vname: LBL_RELATED_USER
        widget: DocumentUserWidget
    only_generic_docs
        widget: DocumentUsersFilter
widgets
    DocumentUserWidget
        type: column
        path: modules/Documents/widgets/DocumentUserWidget.php
    DocumentUsersFilter
        type: field
        path: modules/Documents/widgets/DocumentUsersFilter.php

