<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
        next
            vname: LBL_CREATE_BUTTON_LABEL
            async: false
            type: button
            onclick: return sListView.adminCreateLayout({FORM});
    disabled_buttons
        - save
        - cancel
	sections
		--
			id: main
			elements
				- search_module
