<?php return; /* no output */ ?>

detail
    type: editview
    title: LBL_MODULE_TITLE
layout
	sections
		--
			elements
				--
					name: name
					colspan: 2
					required: true
				- parent
				- contact
				- assigned_user
                -
				- filename
                -
				--
					name: description
					colspan: 2
