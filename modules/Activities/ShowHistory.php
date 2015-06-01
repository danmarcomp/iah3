<?php

require_once 'include/ListView/ListViewManager.php';
require_once 'include/database/ListQuery.php';

$parent_id = array_get_default($_REQUEST, 'parent_id');
$parent_type = array_get_default($_REQUEST, 'parent_type', 'Accounts');
if (array_get_default($_REQUEST, 'total_history')) {
	$lq = getHistoryQuery($parent_id);
	$total_history = true;
} else {
	$lq = getSummaryQuery($parent_type, $parent_id);
	$total_history = false;
}
if(! $lq)
	return;

$parent = ListQuery::quick_fetch_row(AppConfig::module_primary_bean($parent_type), $parent_id, array('name'));

$lv = new ListViewManager('listview', true);
$lv->show_create_button = false;
$lv->show_mass_update = false;
$lv->show_checks = false;
$lv->show_filter = false;
$lv->show_help = false;
$lv->custom_icon = $parent_type;

$title = AppConfig::setting("display.list.{$parent_type}.title", 'LBL_MODULE_TITLE');
$title = translate($title, $parent_type);
$lv->custom_title_html = to_html($title) . translate('LBL_SEPARATOR', 'app') . $parent['name'];

$lv->hide_navigation_controls = true;
$lv->no_sort = true;
unset($_REQUEST['list_limit']);
$lv->default_list_limit = -1;
$lv->loadRequest();

if(! $lv->initModuleView('Activities', 'Summary', null, null, null, 'ActivityHistory'))
	ACLController::displayNoAccess();
$lv->show_tabs = false;
$lv->getFormatter()->setQuery($lq);


if (!empty($_REQUEST['print'])) {
	$lv->initFormatter($lv->getFormatter());
	require_once('include/ListView/ListViewExporter.php');
	$export = new ListViewExporter('pdf');
	$export->outputFormatter($lv->getFormatter(), 'all');
	exit;
}

$url = "async.php?print=1&module=Activities&action=ShowHistory&parent_id=$parent_id&parent_type=$parent_type&total_history=".($total_history ? "1" : "");
$buttons = array(
	'print'  => array (
		'vname' => 'LBL_PDF_PDF',
		'vname_module' => 'app',
		'params' => array (
			'toplevel' => true,
			'icon' => 'icon-print',
			'perform' => 'window.open("' . $url . '", "_blank")',
		)
	)
);
$lv->getFormatter()->addButtons($buttons);

$lv->render();

function getHistoryQuery($parent_id)
{
	$parent_links = array(
		'contacts' => 'Contacts',
		'opportunities' => 'Opportunities',
		'cases' => 'Cases',
		'bugs' => 'Bugs',
		'project' => 'Project'
	);

	$parent_ids = array();
	$by_contact = array();

	foreach ($parent_links as $link_name => $parent_type) {
		$lq = new ListQuery('Account', null, array('link_name' => $link_name));
		$lq->addFields(array('id'));
		$lq->setParentKey($parent_id);
		$result = $lq->runQuery();
		$parent_ids[$link_name] = $lq->getResultIds($result);
	}

	$subquery_params = array(
		'meetings' => array(
			'clauses' => array(
				array('field' => 'status', 'value' => 'Planned', 'operator' => '!='),
			),
			'model_name' => 'Meeting',
			'module_name' => 'Meetings',
		),
		'calls' => array(
			'clauses' => array(
				array('field' => 'status', 'value' => 'Planned', 'operator' => '!='),
			),
			'model_name' => 'Call',
			'module_name' => 'Calls',
		),
		'tasks' => array(
			'clauses' => array(
				array('field' => 'status', 'operator' => '!=', 'value' => array('Not Started', 'In Progress', 'Pending Input')),
			),
			'parent' => 'contact_id',
			'model_name' => 'Task',
			'module_name' => 'Tasks',
		),
		'emails' => array(
			'model_name' => 'Email',
			'module_name' => 'Emails',
		),
		'notes' => array(
			'parent' => 'contact_id',
			'model_name' => 'Note',
			'module_name' => 'Notes',
			'no_status' => true,
		),
	);

	foreach ($subquery_params as $link_name => $params) {
		$lq = new ListQuery('Contact', null, array('link_name' => $link_name));
		$lq->addFields(array('id'));
		if (isset($params['parent'])) {
			$lq->addSimpleFilter($params['parent'], $parent_ids['contacts']);
		} else {
			$lq->addSimpleFilter('~join.contact_id', $parent_ids['contacts']);
		}
		if (isset($params['clauses'])) {
			$lq->addFilterClauses($params['clauses']);
		}
		$result = $lq->runQuery();
		$by_contact[$link_name] = $lq->getResultIds($result);
	}


	$lq = new ListQuery('ActivityHistory');

	foreach ($subquery_params as $link_name => $params) {
		if (isset($params['clauses'])) {
			foreach ($params['clauses'] as $clause) {
				$lq->addFilterClause($clause, $link_name);
			}
		}
		$clauses = array(
			array('field' => 'parent_id', 'value' => $parent_id),
			array('field' => 'id', $by_contact[$link_name]),
		);
		$lq->addField('module_name');
		foreach ($parent_ids as $parent_link => $ids) {
			if ($parent_link == 'contacts') continue;
			if (!empty($ids)) {
				$clauses[] = array(
					'multiple' => array(
						array('field' => 'parent_id', 'value' => $ids),
						array('field' => 'parent_type', 'value' => $parent_links[$parent_link]),
					),
				);
			}
		}
		$lq->addFilterClause(array('operator' => 'OR', 'multiple' => $clauses), $link_name);
	}
	$lq->setOrderBy('activity_date DESC');
	return $lq;
}

function &getSummaryQuery($parent_type, $parent_id)
{
	$lq = null;
	$bean_name = AppConfig::module_primary_bean($parent_type);
	if($bean_name) {
		$lq = new ListQuery($bean_name, null, array('link_name' => 'history', 'parent_key' => $parent_id));
		$lq->addField('module_name');
	}
	return $lq;
}

