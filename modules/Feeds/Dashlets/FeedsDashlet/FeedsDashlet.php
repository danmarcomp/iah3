<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/Dashlets/Dashlet.php');
require_once('include/Sugar_Smarty.php');

class FeedsDashlet extends Dashlet {

	var $dashletIcon = 'Feeds';
	var $isConfigurable = true;
	var $hasScript = true;
	
	var $feed_url;
	var $feed_icon;
	var $feed_title;
	var $display_rows = 8;
	
	var $load_success = null;
	
	static $display_rows_options = array(
		3, 5, 8, 10, 15,
	);

    function FeedsDashlet($id, $def=null) {
		parent::__construct($id, $def);
		
		$this->loadLanguage('FeedsDashlet', 'Feeds');
		
		$this->title = array_get_default($def, 'title', '');
		if (!empty($def['feed_url']))
			$this->feed_url = $def['feed_url'];
		if (!empty($def['feed_icon']))
			$this->feed_icon = $def['feed_icon'];
		if (!empty($def['feed_title']))
			$this->feed_title = $def['feed_title'];
		if (!empty($def['display_rows']))
			$this->display_rows = $def['display_rows'];
        if (isset($def['auto_refresh_time']))
            $this->autoRefreshTime = $def['auto_refresh_time'];
    }
        
    function process() {
    	global $pageInstance;
    	$pageInstance->add_js_include('modules/Feeds/Dashlets/FeedsDashlet/FeedsDashlet.js', null, LOAD_PRIORITY_BODY);
    	$json = getJSONobj();
    	$pageInstance->add_js_literal('FeedsLang = ' . $json->encode($this->dashletStrings) . ';', null, LOAD_PRIORITY_BODY);
    	if(! $this->asyncDisplay)
    		return 'pending';
    	if($this->load_feed(true, $this->forceRefresh) === 'download')
			return 'download';
    }
    
    function displaySample() {
    	global $pageInstance;
    	$pageInstance->add_js_include('modules/Feeds/Dashlets/FeedsDashlet/FeedsDashlet.js', null, LOAD_PRIORITY_BODY);
    	return parent::displaySample();
    }
    
    function getIcon() {
    	if($this->feed_icon)
    		return $this->feed_icon;
    	return parent::getIcon();
    }
    
    function getTitleText() {
    	if($this->title)
    		return $this->title;
    	else if($this->feed_title)
    		return $this->feed_title;
    	return $this->dashletStrings['LBL_TITLE'];
    }
    
    function load_feed($defer=false, $force_refresh=false) {
    	require_once('modules/Feeds/Feed.php');
    	$this->feed = new Feed();
    	if($defer) {
    		$this->load_success = false;
    		if($this->feed->load_feed($this->feed_url, array(&$this, 'download_complete'), $force_refresh))
				return 'download';
    	} else {
			$this->load_success = $this->feed->load_feed($this->feed_url);
			$this->finish_load();
		}
    	return $this->load_success;
    }
    
    function download_complete() {
    	$this->load_success = $this->feed->feed_loaded();
    	$this->finish_load();
    }
    
    function finish_load() {
    	if($this->load_success) {
			$t = $this->feed->get_feed_title();
    		if(mb_strlen($t) > 50)
    			$t = mb_substr($t, 0, 48) . '..';
			$this->feed_title = $t;
			$this->feed_icon = $this->feed->get_feed_favicon();
		} else {
			$this->feed_title = '';
			$this->feed_icon = '';
		}
    }
    
    
    
    function get_buttons() {
    	global $app_strings, $image_path;
    	$prev_on = "<button type=\"button\" onclick=\"FeedsDashlet.previousFeedItem('".$this->id."');\" class=\"input-button input-outer nav-button\" title=\"".$app_strings['LNK_LIST_PREVIOUS']."\"><div class=\"input-icon icon-prev\"></div></button>";
    	$prev_off = "<button type=\"button\" disabled=\"disabled\" class=\"input-button input-outer nav-button\" title=\"".$app_strings['LNK_LIST_PREVIOUS']."\"><div class=\"input-icon icon-prev off\"></div></button>";
    	$next_on = "<button type=\"button\" onclick=\"FeedsDashlet.nextFeedItem('".$this->id."');\" class=\"input-button input-outer nav-button\" title=\"".$app_strings['LNK_LIST_PREVIOUS']."\"><div class=\"input-icon icon-next\"></div></button>";
    	$next_off = "<button type=\"button\" disabled=\"disabled\" class=\"input-button input-outer nav-button\" title=\"".$app_strings['LNK_LIST_PREVIOUS']."\"><div class=\"input-icon icon-next off\"></div></button>";
    	$close = '<button type="button" onclick="FeedsDashlet.hideDialog();" class="input-button input-outer"><div class="input-icon left icon-cancel"></div><span class="input-label">'.$app_strings['LBL_ADDITIONAL_DETAILS_CLOSE'].'</span></button>';
    	return compact('prev_on', 'prev_off', 'next_on', 'next_off', 'close');
    }
    
