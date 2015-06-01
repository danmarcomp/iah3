<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
********************************************************************************/
/*********************************************************************************

* Description:  TODO: To be written.
* Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
* All Rights Reserved.
* Contributor(s): ______________________________________..
********************************************************************************/



require_once('data/SugarBean.php');

global $predefined_charts;
require_once('modules/Charts/code/predefined_charts.php');

class Dashboard extends SugarBean {

	var $db;

	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $assigned_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $assigned_user_name;
	var $shared_with;

	var $name;
	var $section;
	var $description;
	var $content;
	var $options;
	var $published;
	var $flat_tab;
	var $position;
	var $tab_order;
	var $icon;

	var $module_dir = 'Dashboard';
	var $table_name = "dashboards";
	var $object_name = "Dashboard";
	var $new_schema = true;
	
	var $additional_column_fields = array('assigned_user_name', 'created_by_name', 'modified_by_name');
	
	static $standard_widths = array(
		1 => array('%', 100),
		2 => array('%', 60, 40),
		3 => array('%', 30, 40, 30),
		4 => array('%', 30, 20, 30, 20),
	);
	
	// not saved
	var $columns;
	var $col_count;
	var $dashlet_ids;
	var $async_display = true;
	

	function Dashboard()
	{
		parent::SugarBean();
	}
	
	function retrieve($id=-1, $enc=true) {
		$ret = parent::retrieve($id, $enc);
		if($ret)
			$this->init_from_content();
		return $ret;
	}

	function get_section_title() {
		return translate($this->section);
	}
	
	function get_title() {
		return translate($this->name);
	}
	
	function get_icon() {
		if($this->icon)
			return $this->icon;
		return 'Dashboard';
	}

	function get_summary_text()
	{
		return $this->get_title();
	}
	
	function fetch_grouped($user_id=null, $published=null) {
		$lst = $this->fetch_all($user_id, true, $published);
		$ret = array();
		if($lst === false)
			return $lst;
		else if($lst)
			foreach($lst as $obj)
				$ret[$obj->section][$obj->id] = $obj;
		return $ret;
	}
	
	function fetch_all($user_id=null, $section=null, $published=null) {
		$lq = new ListQuery('Dashboard');
		if(isset($section)) {
			if($section === true)
				$lq->addSimpleFilter('section', '', 'length');
			else
				$lq->addSimpleFilter('section', $section);
		}
		if(isset($user_id)) {
			$lq->acl_user_id = $user_id;
			$lq->acl_admin_except = false;
			$lq->addAclFilter('list');
		}
		if(isset($published))
			$lq->addSimpleFilter('published', $published ? 1 : 0);
		$lq->setOrderBy('NOT published, section, position, tab_order');
		$lst = $lq->fetchAllObjects();
		return $lst;
	}
	
	function set_content($c) {
		$this->content = base64_encode(serialize($c));
	}
	
	function set_default_content($cols=2) {
		$c = array();
		for($i = 0; $i < $cols; $i++)
			$c[] = array('dashlets' => array());
		$c = array('columns' => $c);
		$this->set_content($c);
	}
	
	function get_content() {
		// support pre-6.5 content encoding
		//if(empty($this->section)) {
		//	return unserialize(from_html($this->content));
		//}
		return unserialize(base64_decode($this->content));
	}
	
	function get_dashlet_options($dlet_id=null, $no_override=false) {
		$opts_arr = unserialize(base64_decode($this->options));
		if(! is_array($opts_arr))
			$opts_arr = array();
		$ret = null;
		if(! isset($dlet_id)) {
			if(! $no_override)
				$ret = $opts_arr;
			else {
				foreach($opts_arr as $id => $def) {
					$user_opts = $this->get_user_dashlet_options($id);
					if($user_opts)
						$ret[$id] = $user_opts;
					else
						$ret[$id] = $def;
				}
			}
		}
		else {
			$ret = array_get_default($opts_arr, $dlet_id, array());
			if(! $no_override) {
				$user_opts = $this->get_user_dashlet_options($dlet_id);
				if($user_opts)
					$ret = $user_opts;
			}
		}			
		return $ret;
	}

