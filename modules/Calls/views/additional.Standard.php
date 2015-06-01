<?php return; /* no output */ ?>

detail
    type: view
layout
	sections
		--
			id: mini_details
			columns: 1
            label_position: top
            hidden: bean.is_private
			elements
				- name
				- status
				- date_start
                - duration
				--
					name: invitees_list
					widget: InviteesList
                - description
