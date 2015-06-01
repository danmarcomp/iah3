<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

require_once('include/Dashlets/Dashlet.php');
require_once('include/Sugar_Smarty.php');
require_once('modules/Home/Dashlets/WeatherDashlet/WeatherDashletConfigView.php');

class WeatherDashlet extends Dashlet {
	
	var $dashletIcon = 'Weather';
	
	var $cities;
	
	var $cityDisplayData;
	
	var $degreesUnits;
	
	var $mostRecent;
	
	var $showTimes = 1;
	
	const CITIES_NUM = 10;
	
	const YAHOO_WEATHER_URL = "http://weather.yahooapis.com/forecastrss?w=";
	
	const YAHOO_WEATHER_ERROR = "Yahoo! Weather Error";
	
	const YAHOO_APPLICATION_ID = "N781.1DV34FdGJl7GB3JPtiBKat.wf4lIo90bjQorNCHOk6Eyoi5kWBDo3LoK2LeWj9nObmw6Kn8AXhOamw-";
	
	const UNLIMITED_VISIBILITY = "999";
	
	const DEFAULT_DEGREES_UNITS = "f";
	
	const FAHRENHEIT = "f";
	
	const CELSIUS = "c";
	
	const DEFAULT_CACHE_EXPIRY = 7200; // 2 hours
	
	const REQUEST_TIMEOUT = 20; // seconds
	
    /**
     * Constructor 
     * 
     * @global string current language
     * @param string $id id for the current dashlet (assigned from Home module)
     * @param array $def options saved for this dashlet
     */
    function WeatherDashlet($id, $def=null) {
        parent::__construct($id, $def); // call parent constructor
         
        $this->loadLanguage('WeatherDashlet'); // load the language strings here

        $this->isConfigurable = true; // dashlet is configurable
        $this->hasScript = true;  // dashlet has javascript attached to it
                
        // if no custom settings, use default
        if(empty($def['title'])) {
        	$this->title = $this->dashletStrings['LBL_TITLE'];
        } else {
        	$this->title = $def['title'];
        }

        if (isset($def['auto_refresh_time']))
            $this->autoRefreshTime = $def['auto_refresh_time'];

        if (!empty($def['cities'])) {
			$this->cities = $def['cities'];
		} else {
			$this->cities = $this->getDefaultCities();
		}
		
		if(isset($def['show_times']))
			$this->showTimes = $def['show_times'];

		if (!empty($def['degrees_units'])) {
			$this->degreesUnits = $def['degrees_units'];
			// sanitize, mainly for cache filename
			if($this->degreesUnits != self::FAHRENHEIT && $this->degreesUnits != self::CELSIUS)
				$this->degreesUnits = self::DEFAULT_DEGREES_UNITS;
		} else {
			$this->degreesUnits = self::DEFAULT_DEGREES_UNITS;
		}        		
    }
    
    function process() {
    	global $pageInstance, $timedate;
    	$pageInstance->add_js_include('modules/Home/Dashlets/WeatherDashlet/WeatherDashlet.js');
    	$this->cityDisplayData = array();
		$now = time();
		
		$missing = array();
		
		foreach($this->cities as $city) {
			$woeid = $city["woeid"];
			
			if(! empty($city['timezone']) && ($offset = $timedate->getTimeZoneOffset($city['timezone'])) !== false) {
				$city['current_time'] = date($timedate->get_time_format(), $now + $offset);
			} else
				$city['current_time'] = '--';
			$this->cityDisplayData[$woeid] = $city;
			
			$weather = $this->getWeather($woeid);
			if($weather === false) {
				if(! $this->asyncDisplay)
					return 'pending'; // defer display
				$missing[] = $woeid;
			}
			else
				$this->cityDisplayData[$woeid]['weather_data'] = $this->formatWeather($weather);
		}
		
		if(count($missing)) {
			$this->fetchWeather($missing);
			return 'download';
		}
    }
    
    function displaySample() {
    	global $pageInstance;
    	$pageInstance->add_js_include('modules/Home/Dashlets/WeatherDashlet/WeatherDashlet.js');
    	return parent::displaySample();
    }

