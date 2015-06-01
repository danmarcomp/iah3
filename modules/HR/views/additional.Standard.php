<?php return; /* no output */ ?>

detail
    type: view
layout
	sections
		--
			id: mini_details
			columns: 1
            label_position: top
			elements
				- user.address_home
                - user.title
                - user.phone_mobile
                - user.phone_home
                - user.phone_work
                - user.phone_other
                - user.description
