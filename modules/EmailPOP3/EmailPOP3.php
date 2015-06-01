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


 /*********************************************************************************
 * $Id: $
 ********************************************************************************/

require_once('data/SugarBean.php');


class EmailPOP3 extends SugarBean {
	var $field_name_map;

	// Stored fields
	var $id;
	var $user_id;
	var $name;
	var $host;
	var $username;
	var $password;
	var $email;
    var $leave_on_server;
    var $last_check;
    var $protocol;
    var $imap_folder;
    var $port;
    var $use_ssl;
    var $from_name;
	var $mailbox_type;
	var $template_id;
	var $ooo_template_id;
	var $email_folder_id;
	var $filter_domain;
	var $active = 1;
	var $scheduler = 1;
	var $restrict_since;
	var $mark_read;

	var $table_name = "emails_pop3";
	var $module_dir = 'EmailPOP3';
	var $object_name = "EmailPOP3";

	var $new_schema = true;
	
	static $restrict_since_opts = array(
		'1d', '7d', '30d', '60d', '90d', '6m', '12m', '18m', 'all',
	);
	static $restrict_period_opts = array(
		'd' => 'days', 'm' => 'months', 'y' => 'years',
	);

    function getUserFromAddresses($user_id)
    {
        global $db;
        if (empty($db)) {
            $db = PearDatabase::getInstance();
        }
        $query = "SELECT id, from_name, email FROM emails_pop3 WHERE user_id IN ('$user_id', '-1') AND !deleted";
        $res = $db->query($query);
        $addresses = array();
        while ($row = $db->fetchByAssoc($res)) {
            $addresses[$row['id']] = array($row['from_name'], $row['email']);
        }
        return $addresses;
    }
    
    static function format_restrict_since($val) {
    	global $app_list_strings;
    	$dom =& $app_list_strings['email_import_since_dom'];
    	if(preg_match('/^(\d+)([dmy])$/', $val, $m)) {
    		$lbl = $dom['prev_n_'.self::$restrict_period_opts[$m[2]]];
    		return str_replace('{N}', $m[1], $lbl);
    	}
    	if(isset($dom[$val]))
    		return $dom[$val];
    	return $val;
    }
    
    static function restrict_since_options() {
    	$opts = array();
    	foreach(self::$restrict_since_opts as $s)
    		$opts[$s] = self::format_restrict_since($s);
    	return $opts;
    }
    
    function restrict_since_time($val=null) {
    	if(preg_match('/^(\d+)([dmy])$/', $val, $m)) {
    		return (-$m[1]).' '.self::$restrict_period_opts[$m[2]];
    	}
    	return '';
    }

    static function create_mbox($data) {
        $upd = RowUpdate::blank_for_model('EmailPOP3');
        $upd->set($data);

        if(! $upd->save())
            return false;

        return $upd->getField('id');
    }

    static function get_restrict_options() {
        return EmailPOP3::restrict_since_options();
    }

    static function get_type_options() {
        global $app_list_strings;

        $box_types = $app_list_strings['dom_mailbox_type'];
        //unset($box_types['bounce']);

        return $box_types;
    }

	static function get_protocol_options() {
		global $app_list_strings;
		$options = $app_list_strings['dom_email_server_type'];
		if (AppConfig::setting('site.support_local_mailboxes'))
			$options['LOCAL'] = 'Local mailbox';
        return $options;;
    }

    static function init_js(DetailManager $mgr) {
        $uid = $mgr->getRecord()->getField('user_id');
        $hidden = array(
	        'user_id' => $uid,
	    );
        if($uid != '-1') {
            $hidden['return_module'] = 'Users';
            $hidden['return_action'] = 'DetailView';
            $hidden['return_record'] = $uid;
        	$hidden['return_layout'] = 'Email';
        }
        $layout = $mgr->getLayout();
	    $layout->addFormHiddenFields($hidden);

		$layout->addScriptInclude('modules/EmailPOP3/mailbox.js');
		$layout->addFormInitHook('mailbox_init_form({FORM});');
    }
    
    static function init_record(RowUpdate $upd) {
		if (array_get_default($_REQUEST, 'return_module') == 'Users' && ! empty($_REQUEST['return_record'])) {
			$user_id = $_REQUEST['return_record'];
		} else
			$user_id = '-1'; // group inbox
		$upd->set('user_id', $user_id);
    }
}
?>