    /**
     * Displays the dashlet
     * 
     * @return string html to display dashlet
     */
	function display() {
		global $timedate;
        $ss = new Sugar_Smarty();
        $ss->assign('id', $this->id);
		$ss->assign('LANG', $this->dashletStrings);

		global $theme, $odd_bg, $even_bg, $hilite_bg;
		require_once("themes/$theme/layout_utils.php");

		$ss->assign("citiesList", $this->cityDisplayData);
		$ss->assign("showTimes", $this->showTimes);
	
		$ss->assign('bgHilite', $hilite_bg);
		$ss->assign('rowColor', array('oddListRow', 'evenListRow'));
		$ss->assign('bgColor', array($odd_bg, $even_bg));
		
		$json = getJSONObj();
		$ss->inc_mgr->add_js_literal('Weather.init("'.$this->id.'", ' . $json->encode($this->cityDisplayData) . ');', null, LOAD_PRIORITY_FOOT);
        if ($this->autoRefreshTime > 0)
            $ss->inc_mgr->add_js_literal("SUGAR.sugarHome.initDashletAutoRefresh('{$this->id}', {$this->autoRefreshTime});", null, LOAD_PRIORITY_FOOT);

		$str = $ss->fetch(
			'modules/Home/Dashlets/WeatherDashlet/WeatherDashlet.tpl'
		);

		$dt = gmdate('Y-m-d H:i:s', isset($this->mostRecent) ? $this->mostRecent : time());
		$dt = $timedate->to_relative_date_time($dt);
		$title = str_replace('{time}', $dt, $this->dashletStrings['LBL_LAST_UPDATE']);

		$ss->export_includes();

        return parent::display($title) . $str;
    }
    
    function getCacheFilename($woeid) {
    	$key = (int)$woeid . $this->degreesUnits;
    	$cache_fname = CacheManager::get_location('weather/') . $key . '.txt';
    	return $cache_fname;
    }
    
    /**
	 * Get weather from local cache, reverting to fetchWeather if not found.
	 * 
	 * @param string $woeid
	 * @return array
	 */
    function getWeather($woeid) {
		if($this->forceRefresh)
			return false;
		
    	$cache_fname = $this->getCacheFilename($woeid);
    	$now = time();
    	$ret = null;
    	if(file_exists($cache_fname)) {
    		$mtime = @filemtime($cache_fname);
    		if($now - $mtime < self::DEFAULT_CACHE_EXPIRY) {
    			$ret = unserialize(file_get_contents($cache_fname));
				if(! isset($this->mostRecent) || $this->mostRecent < $mtime)
					$this->mostRecent = $mtime;
    		}
    	}
    	if(empty($ret))
    		return false;
		return $ret;
    }
    
    function formatWeather($data) {
    	if($data) {
			if($data['pressure'])
				$data['pressure'] = format_number($data['pressure'], -1) .
					' ' . $data['pressure_units'] .
					' ' . $this->dashletStrings['LBL_AND'] .
					' ' . $this->getPressureState($data['pressure_state']);
			else
				$data['pressure'] = '--';
			
			if($data['humidity'])
				$data['humidity'] .= '%';
			else
				$data['humidity'] = '--';
			
			if($data['visibility'] >= self::UNLIMITED_VISIBILITY)
				$data['visibility'] = $this->dashletStrings['LBL_UNLIMITED'];
			else
				$data['visibility'] = format_number($data['visibility'], -1) .
					' ' . $data['visibility_units'];
			
			if($data['chill']) {
				if($data['chill'] == $data['temp'])
					$data['chill'] = '';
				else
					$data['chill'] = '(' . $data['chill'] . ' ' . $this->dashletStrings['LBL_WIND_CHILL'] . ')';
			}
			$data['wind'] = format_number($data['wind_speed'], -1) .
				' ' . $data['wind_units'] . ' ' . $this->getWindDirection($data['wind_direction']);
			
			$data['sunrise'] = $this->reformat_time($data['sunrise']);
			$data['sunset'] = $this->reformat_time($data['sunset']);
		}
		return $data;
    }

	/**
	 * Get weather from Yahoo weather
	 * 
	 * @param string $woeids - to find your WOEID,
	 * browse or search for your city from the Yahoo Weather home page
     * @param bool $save_cache
	 * @return array
	 */
	function fetchWeather($woeids, $save_cache=true) {
		foreach($woeids as $woeid) {
			DLManager::add_download(array(
				'woeid' => $woeid,
				'url' => self::YAHOO_WEATHER_URL . $woeid ."&u=". $this->degreesUnits,
				'save_cache' => $save_cache,
				'callback' => array(&$this, 'finishDownload'),
			));
		}
	}
	
