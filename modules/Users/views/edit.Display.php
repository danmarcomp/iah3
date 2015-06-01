<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
	form_buttons
    form_hidden_fields
        display_tabs_def:
        hide_tabs_def:
	sections
        --
            id: locale_settings
            vname: LBL_USER_SETTINGS
            widget: UserInfoWidget
        --
            id: layout_settings
            vname: LBL_LAYOUT_OPTIONS
            widget: UserInfoWidget
