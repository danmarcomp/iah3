<?php return; /* no output */ ?>

list
	default_order_by: date_start DESC
	no_mass_delete: true
	buttons_hook
		class_function: mutateListButtons
		class: Email
		file: modules/Emails/Email.php
	massupdate_handlers
		--
			name: trash
			class: Email
			file: modules/Emails/Email.php
			class_function: massupdate_trash
			acl: edit
			require_fields: [assigned_user, folder, folder_ref.reserved]
	layouts
		Browse
			vname: LBL_MODULE_PART_TITLE_PERSONAL
			override_filters
				section
					value: personal
					hidden: true
				filter_owner
					value: mine
					hidden: true
		Group
			vname: LBL_MODULE_PART_TITLE_GROUP
			override_filters
				section
					value: group
					hidden: true
				filter_owner
					hidden: true
	field_formatter
		file: modules/Emails/ListFieldFormatter.php
		class: EmailListFieldFormatter
edit
	add_conversion_fields
		- reply_to
		- reply_all
	redirect_action: index
hooks
	view
		--
			class_function: mark_read_on_view
			required_fields: [isread]
	after_edit
		--
			class_function: edit_redirect			
basic_filters
	section
	folder_ref
	only_unread
filters
	section
		name: section
		vname: LBL_EMAIL_SECTION
		default_value: personal
		type: section
		options
			personal
				vname: LBL_MODULE_PART_TITLE_PERSONAL
			group
				vname: LBL_MODULE_PART_TITLE_GROUP
	folder_ref
		type: section_ref
		options_function: [EmailFolder, get_filter_options]
		filter_args: [section]
		default_value:
		auto_suppress: true
		required: true
	only_unread
		default_value: false
		type: flag
		negate_flag: false
		vname: LBL_ONLY_UNREAD
		operator: eq
        value: 0
		field: isread
	unread_only
		vname: LBL_ONLY_UNREAD
		operator: !=
		field: isread
fields
	in_reply_to
		widget: EmailInReplyToWidget
	contact
		widget: EmailContactWidget
    parent
        widget: EmailParentWidget
widgets
	EmailWidget
		type: section
		path: modules/Emails/widgets/EmailWidget.php
	EmailInReplyToWidget
		type: field
		path: modules/Emails/widgets/EmailInReplyToWidget.php
	EmailContactWidget
		type: column
		path: modules/Emails/widgets/EmailContactWidget.php
    EmailParentWidget
        type: column
        path: modules/Emails/widgets/EmailParentWidget.php
