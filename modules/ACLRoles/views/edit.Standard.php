<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	form_buttons
	sections
		--
			id: main
			elements
				--
					name: name
					required: true
                -
				- description
                -
		--
			id: acl_roles
			vname: LBL_EDIT_VIEW_DIRECTIONS
			widget: RolesWidget
