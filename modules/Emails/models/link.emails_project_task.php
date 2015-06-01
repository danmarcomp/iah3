<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_project_tasks
	primary_key: [email_id, project_task_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	project_task
		type: ref
		bean_name: Project
indices
	idx_ept_project_task
		fields
			- project_task_id
relationships
	emails_project_task_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: project_task_id
		lhs_bean: Email
		rhs_bean: ProjectTask
