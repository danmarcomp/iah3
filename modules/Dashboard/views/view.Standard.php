<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
		edit_layout
			icon: icon-editlayout
			vname: LBL_EDIT_LAYOUT_LABEL
			url: index.php?module=Home&edit=page&layout={RECORD}
			order: 10
			acl: edit
	sections
		--
			id: main
			elements
				- display_name
				- section
				- position
				- tab_order
				- published
				- assigned_user
				- flat_tab
				--
					name: shared_with
					widget: SharedWithInput
				--
					name: description
					colspan: 2
	subpanels
		- securitygroups