	function set_dashlet_options($dlet_id, $opts) {
		$opts_arr = unserialize(base64_decode($this->options));
		if(! is_array($opts_arr))
			$opts_arr = array();
		if(! isset($opts) && isset($opts_arr[$dlet_id]))
			unset($opts_arr[$dlet_id]);
		else if(isset($opts))
			$opts_arr[$dlet_id] = $opts;
		if($this->assigned_user_id == $GLOBALS['current_user']->id)
			$this->reset_user_dashlet_options($dlet_id);
		$this->options = base64_encode(serialize($opts_arr));
	}
	
	function reset_dashlet_options($dlet_id) {
		$this->set_dashlet_options($dlet_id, null);
	}
	
	
	function get_user_dashlet_options($dlet_id) {
		global $current_user;
        if($dlet_id)
			return $current_user->getPreference('dashlet_options~'.$dlet_id, 'home');
	}
	
	function set_user_dashlet_options($dlet_id, $options=null) {
        global $current_user;
        if($dlet_id) {
			$current_user->setPreference('dashlet_options~'.$dlet_id, $options, 0, 'home');	
			if(! isset($options)) {
				// clear sort order
				unset($_SESSION['dashlet_' . $dlet_id . '_order_by']);
			}
		}
	}

	function reset_user_dashlet_options($dlet_id) {
		$this->set_user_dashlet_options($dlet_id);
	}

	
	function init_from_content() {
		require_once('include/layout/DashletManager.php');
		$page_data = $this->get_content();
		$dashletsFiles = DashletManager::get_dashlet_files();
		$cols = array();
		if(! isset($page_data['columns']))
			$page_data['columns'] = array();
		foreach($page_data['columns'] as $idx => $coldata) {
			$cols[$idx] = $coldata;
			$cols[$idx]['dashlets'] = DashletManager::lookup_dashlet_files($coldata['dashlets']);
		}
		$this->columns = $cols;
		$this->col_count = count($this->columns);
	}
	
	
	function find_dashlet($id) {
		foreach($this->columns as $colNum => $column)
			foreach($column['dashlets'] as $did => $def)
				if($did == $id)
					return $def;
	}
	
	function get_dashlet_ids() {
		if(is_array($this->dashlet_ids))
			return $this->dashlet_ids;
		return array();
	}
	
	function process_dashlets($edit_layout=false) {
		$this->dashlet_ids = array(); // collect ids to pass to javascript
		$display = array();
		$this->async_display = false;
		foreach($this->columns as $colNum => $column) {
			if(isset($column['width']))
				$display[$colNum]['width'] = $column['width'];
			$display[$colNum]['dashlets'] = array(); 
			foreach($column['dashlets'] as $id => $def) {
				if(! empty($id)) {
					$data =& $this->process_dashlet_def($def, $id, $edit_layout, false, false);
					if($data) {
						$display[$colNum]['dashlets'][$id] = $data;
						$this->dashlet_ids[] = $id;
					}
				}
			}
		}
		if($edit_layout)
			$this->edit_layout = true;
		return $display;
	}
	
	function &load_dashlet($dashlet_def, $id, $width) {
		// NOTE: some dashlets include files that modify these variables,
		// so we must import them into the local scope
		global $app_strings, $dashletData;

		if(! isset($dashlet_def['fileLocation']) || ! is_file($dashlet_def['fileLocation']))
			$dashlet = null;
		else {
			require_once($dashlet_def['fileLocation']);
			$options = $this->get_dashlet_options($id);
			$options['requestedWidth'] = $width;
			$dashlet = new $dashlet_def['className']($id, $options);
			if(isset($dashlet_def['properties'])) {
				foreach($dashlet_def['properties'] as $k => $v) {
					$m = "set_{$k}";
					if(method_exists($dashlet, $m))
						$dashlet->$m($v);
					else
						$dashlet->$k = $v;
				}
			}
		}
		return $dashlet;
	}
	
