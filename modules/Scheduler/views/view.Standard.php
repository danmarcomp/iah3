<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				--
					name: show_type
					colspan: 2
                - enabled
                - run_interval
                - run_on_user_login
                -
                --
                	name: status_text
                	colspan: 2
                --
                    name: show_description
                    colspan: 2
