<?php

require_once 'include/layout/forms/FormField.php';
require_once 'include/database/ListQuery.php';

class ResourcesList extends FormField
{
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
		$lq = new ListQuery('Meeting', null, array('link_name' => 'resources'));
		$lq->setParentKey($row_result->getField('id'));
		$lq->setOrderBy('');
		$lq->addFields(array('id', 'name'));
		$res = $lq->runQuery();
		$rows = $res->getRows();

		if (!$rows) return null;

		$ret = '';

		foreach ($rows as $row) {
			if ($ret) $ret .= ', ';
			$ret .= '<a href="index.php?module=Resources&action=DetailView&record=' . $row['id'] . '" class="tabDetailViewDFLink">';
			$ret .= '<div class="theme-icon bean-Resource"></div>&nbsp;';
			$ret .= $row['name'];
			$ret .= '</a>';
		}
		return $ret;
	}
}