    function finishDownload(&$dl) {
		$woeid = $dl->woeid;
    	if(! $dl->failed) {
			$arrData = $this->parseWeatherResult($woeid, $dl->getResponse());
			if($dl->save_cache) {
				$cache_fname = $this->getCacheFilename($woeid);
				if( ($fp = @fopen($cache_fname, 'w')) ) {
					fwrite($fp, serialize($arrData));
					fclose($fp);
				}
			}
			$this->cityDisplayData[$woeid]['weather_data'] = $this->formatWeather($arrData);
		} else {
			$this->cityDisplayData[$woeid]['weather_data'] = null;
		}
    }
		
	
	function parseWeatherResult($woeid, &$data) {
		libxml_use_internal_errors(true);		
		$xml = simplexml_load_string($data);
		
		if (!$xml) {
			$loadXml = false;	
		} else {
			$loadXml = true;
			$description = $xml->xpath("/rss/channel/description");
		}	
		
		$resultArray = array();

		if ( $loadXml && ((string)$description[0] != self::YAHOO_WEATHER_ERROR) ) {

			$unitsData = $xml->xpath("/rss/channel/yweather:units");
			$windData = $xml->xpath("/rss/channel/yweather:wind");
			$atmosphereData = $xml->xpath("/rss/channel/yweather:atmosphere");
			$astronomyData = $xml->xpath("/rss/channel/yweather:astronomy");
			$conditionData = $xml->xpath("/rss/channel/item/yweather:condition");
			$imageData = $xml->xpath("/rss/channel/item/description");
	
			$forecastData1 = $xml->xpath("/rss/channel/item/yweather:forecast[1]");
			$forecastData2 = $xml->xpath("/rss/channel/item/yweather:forecast[2]");		
			$imageDataString = (string)$imageData[0];
			
			$linkData = $xml->xpath("/rss/channel/item/link");
			$linkDataString = (string)$linkData[0];
	
			preg_match('/<img src="(.*)"\/>/Usm', $imageDataString, $imageResult); 
			$image = $imageResult[1];
			
			$resultArray["text"] = (string)$conditionData[0]->attributes()->text;
			$resultArray["temp"] = (string)$conditionData[0]->attributes()->temp . "&deg;".strtoupper($this->degreesUnits);
			$resultArray["humidity"] = (string)$atmosphereData[0]->attributes()->humidity;

			$resultArray['pressure_state'] = (int)$atmosphereData[0]->attributes()->rising;
			$resultArray['pressure_units'] = (string)$unitsData[0]->attributes()->pressure;
			$resultArray["pressure"] = (float)$atmosphereData[0]->attributes()->pressure;
			
			$resultArray["visibility"] = (float)$atmosphereData[0]->attributes()->visibility;
			$resultArray["visibility_units"] = (string)$unitsData[0]->attributes()->distance;
			
			$resultArray["wind_speed"] = (float)$windData[0]->attributes()->speed;
			$resultArray["wind_units"] = (string)$unitsData[0]->attributes()->speed;
			$resultArray["wind_direction"] = (string)$windData[0]->attributes()->direction;
			$chill = (string)$windData[0]->attributes()->chill;
			$resultArray["chill"] = $chill ? $chill . '&deg;' . strtoupper($this->degreesUnits) : '';
			$resultArray["sunrise"] = (string)$astronomyData[0]->attributes()->sunrise;
			$resultArray["sunset"] = (string)$astronomyData[0]->attributes()->sunset;
			$resultArray["image"] = $image;
			
			$resultArray["woeid"] = $woeid;
			$resultArray["yahoo_weather_link"] = $linkDataString;
			
			$resultArray["forecast1_day"] = (string)$forecastData1[0]->attributes()->day;
			$resultArray["forecast1_date"] = (string)$forecastData1[0]->attributes()->date;
			$resultArray["forecast1_low"] = (string)$forecastData1[0]->attributes()->low . "&deg;";
			$resultArray["forecast1_high"] = (string)$forecastData1[0]->attributes()->high . "&deg;";
			$resultArray["forecast1_text"] = (string)$forecastData1[0]->attributes()->text;
			
			$resultArray["forecast2_day"] = (string)$forecastData2[0]->attributes()->day;
			$resultArray["forecast2_date"] = (string)$forecastData2[0]->attributes()->date;
			$resultArray["forecast2_low"] = (string)$forecastData2[0]->attributes()->low . "&deg;";
			$resultArray["forecast2_high"] = (string)$forecastData2[0]->attributes()->high . "&deg;";
			$resultArray["forecast2_text"] = (string)$forecastData2[0]->attributes()->text;
		}
		else
			$resultArray = false;
		
		libxml_clear_errors();
						
		return $resultArray;
	}
	
	function reformat_time($d) {
		global $timedate;
		$native_fmt = 'h:i a';
		if($d && $timedate->check_matching_format($d, $native_fmt)) {
			$d = $timedate->swap_formats($d, $native_fmt, $timedate->get_time_format());
		}
		return $d;
	}
	
	/**
	 * Provide external access to _searchLocations via CallDashletMethod
	**/
	function searchLocations() {
		$locName = array_get_default($_REQUEST, 'location');
		$locId = array_get_default($_REQUEST, 'location_id', '');
		$json = self::_searchLocations($locName, $locId, true);
		return $json;
	}
	
