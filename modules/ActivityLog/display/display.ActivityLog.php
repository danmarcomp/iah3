<?php return; /* no output */ ?>

list
	default_order_by: date_entered desc
    show_create_button: false
    show_edit_view_links: false
    show_additional_details: false
    no_mass_panel: true
    field_formatter
        file: modules/ActivityLog/ActivityListFieldFormatter.php
        class: ActivityListFieldFormatter
basic_filters
    module_name
		options_function: [ActivityLog, get_search_module_options]
fields
    activity_text
        vname: LBL_ACTIVITY_LOG_TEXT
        vname_module: ActivityLog
        type: varchar
        source
            fields: [module_name, record_item, status, moved_to, assigned_user, converted_to]
        reportable: false