	function load_dashlet_id($id) {
		$def = $this->find_dashlet($id);
		if($def)
			return $this->load_dashlet($def, $id, 0);
	}
	
	function process_dashlet_def(&$dashlet_def, $id, $sample=false, $dynamic=false, $refresh=false, $width='') {
		$ret = null;
		if( ($dashlet = $this->load_dashlet($dashlet_def, $id, $width)) ) {
			if($refresh)
				$dashlet->forceRefresh = true;
			$dashlet->asyncDisplay = $this->async_display;
			$dashlet->setWidth($width);
			if(! $sample) {
				if($dynamic && $dashlet->hasScript) {
					$dashlet->isConfigurable = false;
					$ret['display'] = $dashlet->getTitle('') . translate('LBL_RELOAD_PAGE', 'Home');
				} else {
					$dashlet->locked = true;
					$proc_response = $dashlet->process();
					if($proc_response === 'pending') {
						$ret['display'] = $dashlet->displayPending();
						$ret['script'] = $dashlet->displayPendingScript();
					}
					else if($proc_response == 'download') {
						$ret['download'] = 1;
						$ret['id'] = $dashlet->id;
						$ret['dashlet'] =& $dashlet;
					}
					else {
						$ret['display'] = $dashlet->display();
						if($dashlet->hasScript) {
							$ret['script'] = $dashlet->displayScript();
						}
					}
				}
			} else {
				$ret['display'] = $dashlet->displaySample();
				if($dashlet->hasScript) {
					$ret['script'] = $dashlet->displayScript();
				}
			}
		}
		return $ret;
	}
	
	function finish_display(&$dashlet_info) {
		$dashlet =& $dashlet_info['dashlet'];
		$ret = array(
			'display' => $dashlet->display(),
		);
		if($dashlet->hasScript) {
			$ret['script'] = $dashlet->displayScript();
		}
		return $ret;
	}
	
	function process_dashlet($id, $sample=false, $dynamic=false, $refresh=false, $width='') {
		$def = $this->find_dashlet($id);
		if($def)
			return $this->process_dashlet_def($def, $id, $sample, $dynamic, $refresh, $width);
	}
	
	
	function set_column_layout($newLayout) {
		$old_data = $this->get_content();
		$data = $old_data;
		$data['columns'] = array();
		$dashlets = array();
		foreach($old_data['columns'] as $col => $coldata) {
			foreach($coldata['dashlets'] as $id => $v)
				$dashlets[$id] = $v;
			$data['columns'][$col] = $coldata;
			$data['columns'][$col]['dashlets'] = array();
		}
	
		foreach($newLayout as $col => $order) {
			foreach ($order as $id) {
				if(isset($dashlets[$id]))
					$data['columns'][$col]['dashlets'][$id] = $dashlets[$id];
			}
		}
		$this->set_content($data);
	}
	
	function set_column_widths($widths, $unit='%') {
		$data = $this->get_content();
		if(empty($data['columns']) || ! is_array($data['columns']))
			return false;
		if(! is_array($widths) || ! count($widths) || ! array_sum($widths))
			$widths = array_slice(self::$standard_widths[count($data['columns'])], 1);
		$ws = array();
		$min_width = 10;
		for($c = 0; $c < count($data['columns']); $c++) {
			$ws[$c] = max((int)array_get_default($widths, $c, 0), $min_width);
		}
		if($unit == '%') {
			// make sure widths add up to 100%
			while( ($tot = array_sum($ws)) > 100) {
				foreach($ws as &$w)
					$w -= 5;
			}
			if($tot < 100)
				$ws[count($ws)-1] += 100 - $tot;
		}
		foreach($ws as $c => $w)
			$data['columns'][$c]['width'] = $w . $unit;
		$this->set_content($data);
	}
	