	function fetchTimezone($woeid) {
		$requestUrl = sprintf(
			"http://where.yahooapis.com/v1/place/%s/belongtos.type('Time%%20Zone')?appid=%s",
			urlencode($woeid), self::YAHOO_APPLICATION_ID);

		$curlInstance = curl_init();
		curl_setopt($curlInstance, CURLOPT_URL, $requestUrl);
		curl_setopt($curlInstance, CURLOPT_HEADER, 0);
		curl_setopt($curlInstance, CURLOPT_RETURNTRANSFER,1);
		$resultString = curl_exec($curlInstance);
		curl_close($curlInstance);

		if($resultString && preg_match('~<name>(.*?)</name>~Usm', $resultString, $m)) {
			return $m[1];
		}
		return false;
	}
	
	/**
	**/
	static function _searchLocations($locName, $locId, $as_string = false) {
		$ret = array();
		if(! empty($locName)) {
			$requestUrl = sprintf(
				"http://where.yahooapis.com/v1/places.q('%s');start=0;count=5?format=json&lang=%s&appid=%s",
				urlencode($locName), urlencode($GLOBALS['current_language']), self::YAHOO_APPLICATION_ID);

			$curlInstance = curl_init();
			curl_setopt($curlInstance, CURLOPT_URL, $requestUrl);
			curl_setopt($curlInstance, CURLOPT_HEADER, 0);
			curl_setopt($curlInstance, CURLOPT_RETURNTRANSFER,1);
			$resultString = curl_exec($curlInstance);
			curl_close($curlInstance);
			
			if($resultString) {
				$json = getJSONobj();				
				$jsonData = $json->decode($resultString);
								
				if($as_string) {
					$jsonData["places"]["location_id"] = $locId;
					$resultString = $json->encode($jsonData);
					return $resultString;
				}
					
				$ret = $jsonData;
			}
		}
		else if($as_string)
			return '[]';
		return $ret;
	}

    /**
     * Displays the javascript for the dashlet
     * 
     * @return string javascript to use with this dashlet
     */
	function displayScript() {
		$json = getJSONObj();
		$script = '<script type="text/javascript">WeatherLang = ' . $json->encode($this->dashletStrings) . ';</script>';
		return $script;
    }
        
    /**
     * Displays the configuration form for the dashlet
     * 
     * @return string|void html to display form
     */
    function displayOptions() {
        $view = new WeatherDashletConfigView($this);
        return $view->getTemplate();
    }

    /**
     * Called to filter out $_REQUEST object when the user submits the configure dropdown
     * 
     * @param array $req $_REQUEST
     * @return array filtered options to save
     */  
    function saveOptions($req) {
        global $current_user;

        $options = array();
        $options['title'] = $req['title'];
        $options['auto_refresh_time'] = $req['auto_refresh_time'];
        $options['degrees_units'] = $req['degrees_units'];
        $options['show_times'] = $req['show_times'];

        $json = getJSONobj();
        $cities = $json->decode($req['cities']);
        if(! is_array($cities))
        	$cities = array();
        
        $old_cities = array();
        if(isset($options['cities'])) {
        	foreach($options['cities'] as $city) {
        		if(! empty($city['woeid']))
	        		$old_cities[$city['woeid']] = $city;
        	}
        }
        
        foreach($cities as $city_key => $city) {
        	$city['name'] = trim(array_get_default($city, 'name', ''));
        	$city['woeid'] = trim(array_get_default($city, 'woeid', ''));
        	if($city['name'] !== "" && $city['woeid']) {
        		if(isset($old_cities[$city['woeid']])) {
        			$old_city = $old_cities[$city['woeid']];
        			if(isset($old_city['timezone']))
        				$city['timezone'] = $old_city['timezone'];
        		}
				if(empty($city['timezone']))
					$city['timezone'] = $this->fetchTimezone($city['woeid']);
        		$options['cities'][] = $city;
        	}
        }
        
		$current_user->setPreference('weather', null, 0, 'WeatherDashlet');

		return $options;
    }
    
	function getDefaultCities() {
		return AppConfig::setting('locale.defaults.weather_cities', array());
	}
	
	/**
	 * Get barometric pressure state by state code
	 * 
	 * @param int $stateCode:
	 * steady (0), rising (1), or falling (2).
	 * @return string
	 */
	function getPressureState($stateCode) {
		
		$states = array(
			0 => $this->dashletStrings['LBL_PRESSURE_STEADY'],
			1 => $this->dashletStrings['LBL_PRESSURE_RISING'],
			2 => $this->dashletStrings['LBL_PRESSURE_FALLING'],
		);
		
		return $states[$stateCode];		
	}
	
	function getWindDirection($dir) {
		$dir = (round($dir / 45) * 45) % 360;
		return array_get_default($this->dashletStrings, 'LBL_WIND_DEG'.$dir);
	}
}
?>