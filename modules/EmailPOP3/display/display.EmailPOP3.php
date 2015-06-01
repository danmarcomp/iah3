<?php return; /* no output */ ?>

list
	layouts
		Browse
			vname: LBL_GROUP_MAILBOXES
			override_filters
				group_inbox: 1
		User
			vname: LBL_USER_MAILBOXES
			override_filters
				group_inbox: 0
hooks
    edit
        --
            class_function: init_js
filters
	group_inbox
		vname: LBL_GROUP_OR_USER
		type: section
		options
			0
				vname: LBL_USER_MAILBOXES
				field: user_id
				operator: !=
				value: -1
			1
				vname: LBL_GROUP_MAILBOXES
				field: user_id
				operator: =
				value: -1
		default_value: 1
auto_filters
	group_inbox
