<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	form_buttons
        subscribe
            vname: LBL_SUBSCRIBE
            type: button
			url: ?module=Threads&action=Subscribe&record={RECORD}&{RETURN}&return_panel=posts
			hidden: bean.current_user_subscribed
        unsubscribe
            vname: LBL_UNSUBSCRIBE
            type: button
			url: ?module=Threads&action=Subscribe&record={RECORD}&{RETURN}&return_panel=posts
			hidden: !bean.current_user_subscribed
	sections
		--
			id: main
			elements
                --
                	name: user_photo
                	rowspan: 2
                - forum
				- date_entered
                --
                	name: title
                	colspan: 2
				--
					name: description_html
					colspan: 2
	subpanels
		- posts
