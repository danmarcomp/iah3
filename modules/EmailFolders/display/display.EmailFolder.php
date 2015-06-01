<?php return; /* no output */ ?>

list
    default_order_by: name
    layouts
        Browse
            vname: LBL_PERSONAL_FOLDERS
            override_filters
                section
                    value: personal
                    hidden: true
        GroupFolders
            vname: LBL_GROUP_FOLDERS
            override_filters
                section
                    value: group
                    hidden: true
            hidden: ! mode.admin
hooks
	edit
		--
			class_function: set_user
			required_fields: [user_id]
basic_filters
    section
    personal_user
        hidden: true
filters
    section
        name: section
        default_value: personal
        type: section
        options
            personal
                vname: LBL_PERSONAL_FILTER
            group
                vname: LBL_GROUP_FILTER
    personal_user
        type: section_ref
        id_name: user_id
        options_function: [EmailFolder, get_user_options]
        filter_args: [section]
        default_value:
