<?php return; /* no output */ ?>

detail
    type: view
    title: LBL_MODULE_TITLE
layout
	sections
		--
			id: main
			elements
				- document_name
				- document_revision
				- department
				- status_id
				- category_id
				- keywords
				- subcategory_id
				- revision_date
				- revision_creator
				- active_date
				-
				- exp_date
				- related_doc
                - date_entered
				- related_doc_rev
                - date_modified
		--
			id: info
			elements
                --
                	name: revision_filename
                	colspan: 2
                --
                    name: description
                    colspan: 2
    subpanels
        - revisions
        - related_documents
        - securitygroups
