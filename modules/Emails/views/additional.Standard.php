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
				--
					name: from_name
					vname: LBL_FROM
				- type
                - body.description
