<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- document_ref
				- document_revision
				- revision
				- created_by_user
                - date_modified
                - date_entered
				--
					name: filename
					colspan: 2
				--
					name: change_log
					colspan: 2
