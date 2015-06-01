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
require_once('include/database/ListQuery.php');

class SocialAccount {

    /**
     * @var string
     */
    private $type;

    /**
     * @var string
     */
    private $login;

    /**
     * @var string
     */
    private $link_type;

    /**
     * @var string
     */
    private $params;

    /**
     * @var string
     */
    private $related_type;

    /**
     * @var string
     */
    private $related_id;

    /**
     * @var RowUpdate
     */
    private $update_obj;

    /**
     * @var array
     */
    public static $supported_modules = array('Accounts', 'Contacts', 'Leads');

    const ICONS_PATH = 'modules/SocialAccounts/icons/';

    const ICON_SIZE = 28;

    const ICON_SMALL_SIZE = 15;

    const ICON_MOBILE_SIZE = 20;

    /**
     * @param array $fields
     */
    public function __construct($fields = array()) {
        foreach ($fields as $name => $value) {
        	if($name == 'params')
        		$this->setParams($value);
            else if (property_exists(get_class($this), $name))
                $this->$name = $value;
        }

        $this->update_obj = null;
    }
    
    
    public static function quick_create_row($url, $related_type, $related_id) {
		require_once('modules/SocialAccounts/SocialUrlParser.php');

		$parser = new SocialUrlParser($url);
		$parse_result = $parser->run();
		if($parse_result && ! empty($parse_result['login'])) {
			$parse_result['related_type'] = $related_type;
			$parse_result['related_id'] = $related_id;
			$social_account = new SocialAccount($parse_result);
			return $social_account->prepareInsertRow();
		}
	}
    

    /**
     * @param string $value
     */
    public function setLogin($value) {
        $this->login = $value;
    }
    
    /**
     * @param string $value
     */
    public function setLinkType($value=null) {
    	if(! $value) $value = 'profile';
        $this->link_type = $value;
    }

    /**
     * @param string $value
     */
    public function setParams($value) {
    	if(is_array($value))
			$this->params = serialize($value);
		else
			$this->params = $value;
    }