	function set_column_count($columns, $reset_widths=true) {
		if(! is_numeric($columns) || $columns < 1 || $columns > 5)
			return false;
		$old_data = $this->get_content();
		$data = $old_data;
		$data['columns'] = array();
		if(empty($old_data['columns']))
			$old_data['columns'] = array();
		$idx = 0;
		foreach($old_data['columns'] as $coldata) {
			if(empty($coldata['dashlets']))
				$coldata['dashlets'] = array();
			if($idx < $columns)
				$data['columns'][] = $coldata;
			else {
				foreach($coldata['dashlets'] as $id => $v)
					$data['columns'][$columns-1]['dashlets'][$id] = $v;
			}
			$idx ++;
		}
		while($idx < $columns)
			$data['columns'][$idx ++] = array('dashlets' => array());
		if($reset_widths) {
			$unit = self::$standard_widths[$columns][0];
			for($idx = 0; $idx < $columns; $idx++)
				$data['columns'][$idx]['width'] = self::$standard_widths[$columns][$idx+1] . $unit;
		}
		$this->set_content($data);
	}
	
	function user_can_edit() {
		if(AppConfig::is_admin() || AppConfig::current_user_id() == $this->assigned_user_id)
			return true;
	}
	
	function add_dashlet($className) {
		$page_data = $this->get_content();
		if(! $page_data || empty($page_data['columns'])) {
			$page_data['columns'] = array(
				array('width' => '50%', 'dashlets' => array()),
				array('width' => '50%', 'dashlets' => array()),
			);
		}
		foreach($page_data['columns'] as $idx => $col) {
			$new_id = create_guid();
			$new_col = $col;
			$new_col['dashlets'] = array($new_id => array('className' => $className));
			foreach($col['dashlets'] as $id => $dlet)
				$new_col['dashlets'][$id] = $dlet;
			$page_data['columns'][$idx] = $new_col;
			$this->set_content($page_data);
			return $new_id;
		}
		return false;
	}
	
	function remove_dashlet($dashlet_id) {
		$page_data = $this->get_content();
		if(! $page_data || empty($page_data['columns']))
			return false;
		foreach($page_data['columns'] as $idx => $col) {
			if(isset($col['dashlets'][$dashlet_id])) {
				unset($page_data['columns'][$idx]['dashlets'][$dashlet_id]);
				$this->set_content($page_data);
				return true;
			}
		}
		return false;
	}
	
	function fill_in_additional_detail_fields() {
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_by_name = get_assigned_user_name($this->modified_user_id);
	}
	
	static function get_section_options($field=null) {
		require_once('include/layout/DashletManager.php');
		$opts = DashletManager::get_section_options();
		$ret = array('' => '');
		foreach($opts as $k)
			$ret[$k] = translate($k);
		return $ret;
	}
	
	static function get_display_name($lbl='') {
		return translate($lbl, 'app');
	}
	
	static function init_record(RowUpdate $upd) {
		$upd->set('assigned_user_id', AppConfig::current_user_id());
		$upd->set('shared_with', '');
	}
	
	static function fill_defaults(RowUpdate $upd) {
		$dname = array_get_default($_REQUEST, 'display_name');
		$old = $upd->getField('name');
		if(strlen($dname) && $dname != self::get_display_name($old)) {
			$upd->set('name', $dname);
		}
	}
	
	static function before_save(RowUpdate $upd) {		
		if(! empty($upd->duplicate_of_id)) {
			$old = ListQuery::quick_fetch_row('Dashboard', $upd->duplicate_of_id);
			if($old) {
				$upd->set('content', $old['content']);
				$upd->set('options', $old['options']);
			}
		}
	}
}

?>
