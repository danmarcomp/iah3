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
                	width: 60
                	colspan: 2
                - date_due
                - status
                - date_start
                - parent
                - effort_estim
                - contact
                - effort_actual
                - priority
                - assigned_user
                - is_private
                --
                	name: description
                	colspan: 2
