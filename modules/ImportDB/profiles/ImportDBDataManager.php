<?php
/*
 *
 * The contents of this file are subject to the info@hand Software License Agreement Version 1.3
 *
 * ("License"); You may not use this file except in compliance with the License.
 * You may obtain a copy of the License at <http://1crm.com/pdf/swlicense.pdf>.
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for the
 * specific language governing rights and limitations under the License,
 *
 * All copies of the Covered Code must include on each user interface screen:
 * (i) the 1CRM copyright notice,
 * (ii) the "Powered by the 1CRM Engine" logo, 
 *
 * (iii) the "Powered by SugarCRM" logo, and
 * (iv) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.
 * See full license for requirements.
 *
 * The Original Code is : 1CRM Engine proprietary commercial code.
 * The Initial Developer of this Original Code is 1CRM Corp.
 * and it is Copyright (C) 2004-2012 by 1CRM Corp.
 *
 * All Rights Reserved.
 * Portions created by SugarCRM are Copyright (C) 2004-2008 SugarCRM, Inc.;
 * All Rights Reserved.
 *
 */
class ImportDBDataManager {
	/**
	 * @var ImportDBImportProfile
	 */
	private $profile;
	/**
	 * This is DBManager instance actually, but for better code completion we assume that this is
	 * concrete DBManager instance.
	 *
	 * @var MysqlManager
	 */
	private $db;
	/**
	 * @var array
	 */
	private $mod_strings = array();

	private $list_limit = 20;
	private $list_offset = 0;
	private $list_page = 0;
	private $list_order_by = 'date_modified';
	private $list_order_dir = 'ASC';
	private $list_total = 0;
	private $list_result = null;

	/**
	 * Constructor
	 *
	 * @param ImportDBImportProfile $profile
	 * @return void
	 */
	public function __construct(ImportDBImportProfile &$profile) {
		$this->profile = $profile;
		$this->db = DBManagerFactory::getInstance();
		$this->mod_strings = return_module_language('', 'ImportDB');
	}

	/**
	 * Prepares list for data management
	 *
	 * @throws BadMethodCallException
	 * @param string $module
	 * @param bool $allow_abstract
	 * @return int
	 */
	public function fetchList($module, $allow_abstract = false) {
		$ent_data = $this->profile->getAvailableModules($module, null);
		$ent_bean_name = AppConfig::module_primary_bean($module);
		$model = new ModelDef($ent_bean_name);

		if (!$allow_abstract && empty($ent_data['module'])) return false;

		$query = $this->buildListQuery($module, $model->table_name, $ent_data['title_field']);
		return $this->retreiveList($query);
	}

	/**
	 * Parses items list and pager data
	 *
	 * @param XTemplate $xtpl
	 * @param string $module
	 * @return void
	 */
	public function parseList(XTemplate &$xtpl, $module) {
		if ($this->db->getRowCount($this->list_result)) {
			$parity = true;
			while ($row = $this->db->fetchByAssoc($this->list_result)) {
				$row['html_parity'] = $parity ? 'odd' : 'even';

				$xtpl->assign('ROW', $row);
				$xtpl->parse('main.row');

				$parity = !$parity;
			}
		} else {
			// if current page does not have records - go to previous untill first page reached
			if (!$this->list_page) {
				$xtpl->parse('main.empty_result');
			} else {
				$redirect = 'index.php?module=ImportDB&action=Manage&profile=' . $this->profile->getName() . '&entity=' . $module . '&pager_page=' . ($this->list_page - 1);
				header("Location: $redirect");
				die();
			}
		}

		$control_bar = '';
		if ($this->list_total) {
			$xtpl_pager = new XTemplate('modules/ImportDB/profiles/ManageListPager.html');
			$xtpl_pager->assign('MOD_STRINGS', $this->mod_strings);

			if ($this->list_offset) {
				$xtpl_pager->parse('main.start');
				$xtpl_pager->parse('main.prev');
			} else {
				$xtpl_pager->parse('main.disabled_start');
				$xtpl_pager->parse('main.disabled_prev');
			}

			$index_end = $this->list_offset + $this->list_limit;
			if ($index_end >= $this->list_total) {
				$index_end = $this->list_total;

				$xtpl_pager->parse('main.disabled_next');
				$xtpl_pager->parse('main.disabled_end');
			} else {
				$xtpl_pager->parse('main.next');
				$xtpl_pager->parse('main.end');
			}

			$xtpl_pager->assign('P', array(
				'total' => $this->list_total,
				'index_start' => $this->list_offset + 1,
				'index_end' => $index_end,
			));

			$xtpl_pager->parse('main');
			$control_bar = $xtpl_pager->text('main');
		}


		$xtpl->assign('PAGER', array(
			'control_bar' => $control_bar,
			'current_page' => $this->list_page
		));
	}

