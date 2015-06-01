<?php

// vim: set foldmethod=marker :

if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/Dashlets/Dashlet.php');
require_once('include/Sugar_Smarty.php');
require_once('modules/Currencies/Currency.php');

class StockDashlet extends Dashlet {
	var $dashletIcon = 'StockQuotes';
	var $symbols;
	var $display_chart;
	
	var $stockData;
	var $lastUpdated;
	
	const DEFAULT_CACHE_EXPIRY = 7200; // 2 hours
	
	const REQUEST_TIMEOUT = 20; // seconds

    /**
     * Constructor 
     * 
     * @global string current language
     * @param guid $id id for the current dashlet (assigned from Home module)
     * @param array $def options saved for this dashlet
     */
    function StockDashlet($id, $def=null) {
        parent::__construct($id, $def); // call parent constructor
         
        $this->loadLanguage('StockDashlet'); // load the language strings here

        $this->isConfigurable = true; // dashlet is configurable
        $this->hasScript = true;  // dashlet has javascript attached to it
                
        // if no custom title, use default
        if(empty($def['title'])) $this->title = $this->dashletStrings['LBL_TITLE'];
		else $this->title = $def['title'];

        if (isset($def['auto_refresh_time']))
            $this->autoRefreshTime = $def['auto_refresh_time'];

        if (!empty($def['symbols'])) {
			$this->symbols = $def['symbols'];
		} else {
			$this->symbols = $this->getDefaultSymbols();
		}

		$this->display_chart = !! array_get_default($def, 'display_chart', true);
    }
    
    function process() {
    	global $pageInstance;
		$pageInstance->add_js_include('modules/Home/Dashlets/StockDashlet/StockDashlet.js');
		if( ($status = $this->getStocks($this->forceRefresh)) )
    		return $status;
    }

    function displaySample() {
    	global $pageInstance;
    	$pageInstance->add_js_include('modules/Home/Dashlets/StockDashlet/StockDashlet.js');
    	return parent::displaySample();
    }
    
    function getCacheFilename() {
		$key = md5(preg_replace('~\s~Ums', '', $this->symbols));
    	$cache_fname = CacheManager::get_location('stocks/') . $key . '.txt';
    	return $cache_fname;
    }

	function getStocks($refresh=false, $format=true) {
    	$cache_fname = $this->getCacheFilename();
    	$now = time();
    	$ret = null;
    	if(file_exists($cache_fname) && ! $refresh) {
    		$mtime = @filemtime($cache_fname);
    		if($now - $mtime < self::DEFAULT_CACHE_EXPIRY) {
    			$ret = unserialize(file_get_contents($cache_fname));
				$this->lastUpdated = $mtime;
    		}
    	}
    	if(! isset($ret)) {
    		if($this->asyncDisplay) {
    			$this->fetchQuotes($this->symbols);
    			return 'download';
    		}
    		return 'pending';
    	}
    	$this->setStockData($ret);
	}
	
	function setStockData($data) {
		$this->stockData = $this->formatData($data);
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
		$ss->assign('LANG', $this->dashletStrings);

		if($this->stockData) {
			global $theme, $odd_bg, $even_bg, $hilite_bg;
			require_once("themes/$theme/layout_utils.php");
			$ss->assign('titles', $this->stockData[0]);
			$ss->assign('data', $this->stockData[1]);
	
			$ss->assign('bgHilite', $hilite_bg);
			$ss->assign('rowColor', array('oddListRow', 'evenListRow'));
			$ss->assign('bgColor', array($odd_bg, $even_bg));
			
			$data = $this->stockData[1];
			$json = getJSONObj();
			$display_chart = $this->display_chart ? 'true' : 'false';
			$ss->inc_mgr->add_js_literal('StockQuotes.init("'.$this->id.'", '.$display_chart.', ' . $json->encode($data) . ');', null, LOAD_PRIORITY_FOOT);
            if ($this->autoRefreshTime > 0)
                $ss->inc_mgr->add_js_literal("SUGAR.sugarHome.initDashletAutoRefresh('{$this->id}', {$this->autoRefreshTime});", null, LOAD_PRIORITY_FOOT);

            $str = $ss->fetch(
				'modules/Home/Dashlets/StockDashlet/StockDashlet.tpl'
			);
			$dt = gmdate('Y-m-d H:i:s', isset($this->lastUpdated) ? $this->lastUpdated : time());
			$dt = $timedate->to_relative_date_time($dt);
			$title = str_replace('{time}', $dt, $this->dashletStrings['LBL_ALL_USD']);
		} else
			$title = '';
		$ss->export_includes();

        return parent::display($title) . $str;
    }


	function parseData($csvData)
	{
		if(! preg_match('/^\d+/', $csvData))
			return false;
		$ret = array();

		$fname = tempnam('/tmp', 'StockQuotes');
		$fp = fopen($fname, 'r+');
		fwrite($fp, $csvData);
		rewind($fp);
		while ($row = fgetcsv($fp)) {
			foreach($row as $i => $x) {
				if ($x === '000') {
					$row[$i-1] *= 1000;
					unset($row[$i]);
				}
			}
			$ret[] = array_values($row);
		}
		fclose($fp);
		return $ret;
	}

