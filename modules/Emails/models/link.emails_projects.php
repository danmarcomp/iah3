<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_projects
	primary_key: [email_id, project_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	project
		type: ref
		bean_name: Project
indices
	idx_project_email_project
		fields
			- project_id
relationships
	emails_projects_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: project_id
		lhs_bean: Email
		rhs_bean: Project