	/**
	 * Builds generic query for listing
	 *
	 * @param string $module
	 * @param string $data_table
	 * @param string|array $data_field_title
	 * @return string
	 */
	protected function buildListQuery($module, $data_table, $data_field_title) {
		$p = new ImportDBProfile();
		$e = new ImportDBModule();
		$h = new ImportDBHistory();

		$profile_table = $p->table_name;
		$module_table = $e->table_name;
		$history_table = $h->table_name;

		unset($p);
		unset($e);
		unset($h);

		$profile_name = $this->profile->getName();

		$this->list_order_by = empty($_REQUEST['order_by']) ? $this->list_order_by : $_REQUEST['order_by'];
		$this->list_order_dir = empty($_REQUEST['order_dir']) ? $this->list_order_dir : $_REQUEST['order_dir'];

		if (is_array($data_field_title)) {
			$titles = array();
			foreach ($data_field_title as $field) $titles[] = 'd.' . $field;
			$data_title = 'CONCAT_WS(" ", ' . implode(', ', $titles) . ')';
		} else {
			$data_title = 'd.' . $data_field_title;
		}

		return <<<LISTQUERY
SELECT
	{$data_title} AS data_title,
	h.id AS history_id,
	h.source_id,
	h.generated_id,
	h.date_modified
FROM {$data_table} d
INNER JOIN {$history_table} h ON d.id = h.generated_id AND h.deleted = 0
INNER JOIN {$module_table} e ON e.id = h.module_id AND e.name = "{$module}"
INNER JOIN {$profile_table} p ON p.id = e.profile_id AND p.name = "{$profile_name}"
ORDER BY {$this->list_order_by} {$this->list_order_dir}
LISTQUERY;
	}

	/**
	 * Gets total records count and list result resource
	 *
	 * @param string $query
	 * @return int
	 */
	private function retreiveList($query) {
		$count_query = preg_replace(array('/SELECT.*?FROM /As', '/ORDER BY .*/'), array('SELECT COUNT(d.id) FROM ', ''), $query);
		$this->list_total = intval($this->db->getOne($count_query));

		$this->list_page = empty($_REQUEST['pager_page']) ? 0 : intval($_REQUEST['pager_page']);

		$op = empty($_REQUEST['op']) ? '' : $_REQUEST['op'];

		// need to use stripos instead of equal sign because of IE <button type="submit"> behavior
		// see Important notice at http://www.w3schools.com/tags/tag_button.asp for details
		if (stripos($op, 'Start') !== false) $this->list_page = 0;
		if (stripos($op, 'Previous') !== false) $this->list_page--;
		if (stripos($op, 'Next') !== false) $this->list_page++;
		if (stripos($op, 'End') !== false) $this->list_page = floor($this->list_total / $this->list_limit);

		$this->list_offset = $this->list_page * $this->list_limit;
		$this->list_result = $this->db->limitQuery($query, $this->list_offset, $this->list_limit);

		return $this->list_total;
	}
}