	function formatData($data)
	{
		if($data) {
			$ret = array();
			$displayCodes = array_keys(StockDashlet::getColumnsMap());
			foreach ($data as $row) {
				$cols = array();
				$change = 0;
				foreach ($displayCodes as $i => $colCode)
				{
					$val = array_get_default($row, $i, '');
					if($colCode == 'c6')
						$change = (float)$val;
					$val = preg_replace('/\b(\d+\.\d+)\b/e', "format_number('\\1', -1, -1)", $val);
					$cols[$colCode] = $val;
				}
				if($change == 0) {
					$color = '#555';
					$img = 'nochange';
				}
				else if ($change < 0) {
					$color = '#F33';
					$img = 'down_r';
				} else {
					$color = '#080';
					$img = 'up_g';
				}
				$icon = get_image('themes/'.$GLOBALS['theme']."/images/$img", 'border="0" alt=""');
				$cols['c6'] = '<span style="font-weight:bold;color:' . $color . '">' . $icon . '&nbsp;' . $cols['c6'] . ' (' . $cols['p2'] . ')';
				$ret[] = $cols;
			}
		} else {
			$ret = array( array('s' => '<em>'.$this->dashletStrings['LBL_PARSE_ERROR'].'</em>') );
		}

		$titles = array();
		return array($titles, $ret);
	}

    /**
     * Displays the javascript for the dashlet
     * 
     * @return string javascript to use with this dashlet
     */
	function displayScript() {
		$json = getJSONObj();
		return '<script type="text/javascript">StockQuotesLang = ' . $json->encode($this->dashletStrings) . ';</script>';
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
		$ss->assign('TITLE', $this->title);
		$ss->assign('TITLE_LABEL', $this->dashletStrings['LBL_CONFIGURE_TITLE']);
        $ss->assign('REFRESH_TIME', $this->autoRefreshTime);
        $ss->assign('REFRESH_OPTIONS', $app_list_strings['dashlet_auto_refresh_dom']);
        $ss->assign('REFRESH_LABEL', $mod_strings['LBL_DASHLET_CONFIGURE_AUTOREFRESH']);
		$ss->assign('SYMBOLS', $this->symbols);
		$ss->assign('SYMBOLS_LABEL', $this->dashletStrings['LBL_SYMBOLS']);
		$ss->assign('CHART', $this->display_chart ? 'checked="checked"' : '');
		$ss->assign('CHART_LABEL', $this->dashletStrings['LBL_DISPLAY_CHART']);
        $ss->assign('saveLbl', $app_strings['LBL_SAVE_BUTTON_LABEL']);
        $ss->assign('resetLbl', $app_strings['LBL_RESET_BUTTON_LABEL']);
        $ss->assign('cancelLbl', $app_strings['LBL_CANCEL_BUTTON_LABEL']);

        return parent::displayOptions() . $ss->fetch('modules/Home/Dashlets/StockDashlet/StockDashletOptions.tpl');
    }  

    /**
     * called to filter out $_REQUEST object when the user submits the configure dropdown
     * 
     * @param array $req $_REQUEST
     * @return array filtered options to save
     */  
    function saveOptions($req) {
        global $timedate, $current_user, $theme;
        $options = array();
        $options['title'] = $_REQUEST['title'];
        $options['auto_refresh_time'] = $_REQUEST['auto_refresh_time'];
        $options['symbols'] = strtoupper(strtr($_REQUEST['symbols'], ';', ','));
        $options['display_chart'] = empty($_REQUEST['display_chart']) ? 0 : 1;
		$current_user->setPreference('quotes', null, 0, 'StockDashlet');
        return $options;
    }

	function fetchQuotes($symbols)
	{
		if (is_array($symbols)) {
			$symbols = implode(',', $symbols);
		} else {
			$symbols = implode(',', array_map('trim', explode(',', $symbols)));
		}

		$url = 'http://download.finance.yahoo.com/d/quotes.csv?s=';
		$url .= $symbols;
		$url .= '&f=';
		$url .= implode('', array_keys(StockDashlet::getColumnsMap()));
		
		DLManager::add_download(array(
			'url' => $url,
			'callback' => array(&$this, 'receiveQuotes'),
		));
	}
	
	function receiveQuotes(&$dl) {
		if(! $dl->failed) {
			$data = $this->parseData($dl->getResponse());
			$cache_fname = $this->getCacheFilename();
			if( ($fp = @fopen($cache_fname, 'w')) ) {
				fwrite($fp, serialize($data));
				fclose($fp);
			} else
				return false;
			$this->lastUpdated = time();
			$this->setStockData($data);
		} else
			$this->setStockData(null);
	}

	function getColumnsMap()
	{
		static $columnsMap = array(/*{{{*/
			'l1' => 'LAST_TRADE',
			'd1' => 'LAST_TRADE_DATE',
			't1' => 'LAST_TRADE_TIME',
			'c6' => 'CHANGE',
			'p2' => 'CHANGE_PERCENT',
			'p' => 'PREV_CLOSE',
			'o' => 'OPEN',
			'a' => 'ASK',
			'b' => 'BID',
			't8' => '1Y_TARGET_PRICE',
			'm' => 'DAYS_RANGE',
			'w' => '52WEEK_RANGE',
			'v' => 'VOLUME',
			'a2' => 'AVG_VOLUME',
			'j1' => 'MARKET_CAP',
			'e' => 'EPS',
			'd' => 'DPS',
			'y' => 'YIELD',
			's' => 'SYMBOL',
			'n' => 'NAME',
			'r' => 'PE',
			//'a5' => 'ASK_SIZE',
			//'b6' => 'BID_SIZE',
		);/*}}}*/
		return $columnsMap;
	}
	
	function getDefaultSymbols() {
		return 'GOOG, MSFT, AAPL, ^DJI, ^GSPTSE, ^FTSE';
	}
}

?>
