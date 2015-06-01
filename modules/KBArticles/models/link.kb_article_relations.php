<?php return; /* no output */ ?>

detail
	type: link
	table_name: kb_article_relations
	primary_key: [relation_type, relation_id, article_id]
fields
	app.date_modified
	app.deleted
	article
		required: true
		type: ref
		bean_name: KBArticle
	relation
		vname: LBL_RELATION_ID
		required: true
		type: ref
		dynamic_module: relation_type
	relation_type
		vname: LBL_RELATION_TYPE
		type: module_name
		required: true
indices
	kb_article_relations_aid
		fields
			- article_id
relationships
	kb_articles_cases
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: article_id
		join_key_rhs: relation_id
		relationship_role_column: relation_type
		relationship_role_column_value: Cases
		lhs_bean: KBArticle
		rhs_bean: aCase
	kb_articles_documents
		lhs_key: id
		rhs_key: id
		relationship_type: many-to-many
		join_key_lhs: article_id
		join_key_rhs: relation_id
		relationship_role_column: relation_type
		relationship_role_column_value: Documents
		lhs_bean: KBArticle
		rhs_bean: Document
	kb_articles_notes
		lhs_key: id
		rhs_key: parent_id
		relationship_type: one-to-many
		relationship_role_column: parent_type
		relationship_role_column_value: KBArticles
		lhs_bean: KBArticle
		rhs_bean: Note
