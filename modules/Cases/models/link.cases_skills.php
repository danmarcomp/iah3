<?php return; /* no output */ ?>

detail
	type: link
	table_name: cases_skills
	primary_key
		- case_id
		- skill_id
fields
	app.date_modified
	app.deleted
	rating
		type: int
	case
		type: ref
		bean_name: aCase
	skill
		type: ref
		bean_name: Skill
indices
	idx_cas_skill_cas
		fields
			- case_id
	idx_cas_skill_skill
		fields
			- skill_id
relationships
	cases_skills
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: case_id
		join_key_rhs: skill_id
		lhs_bean: aCase
		rhs_bean: Skill
