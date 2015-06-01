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


require_once 'include/ListView/ListViewManager.php';
require_once 'include/ListView/MobileListManager.php';

class StandardSearchViewManager extends ListViewManager {
	var $query_string;
	var $match_prefix;
	var $result_count;

	var $show_title = false;
	var $show_tabs = false;
	var $show_filter = false;
	var $show_mass_update = false;
	var $show_create_button = false;
	var $show_compact_empty = true;
	var $outer_style = 'margin-bottom: 0.8em';
	var $show_checks = false;
	var $default_list_limit = 5;

	function __construct($query_string=null, $match_prefix=null, $params=null) {
		parent::__construct('unified_search', $params);
		$this->setQueryString($query_string);
		$this->setMatchPrefix($match_prefix);
	}
	
	function setQueryString($query_string) {
		$this->query_string = $query_string;
	}
	
	function setMatchPrefix($match_prefix) {
		$this->match_prefix = $match_prefix;
	}
	
	function renderTitle() {
	}
	
	function setTitle($title) {
		$this->getFormatter()->setTitle(get_image($this->layout_module, 'style="vertical-align: top" alt=""') . '&nbsp;' . $title);
	}
	
	function getResultCount() {
		if($this->list_result)
			return $this->list_result->result_count;
	}
	
	function initForModel($model) {
		$this->module = 'Home';
		$this->model_name = $model;
		$this->layout_module = AppConfig::module_for_model($model);
		if (!$this->layout_module)
			return false;
		
		if(AppConfig::setting("display.list.{$this->model_name}.disabled"))
			return false;
		
		$this->addHiddenFields(array('search_model' => $model));
		
		return $this->loadLayoutInfo('Browse');
	}
	
	function getLayoutType() {
		return 'list';
	}
	
	function loadRequestFilter(ListFormatter &$fmt) {
		$filt = array();
		if(isset($this->query_string))
			$filt['filter_text'] = $this->query_string;
		if($this->match_prefix)
			$fmt->getFilterForm()->unified_search_match = 'prefix';
		if($this->async_list) {
			$fmt->loadRequestFilter();
		} else {
			$fmt->loadFilterLayout(array());
			$fmt->loadFilter($filt, 'request');
		}
	}
}

class MobileSearchViewManager extends MobileListManager {
	var $query_string;
	var $match_prefix;
	var $result_count;

	var $show_title = false;
	var $show_tabs = false;
	var $show_filter = false;
	var $show_mass_update = false;
	var $show_create_button = false;
	var $show_compact_empty = true;
	var $outer_style = 'margin-bottom: 0.8em';
	var $show_checks = false;
	var $default_list_limit = 5;

	function __construct($query_string=null, $match_prefix=null, $params=null) {
		parent::__construct('unified_search', $params);
		$this->setQueryString($query_string);
		$this->setMatchPrefix($match_prefix);
	}
	
	function setQueryString($query_string) {
		StandardSearchViewManager::setQueryString($query_string);
	}
	
	function setMatchPrefix($match_prefix) {
		StandardSearchViewManager::setMatchPrefix($match_prefix);
	}
	
	function renderTitle() {
	}
	
	function setTitle($title) {
		StandardSearchViewManager::setTitle($title);
	}
	
	function getResultCount() {
		return StandardSearchViewManager::getResultCount();
	}
	
	function initForModel($model) {
		return StandardSearchViewManager::initForModel($model);
	}
	
	function getLayoutType() {
		return 'list';
	}
	
	function loadRequestFilter(ListFormatter &$fmt) {
		return StandardSearchViewManager::loadRequestFilter($fmt);
	}
}


?>
