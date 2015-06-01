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
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$json_supported_actions['add_social_account'] = array();

function json_add_social_account() {
    require_once('modules/SocialAccounts/SocialAccount.php');
    require_once('modules/SocialAccounts/SocialUrlParser.php');

    $url = array_get_default($_REQUEST, 'url');
    $type = array_get_default($_REQUEST, 'type');

    $result = 'failure';
    $data = null;

    $url = urldecode($url);
    $parser = new SocialUrlParser($url, $type);
    $parse_result = $parser->run();

    if (sizeof($parse_result) > 0 && ! empty($parse_result['login'])) {
        $social_account = new SocialAccount($parse_result);
        $networks = SocialAccount::getSupportedNetworks();

        if (! $social_account->load()) {
            $result = 'ok';
			$data = $social_account->getJsonData();
        }
    }
	
    $return = compact('result', 'data');
    json_return_value($return);
}

$json_supported_actions['delete_social_account'] = array();

function json_delete_social_account() {
    require_once('modules/SocialAccounts/SocialAccount.php');
    $type = array_get_default($_REQUEST, 'type');
    $related_type = array_get_default($_REQUEST, 'related_type');
    $related_id = array_get_default($_REQUEST, 'related_id');
    $return = array();
    $result = 'failure';
    
    // FIXME - check edit permissions on related record

    $social_account = new SocialAccount(array('type' => $type, 'related_type' => $related_type, 'related_id' => $related_id));

    if ($social_account->load()) {
        $social_account->delete();
        $result = 'ok';
    }

    $return['result'] = $result;
    json_return_value($return);
}
