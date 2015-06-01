<?php return; /* no output */ ?>

detail
	type: table
	table_name: kb_tags
	primary_key: id
fields
	app.id
	app.date_modified
	app.deleted
	tag
		type: varchar
		len: 30
links
	articles
		relationship: kb_articles_tags
		ignore_role: true
		vname: LBL_NOTES
relationships
	kb_articles_tags
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: article_id
		join_key_rhs: tag_id
		lhs_bean: KBArticle
		rhs_bean: kb_tags
		join_table: kb_articles_tags
