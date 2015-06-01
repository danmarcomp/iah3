<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        subscribe
            vname: LBL_SUBSCRIBE
            type: button
			url: ?module=Threads&action=Subscribe&record={RECORD}&{RETURN}&return_panel=posts&return_layout=Related
			hidden: bean.current_user_subscribed
        unsubscribe
            vname: LBL_UNSUBSCRIBE
            type: button
			url: ?module=Threads&action=Subscribe&record={RECORD}&{RETURN}&return_panel=posts&return_layout=Related
			hidden: !bean.current_user_subscribed
	sections
		--
			id: main
			elements
                - user_photo
				- title
				- forum
				- date_entered
				--
					name: description_html
					colspan: 2
	subpanels
		- opportunities
		- accounts
		- project
		- cases
		- bugs