    /**
     * @return mixed
     */
    public function getParams() {
        $result = unserialize($this->params);
        if ($result) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     *
     * @return bool
     */
    public function load() {
        $lq = new ListQuery('social_accounts', true);

        $clauses = array(
            "rel_type" => array(
                "value" => $this->related_type,
                "field" => 'related_type',
            ),
            "rel_id" => array(
                "value" => $this->related_id,
                "field" => 'related_id'
            ),
            "network_type" => array(
                "value" => $this->type,
                "field" => 'type'
            )
        );

        $lq->addFilterClauses($clauses);
        $result = $lq->runQuerySingle();

        if (! $result->failed) {
            $this->update_obj = RowUpdate::for_result($result);
            $this->login = $this->update_obj->getField('login');
            $this->link_type = $this->update_obj->getField('link_type');
            $this->params = $this->update_obj->getField('params');
            return true;
        } else {
        	$this->update_obj = $this->prepareInsertRow();
            return false;
        }
    }
    
    public function prepareInsertRow() {
		$row = RowUpdate::blank_for_model('social_accounts');
		$row->set(array(
			'related_type' => $this->related_type,
			'related_id' => $this->related_id,
			'type' => $this->type,
			'login' => $this->login,
			'link_type' => $this->link_type,
			'params' => $this->params,
		));
		return $row;
    }

    /**
     * Save Social Account
     *
     */
    public function save() {
        if ($this->update_obj && ! empty($this->login)) {
            $this->update_obj->set('login', $this->login);
            $this->update_obj->set('link_type', $this->link_type);
            if (! empty($this->params))
                $this->update_obj->set('params', $this->params);
            $this->update_obj->save();
        }

    }

    /**
     * Delete Social Account
     *
     */
    public function delete() {
        if ($this->update_obj && $this->update_obj->getPrimaryKeyValue() != '')
            $this->update_obj->deleteRow();
    }

    /**
     * Get chain of social networks icons
     *
     * @param bool $list - for list view
     * @return string
     */
    public function getIconsChain($list = false) {
        $result = '';

        if ($this->moduleSupported($this->related_type)) {
            $accounts = self::getList($this->related_type, $this->related_id);
            $network = self::getSupportedNetworks();

            if (sizeof($accounts) > 0) {
                foreach ($accounts as $type => $details) {
                    $params = array();
                    if (isset($details['params']))
                        $params = unserialize($details['params']);

                    if (isset($params['entered_url'])) {
                        $html = $this->getIconLinkHtml($params['entered_url'], $network[$type]['icon'], $list);
                        $result .= $html;
                    }
                }

				if(! empty($result)) {
					if($list)
						$result = '<div style="text-align: right; white-space: nowrap; margin-left: 3px">' .$result. '</div>';
					else
						$result = '<div style="float: right; white-space: nowrap">' .$result. '</div>';
                }
            }
        }

        return $result;
    }

    /**
     * Get social icon link's html block
     *
     * @param string $url
     * @param string $icon_file
     * @param bool $small - icon size
     * @return string
     */
    public function getIconLinkHtml($url, $icon_file, $small) {
        $html = '';
        $size = self::ICON_SIZE;
        if(AppConfig::is_mobile())
        	$size = self::ICON_MOBILE_SIZE;
        else if ($small)
        	$size = self::ICON_SMALL_SIZE;
        $icon = $this->getIcon($icon_file, $size);

        if ($icon) {
        	$marg = $small ? 'margin-right: 3px' : 'margin-left: 5px';
        	if($url) $icon = '<a style="outline: 0 none; '.$marg.';" href="' .to_html($url). '" target="_blank">' . $icon . '</a>';
            $html = $icon;
        }

        return $html;
    }

    /**
     * Get social network icon
     *
     * @param string $icon_file
     * @param string $size
     * @return string
     */
    public function getIcon($icon_file, $size = '') {
        $icon = self::ICONS_PATH . $icon_file;
        $icon_img = '';

        if ($icon_file && file_exists($icon)) {
            if ($size == '')
                $size = self::ICON_SIZE;
            $icon = to_html($icon . '?s=' . js_version_ident());
            $icon_img = '<img width="' .$size. '" height="' .$size. '" border="0" src="' .$icon. '" />';
        }

        return $icon_img;
    }
    
    /**
     * Get JSON data for editor
     *
     * @return array
     */
    public function getJsonData() {
		return array(
            'type' => $this->type,
            'login' => $this->login,
            'link_type' => $this->link_type,
            'params' => $this->getParams(),
            '_display' => $this->getDisplayName(),
            'icon' => $this->getIcon(self::getNetworkIcon($this->type)),
            'url' => $this->getLinkUrl(),
        );
    }
    
    /**
     * Get display name for account
     *
     * @return string
     */
    public function getDisplayName() {
		return $this->login;
    }
    
    
    /**
     * Get link to profile/group page
     *
     * @return string
     */
    public function getLinkUrl() {
    	$p = $this->getParams();
    	if(! empty($p['entered_url']))
    		return $p['entered_url'];
    }

    
    /**
     *
     * @param string $name
     * @return bool
     */
    public function networkSupported($name) {
        $networks = SocialAccount::getSupportedNetworks();
        if (array_key_exists($name, $networks) && ! empty($networks[$name]['enabled'])) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Current module in supported list?
     *
     * @param string $name
     * @return bool
     */
    private function moduleSupported($name) {
        return in_array($name, self::$supported_modules);
    }

    /**
     * Get Social Accounts list for related record
     *
     * @static
     * @param string $related_type - related Module
     * @param string $related_id - related record ID
     * @return array
     */
    public static function getList($related_type, $related_id) {
        $lq = new ListQuery('social_accounts', true);

        $clauses = array(
            "rel_type" => array(
                "value" => $related_type,
                "field" => 'related_type',
            ),
            "rel_id" => array(
                "value" => $related_id,
                "field" => 'related_id'
            )
        );

        $lq->addFilterClauses($clauses);
        $lq->setOrderBy('type');
        $result = $lq->fetchAll();
        $list = array();

        if (! $result->failed && (is_array($result->rows) && sizeof($result->rows) > 0) ) {
            foreach ($result->rows as $details) {
                $list[$details['type']] = $details;
            }
        }

        return $list;
    }

    /**
     * Delete all Social Accounts for related record
     *
     * @static
     * @param string $related_type - related Module
     * @param $related_id - related record ID
     */
    public static function deleteAllForRelated($related_type, $related_id) {
        global $db;
        $query = "DELETE FROM `social_accounts` WHERE `related_type` = '" .$related_type. "' AND `related_id` = '" .$related_id. "'";
        $db->query($query);
    }

    /**
     * Get list of supported Social Networks
     *
     * @static
     * @return mixed
     */
    public static function getSupportedNetworks() {
        return AppConfig::setting('social.networks');
    }
    
    /**
     * Get icon for a given social network
     *
     * @static
     * @return string
     */
    public static function getNetworkIcon($network) {
    	if($network)
			return AppConfig::setting("social.networks.$network.icon");
    }

}
?>