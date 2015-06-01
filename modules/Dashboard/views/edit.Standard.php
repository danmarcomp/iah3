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
					name: display_name
					editable: true
				- section
				- position
				- tab_order
				- published
				--
					name: assigned_user
					editable: mode.admin
				- flat_tab
				--
					name: shared_with
					editable: mode.admin
					widget: SharedWithInput
				--
					name: description
					colspan: 2
