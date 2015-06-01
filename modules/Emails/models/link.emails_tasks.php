<?php return; /* no output */ ?>

detail
	type: link
	table_name: emails_tasks
	primary_key: [email_id, task_id]
fields
	app.date_modified
	app.deleted
	email
		type: ref
		bean_name: Email
	task
		type: ref
		bean_name: Task
indices
	idx_task_email_task
		fields
			- task_id
relationships
	emails_tasks_rel
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: email_id
		join_key_rhs: task_id
		lhs_bean: Email
		rhs_bean: Task
