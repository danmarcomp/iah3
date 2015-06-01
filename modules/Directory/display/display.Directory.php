<?php return; /* no output */ ?>

list
	title: LBL_LIST_FORM_TITLE
	default_order_by: name
    layouts
        Detailed
            vname: LNK_DETAILED_LAYOUT
            view_name: Detailed
basic_filters
    in_directory
        hidden: true
        default_value: 1
    last_name
        basic_type: alphabet
filters
    any_phone
        vname: LBL_ANY_PHONE
        type: phone
        fields
            - phone_mobile
            - phone_work
            - phone_other
            - phone_fax
    any_email
        vname: LBL_ANY_EMAIL
        type: email
        fields
            - email1
            - email2
    last_name
        match: prefix
