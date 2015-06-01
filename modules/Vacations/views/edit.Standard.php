<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE	
layout
    scripts
        --
            file: "modules/Vacations/vacation.js"
    form_hooks
    	oninit: vacationInit({FORM})
	sections
		--
			id: main
			elements
                - assigned_user
                - status
                - leave_type
                - days
                - date_start
                - date_end
                --
                    name: description
                    colspan: 2
