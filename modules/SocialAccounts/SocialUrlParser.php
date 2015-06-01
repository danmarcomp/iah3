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
class SocialUrlParser {

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $site;

    /**
     * @param string $value
     */
    public function setUrl($value) {
        $url = $value;
        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false)
            $url = 'http://' . $url;

        $this->url = $url;
    }

    /**
     * @param string $url
     * @param string $site
     */
    public function __construct($url, $site=null) {
        $this->site = $site;
        $this->setUrl($url);
    }

    /**
     * Run URL parsing
     *
     * @return array:
     * ['login'] - social site login
     * ['params'] - social site parameters
     */
    public function run() {
        $parsed = parse_url($this->url);
        $result = array();

        if (isset($parsed['host']) && ($site = $this->hostSupported($parsed['host']))) {
        	if(! $this->site) $this->site = $site;
        	$params = $this->extractParams($parsed);
        	if($params && ! empty($params['login'])) {
        		$result['type'] = $this->site;
				$result['login'] = $params['login'];
				$result['link_type'] = $params['link_type'];
				unset($params['login']);
				unset($params['link_type']);
				$params['entered_url'] = $this->url;
				$result['params'] = $params;
			}
        }

        return $result;
    }

    /**
     * Extract social login from profile URL
     *
     * @param array $parsed_url
     * @return string
     */
    private function extractParams($parsed_url) {
        $method = $this->site.'_parser';
        $login = '';

        if (method_exists($this, $method))
            $params = $this->$method($parsed_url);

        return $params;
    }

    /**
     * Parse Facebook profile URL
     *
     * @param array $parsed_url
     * @return string
     */
    private function facebook_parser($parsed_url) {
        $login = '';
        $link_type = 'profile';

        if (preg_match('%(?:facebook?\.com/(?:[^/]+/.+/|(?:(groups|pages|profile\.php)?)/|.*[?&]id=)|facebook/)([^"&?/ ]+)%i', $this->url, $id)) {
        	if($id[1] != 'profile.php')
        		$link_type = $id[1];
            $login = $id[2];
        } elseif (isset($parsed_url['path'])) {
            $id = explode('/', $parsed_url['path']);
            if (isset($id[1]))
                $login = $id[1];
            $link_type = 'vanity';
        }

        return compact('login', 'link_type');
    }


    /**
     * Parse XING profile URL
     *
     * @param array $parsed_url
     * @return string
     */
    private function xing_parser($parsed_url) {
        $login = '';
        $link_type = 'profile';

		if (isset($parsed_url['path'])) {
			$id = explode('/', $parsed_url['path']);
			if (isset($id[1]) && ($id[1] == 'profile' || $id[1] == 'net') && ! empty($id[2])) {
				$link_type = $id[1];
                $login = $id[2];
			}
        }

        return compact('login', 'link_type');
    }


    /**
     * Parse LinkedIn profile URL
     *
     * @param array $parsed_url
     * @return mixed|string
     */
    private function linkedin_parser($parsed_url) {
        $login = '';
        $link_type = 'profile';

        if (isset($parsed_url['path']) && strpos($parsed_url['path'], '/pub/') !== false) {
            $login = str_replace('/pub/', '', $parsed_url['path']);
            $link_type = 'pub';
        } elseif (preg_match('%(?:linkedin?\.com/(?:[^/]+/.+/|(?:(profile\/view|in|groups|pub|company)?)/|.*[?&]id=|gid=)|linkedin/)([^"&?/ ]+)%i', $this->url, $id)) {
        	if($id[1] != 'profile/view')
        		$link_type = $id[1];
            $login = $id[2];
        }

        return compact('login', 'link_type');
    }

    /**
     * Parse Google+ profile URL
     *
     * @param array $parsed_url
     * @return string
     */
    private function google_plus_parser($parsed_url) {
        $login = '';
        $query_str = '';
        $link_type = 'profile';
        
        if (isset($parsed_url['fragment'])) {
            $query_str = '/' . $parsed_url['fragment'];
        } elseif (isset($parsed_url['path'])) {
            $query_str = $parsed_url['path'];
        }

        if ($query_str != '') {
            $id = explode('/', $query_str);
            if (isset($id[1]) && is_numeric($id[1]))
                $login = $id[1];
            else if(isset($id[1]) && $id[1] == 'u' && count($id) >= 4)
            	$login = $id[3];
        }

        return compact('login', 'link_type');
    }

    /**
     * Parse Twitter profile URL
     *
     * @param array $parsed_url
     * @return string
     */
    private function twitter_parser($parsed_url) {
        $login = '';
        $query_str = '';
        $link_type = 'profile';

        if (isset($parsed_url['fragment'])) {
            $query_str = $parsed_url['fragment'];
        } elseif (isset($parsed_url['path'])) {
            $query_str = $parsed_url['path'];
        }

        if ($query_str != '') {
            $id = explode('/', $query_str);
            if (isset($id[1]))
                $login = $id[1];
        }

        return compact('login', 'link_type');
    }

    /**
     *
     * @param string $host
     * @return bool
     */
    private function hostSupported($host) {
        $supported_hosts = $this->getSupportedHosts();
        $clen_host = str_replace('www.', '', $host);

        if ($this->site == 'linkedin') {
            $hot_parts = explode('.', $clen_host);

            if (sizeof($hot_parts) > 2)
                $clen_host = $hot_parts[1] .'.'. $hot_parts[2];
        }

        if (array_key_exists($clen_host, $supported_hosts)) {
        	if(! $this->site || $supported_hosts[$clen_host] == $this->site)
        		return $supported_hosts[$clen_host];
        } else {
            return false;
        }
    }

    /**
     *
     * @return array
     */
    private function getSupportedHosts() {
        require_once('modules/SocialAccounts/SocialAccount.php');
        $networks = SocialAccount::getSupportedNetworks();
        $hosts = array();

        foreach ($networks as $type => $details) {
            $url = str_replace('www.', '', $details['domain']);
            $hosts[$url] = $type;
        }

        return $hosts;
    }
}
?>