    function get_feed_data() {
    	global $image_path;
    	$ret = array('title' => $this->getTitleText(), 'link' => '', 'items' => array());
    	$ret['buttons'] = $this->get_buttons();
    	$ret['site_link_text'] = get_image($image_path . 'view_inline', '') . '&nbsp;' . $this->dashletStrings['LBL_VIEW_SITE'];
    	$ret['story_link_text'] = get_image($image_path . 'view_inline', '') . '&nbsp;' . $this->dashletStrings['LBL_VIEW_STORY'];
    	if($this->load_success) {
    		$ret['favicon'] = $this->feed->get_feed_favicon();
    		$ret['title'] = $this->feed->get_feed_title();
    		$ret['link'] = $this->feed->get_feed_link();
    		$ret['image'] = $this->feed->get_feed_image();
    		$ret['description'] = $this->feed->get_feed_description();
    		$ret['copyright'] = $this->feed->get_feed_copyright();
    		$ret['items'] = $this->feed->get_feed_items($this->display_rows);
    	}
    	return $ret;
    }

    /**
     * Displays the dashlet
     * 
     * @return string html to display dashlet
     */
	function display() {
		global $current_user, $timedate;
		$str = '';
        $ss = new Sugar_Smarty();
        $ss->assign('id', $this->id);
		$ss->assign('STR', $this->dashletStrings);

		global $theme, $odd_bg, $even_bg, $hilite_bg;
		require_once("themes/$theme/layout_utils.php");
	
		$ss->assign('bgHilite', $hilite_bg);
		$ss->assign('rowColor', array('oddListRow', 'evenListRow'));
		$ss->assign('bgColor', array($odd_bg, $even_bg));
		
		$feed_data = $this->get_feed_data();
		$ss->assign('feed_data', $feed_data);
		$json = getJSONObj();
		$ss->inc_mgr->add_js_literal('FeedsDashlet.init("'.$this->id.'", ' . $json->encode($feed_data) . ');', null, LOAD_PRIORITY_FOOT);
        if ($this->autoRefreshTime > 0)
            $ss->inc_mgr->add_js_literal("SUGAR.sugarHome.initDashletAutoRefresh('{$this->id}', {$this->autoRefreshTime});", null, LOAD_PRIORITY_FOOT);

        $str = $ss->fetch(
			'modules/Feeds/Dashlets/FeedsDashlet/FeedsDashlet.tpl'
		);
		$ss->export_includes();

        return parent::display('') . $str;
    }
    
    /**
     * Displays the javascript for the dashlet
     * 
     * @return string javascript to use with this dashlet
     */
	function displayScript() {
		return '';
    }
        
    /**
     * Displays the configuration form for the dashlet
     * 
     * @return string html to display form
     */
    function displayOptions() {
        global $mod_strings, $app_strings, $app_list_strings;

        $ss = new Sugar_Smarty();
		$ss->assign('id', $this->id);
		$ss->assign('STR', $this->dashletStrings);

		$ss->assign("title", $this->title);
		$ss->assign("feed_url", $this->feed_url);
		$ss->assign("display_rows", $this->display_rows);		
		$opts = array_combine(self::$display_rows_options, self::$display_rows_options);
		$ss->assign("display_rows_options", get_select_options_with_id($opts, $this->display_rows));

        $ss->assign('refresh_time', $this->autoRefreshTime);
        $ss->assign('refresh_options', $app_list_strings['dashlet_auto_refresh_dom']);
        $ss->assign('refresh_label', $mod_strings['LBL_DASHLET_CONFIGURE_AUTOREFRESH']);

        $ss->assign('saveLbl', $app_strings['LBL_SAVE_BUTTON_LABEL']);
		$ss->assign('cancelLbl', $app_strings['LBL_CANCEL_BUTTON_LABEL']);

        return parent::displayOptions() . $ss->fetch('modules/Feeds/Dashlets/FeedsDashlet/FeedsDashletOptions.tpl');
    }  
    
	function searchFeeds() {
		$value = array_get_default($_REQUEST, 'value');
		$json = self::_searchFeeds($value, true);
		return $json;
	}
	
	function _searchFeeds($value, $encode=false) {
		global $db;
		$lq = new ListQuery('Feed', array('id', 'title', 'url'));
		$v = $db->quote($value);
		$filter = "(title like '%{$v}%' OR url like '%{$v}%')";
		$lq->addFilterClause($filter);
		$result = $lq->runQuery(0, 10);
		if($result && ! $result->failed)
			$ret = array_values($result->rows);
		else
			$ret = array();
		if($encode) {
			$json = getJSONobj();
			$ret = $json->encode($ret);
		}
		return $ret;
	}

    /**
     * Called to filter out $_REQUEST object when the user submits the configure dropdown
     * 
     * @param array $req $_REQUEST
     * @return array filtered options to save
     */  
    function saveOptions($req) {
        global $timedate, $current_user, $theme;
        $options = array();
        $options['title'] = array_get_default($_REQUEST, 'title', '');
        $this->feed_url = $options['feed_url'] = $_REQUEST['feed_url'];
		if($this->load_feed()) {
			$options['feed_title'] = $this->feed_title;
			$options['feed_icon'] = $this->feed_icon;
		}
		$options['display_rows'] = $_REQUEST['display_rows'];
        $options['auto_refresh_time'] = $_REQUEST['auto_refresh_time'];
        return $options;
    }
    
}
?>