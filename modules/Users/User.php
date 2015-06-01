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

 * Description: TODO:  To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('modules/UserPreferences/UserPreference.php');
require_once ('data/SugarBean.php');

// User is used to store customer information.
class User extends SugarBean {
	// Stored fields
	var $name = '';
	var $full_name;
	var $id;
	var $user_name;
	var $user_hash;
	var $first_name;
	var $last_name;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $created_by;
	var $created_by_name;
	var $modified_by_name;
	var $description;
	var $phone_home;
	var $phone_mobile;
	var $phone_work;
	var $phone_other;
	var $phone_fax;
	var $email1;
	var $email2;
	var $address_street;
	var $address_city;
	var $address_state;
	var $address_postalcode;
	var $address_country;
	var $status;
	var $title;
	var $portal_only;
	var $department;
	var $authenticated = false;
	var $error_string;
	var $is_admin;
	var $employee_status;
	var $messenger_id;
	var $messenger_type;
	var $accept_status; // to support Meetings

	var $receive_notifications;


	// longreach
	var $location;
	var $sms_address;
	var $photo_filename;
	var $in_directory;
	var $last_access;
	var $is_online;
	var $receive_case_notifications;
	// longreach
	var $activity_table_name = "users_activity";


	var $reports_to_name;
	var $reports_to_id;
	var $team_exists = false;
	var $table_name = "users";
	var $module_dir = 'Users';
	var $object_name = "User";

	var $savingpreferencetodb = false;

	var $encodeFields = Array ("first_name", "last_name", "description");

	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = array ('reports_to_name');

	

	var $new_schema = true;

	// longreach - start added
	var $default_order_by = "user_name";
	var $teamLeaders = null;
	var $team = null;
	var $quotation_mode;
	var $project_mode;	
	var $week_start_day;
	var $day_begin_hour;
	var $day_end_hour;
	// longreach - end added

	function User() {
		parent :: SugarBean();



	}
	
    /**
     * returns an admin user
     */
    function getSystemUser() {
        if(null !== $this->retrieve('1')) {
            return $this;
        } else {
            // handle cases where someone deleted user with id "1"
            $q = "SELECT users.id FROM users WHERE users.status = 'Active' AND users.delted = 0 AND users.is_admin = 1";
            $r = $this->db->query($q);
            while($a = $this->db->fetchByAssoc($r)) {
                $this->retrieve($a['id']);
                return $this;
            }
        }
    }
	
	function getDefaultSignature() {
		$where = ''; 
		if($defaultId = $this->getPreference('signature_default')) {
			$where = ' AND id = \''.$defaultId.'\'';
			$q = 'SELECT signature, signature_html FROM users_signatures WHERE user_id = \''.$this->id.'\''.$where;
			$r = $this->db->query($q);
			$a = $this->db->fetchByAssoc($r);
			
			return $a;
		} else {
			return '';
		}
	}
	
	/**
	 * retrieves any signatures that the User may have created as <select>
	 */
	function getSignatures($live=false, $defaultSig='') {
		$q = 'SELECT * FROM users_signatures WHERE user_id = \''.$this->id.'\' AND deleted = 0 ORDER BY name ASC';
		$r = $this->db->query($q);
		$sig = array(""=>"");
		while($a = $this->db->fetchByAssoc($r)) {
			$sig[$a['id']] = $a['name']; 
		}
		$change = '';
		if(!$live) {
			$change = 'onChange="setSigEditButtonVisibility();" ';
		}
		$signs  = '<select '.$change.' id="signature_id" name="signature_id" tabindex="390">';
		$signs .= get_select_options($sig, $defaultSig).'</select>';
		return $signs;
	}
	
	/**
	 * returns buttons and JS for signatures
	 */
	function getSignatureButtons() {
		global $mod_strings;
        $title = $mod_strings['LBL_EMAIL_SIGNATURE'];
		$butts  = '<button type="button" class="input-button input-outer" onclick="open_email_signature_form(\'\', \''.$this->id.'\', \''.$title.'\')"><div class="input-icon icon-add left"></div><span class="input-label">' . $mod_strings['LBL_BUTTON_CREATE'] . '</span></button>&nbsp;';
		$butts .= '<span name="edit_sig" id="getSignatureButtons" style="visibility:hidden;"><button type="button" class="input-button input-outer" onclick="open_email_signature_form(document.DetailForm.signature_id.value, \''.$this->id.'\', \''.$title.'\')"><div class="input-icon icon-edit left"></div><span class="input-label">' . $mod_strings['LBL_BUTTON_EDIT'] . '</span></button>&nbsp;</span>';
		return $butts;
	}
	
	/**
	 * performs a rudimentary check to verify if a given user has setup personal
	 * InboundEmail
	 */
	function hasPersonalEmail() {
		$q = 'SELECT count(id) AS count FROM inbound_email WHERE group_id = \''.$this->id.'\'';
		$r = $this->db->query($q);
		$a = $this->db->fetchByAssoc($r);
		if($a['count'] > 0)
			return true;
		else
			return false;
	}

	/* Returns the User's private GUID; this is unassociated with the User's
	 * actual GUID.  It is used to secure file names that must be HTTP://
	 * accesible, but obfusicated.
	 */
	function getUserPrivGuid() {
        $userPrivGuid = $this->getPreference('userPrivGuid', 'global', $this);
		if ($userPrivGuid) {
			return $userPrivGuid;
		} else {
			$this->setUserPrivGuid();
			if (empty($GLOBALS['_setPrivGuid_'])) {
				$GLOBALS['_setPrivGuid_'] = true;
				$userPrivGuid = $this->getUserPrivGuid();
				return $userPrivGuid;
			} else {
				sugar_die("Breaking Infinite Loop Condition: Could not setUserPrivGuid.");
			}
		}
	}

	function setUserPrivGuid() {
		$privGuid = create_guid();
		//($name, $value, $nosession=0)
		$this->setPreference('userPrivGuid', $privGuid, 0, 'global', $this);
	}

	/**
	 * Alias for setPreference in modules/UserPreferences/UserPreference.php
	 *    
	 */
	function setPreference($name, $value, $nosession = 0, $category = 'global', $user = null) {
        if(isset($user)) {
            UserPreference::setPreference($name, $value, $nosession, $category, $user);
        }
        else {
            UserPreference::setPreference($name, $value, $nosession, $category, $this);
        }
	}

	/**
	 * Alias for setPreference in modules/UserPreferences/UserPreference.php
	 *    
	 */
	function resetPreferences($user = null, $category = null) {
        if(isset($user)) {
            UserPreference::resetPreferences($category, $user);
        }
        else {
            UserPreference::resetPreferences($category, $this);
        }
	}
	
	
	/**
	 * Alias for setPreference in modules/UserPreferences/UserPreference.php
	 *    
	 */
	function savePreferencesToDB($user = null) {
        if(isset($user)) {
            UserPreference::savePreferencesToDB($user); // note the correct spelling!
        }
        else {
            UserPreference::savePreferencesToDB($this); 
        }
	}
	
	/**
	 * Alias for setPreference in modules/UserPreferences/UserPreference.php
	 *    
	 */
	function getUserDateTimePreferences($user = null) {
        if(isset($user)) {
            return UserPreference::getUserDateTimePreferences($user);
        }
        else {
            return UserPreference::getUserDateTimePreferences($this);
        }
          
	}
	
	/**
	 * Alias for setPreference in modules/UserPreferences/UserPreference.php
	 *    
	 */
	function loadPreferences($category = 'global', $user = null, $reload=false) {
        if(isset($user)) {
            UserPreference::loadPreferences($category, $user, $reload);
        }
        else {
            UserPreference::loadPreferences($category, $this, $reload);
        }
	}
	
	/**
	 * Alias for setPreference in modules/UserPreferences/UserPreference.php
	 *    
	 */
	function getPreference($name, $category = 'global', $user = null) {
        if(isset($user)) {
            return UserPreference::getPreference($name, $category, $user);
        }
        else {
            return UserPreference::getPreference($name, $category, $this);
        }
	}

	function save($check_notify = false) {
		// longreach - added $ret
		$ret = parent::save($check_notify);
		
        $this->savePreferencesToDB($this);

		// longreach - added return
		return $ret;
	}
	
    static function before_save(RowUpdate &$upd) {
		self::check_limit($upd);

        if($upd->new_record) {
            $upd->set(array('week_start_day' => AppConfig::setting('company.week_start_day', 0)));
        }
		// if this is an admin user, do not allow portal_only flag to be set
        if($upd->getField('is_admin') && $upd->getField('portal_only'))
        	$upd->set('portal_only', 0);
    }

	static function after_save(RowUpdate &$upd) {
		if($upd->new_record) {
			// create employee to match user
			require_once('modules/HR/Employee.php');
			Employee::create_for_user($upd);
			// create email folders for user
			require_once('modules/EmailFolders/EmailFolder.php');
			EmailFolder::create_default_folders_for_user($upd);
		}

		AppConfig::invalidate_cache('acl');
		if($upd->getPrimaryKeyValue() == AppConfig::current_user_id())
			AppConfig::invalidate_cache('userpref');
		
    	$status = $upd->getField('status');
    	$old_status = $upd->getField('status', null, true);
		$status_changed = ($status=='Active') ^ ($old_status=='Active');
		if($upd->getFieldUpdated('reports_to_id') || $status_changed) {
			self::recalculate_forecasts($upd, $status_changed);
		}
	}
	
    function get_summary_text() {
        $this->_create_proper_name_field();
        return $this->name;
	}

	/**
	* @return string encrypted password for storage in DB and comparison against DB password.
	* @param string $user_name - Must be non null and at least 2 characters
	* @param string $user_password - Must be non null and at least 1 character.
	* @desc Take an unencrypted username and password and return the encrypted password
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function encrypt_password($user_password) {
		// encrypt the password.
		$salt = substr($this->user_name, 0, 2);
		$encrypted_password = crypt($user_password, $salt);

		return $encrypted_password;
	}

	function authenticate_user($password) {

		$query = "SELECT * from $this->table_name where user_name='$this->user_name' AND user_hash='$password' AND (portal_only IS NULL OR portal_only !='1') ";
		//$result = $this->db->requireSingleResult($query, false);
		$result = $this->db->limitQuery($query,0,1,false);
		$a = $this->db->fetchByAssoc($result);
		// set the ID in the seed user.  This can be used for retrieving the full user record later
		if (empty ($a)) {
			// already logging this in load_user() method
			//$GLOBALS['log']->fatal("SECURITY: failed login by $this->user_name");
			return false;
		} else {
			$this->id = $a['id'];
			return true;
		}
	}

    /**
     * retrieves an User bean
     * preformat name & full_name attribute with first/last
     * loads User's preferences
     * 
     * @param string id ID of the User
     * @param bool encode encode the result
     * @return object User bean
     * @return null null if no User found
     */
	function retrieve($id, $encode = true) {
		global $locale;

		$ret = SugarBean :: retrieve($id, $encode);

		if ($ret) {
			// make a properly formatted first and last name
			$this->_create_proper_name_field();

			if (isset ($_SESSION)) {
				$this->loadPreferences();
			}
		}
		return $ret;
	}
	
	/**
	 * Load a user based on the user_name in $this
	 * @return -- this if load was successul and null if load failed.
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	function load_user($user_password) {
		global $login_error;
		unset($GLOBALS['login_error']);
		if(isset ($_SESSION['loginattempts'])) {
			$_SESSION['loginattempts'] += 1;
		} else {
			$_SESSION['loginattempts'] = 1;
		}
		if($_SESSION['loginattempts'] > 5) {
			$ip = query_client_ip();
			$GLOBALS['log']->fatal('SECURITY: '.$this->user_name.' has attempted to login '.$_SESSION['loginattempts'].' times from IP address: '.$ip.'.');
		}
		
		$GLOBALS['log']->debug("Starting user load for $this->user_name");

		if (!isset ($this->user_name) || $this->user_name == "" || !isset ($user_password) || $user_password == "")
			return null;
		
		checkAuthUserStatus();

		$user_hash = strtolower(md5($user_password));
		if($this->authenticate_user($user_hash)) {
			$query = "SELECT * from $this->table_name where id='$this->id'";
		} else {
			$GLOBALS['log']->fatal('SECURITY: User authentication for '.$this->user_name.' failed');
			return null;
		}
		$r = $this->db->limitQuery($query, 0, 1, false);
		$a = $this->db->fetchByAssoc($r);
		if(empty($a) || !empty ($GLOBALS['login_error'])) {
			$GLOBALS['log']->fatal('SECURITY: User authentication for '.$this->user_name.' failed - could not Load User from Database');
			return null;
		}

		// Get the fields for the user
		$row = $a;

		// If there is no user_hash is not present or is out of date, then create a new one.
		if (!isset ($row['user_hash']) || $row['user_hash'] != $user_hash) {
			$query = "UPDATE $this->table_name SET user_hash='$user_hash' where id='{$row['id']}'";
			$this->db->query($query, true, "Error setting new hash for {$row['user_name']}: ");
		}

		// now fill in the fields.
		foreach ($this->column_fields as $field) {
			$GLOBALS['log']->info($field);

			if (isset ($row[$field])) {
				$GLOBALS['log']->info("=".$row[$field]);

				$this-> $field = $row[$field];
			}
		}

		$this->loadPreferences($this);
		require_once ('modules/Administration/updater_utils.php');

		$this->fill_in_additional_detail_fields();
		if ($this->status != "Inactive")
			$this->authenticated = true;
			
			
		// longreach - start added
		if($this->is_authenticated()) {
			if($this->status == 'Info Only') {
				$this->authenticated = false;
				return null;
			}
			$this->update_access_time();
			$GLOBALS['trigger_user_login'] = true;
		}
		// longreach - end added


		unset ($_SESSION['loginattempts']);
		return $this;
	}

	/**
	* @param string $user name - Must be non null and at least 1 character.
	* @param string $user_password - Must be non null and at least 1 character.
	* @param string $new_password - Must be non null and at least 1 character.
	* @return boolean - If passwords pass verification and query succeeds, return true, else return false.
	* @desc Verify that the current password is correct and write the new password to the DB.
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	*/
	function change_password($user_password, $new_password) {
		global $mod_strings;
		global $current_user;
		$GLOBALS['log']->debug("Starting password change for $this->user_name");

		if (!isset ($new_password) || $new_password == "") {
			$this->error_string = $mod_strings['ERR_PASSWORD_CHANGE_FAILED_1'].$current_user['user_name'].$mod_strings['ERR_PASSWORD_CHANGE_FAILED_2'];
			return false;
		}

		$old_user_hash = strtolower(md5($user_password));

		if (!is_admin($current_user)) {
			//check old password first
			$query = "SELECT user_name FROM $this->table_name WHERE user_hash='$old_user_hash' AND id='$this->id'";
			$result = $this->db->query($query, true);
			$row = $this->db->fetchByAssoc($result);
			$GLOBALS['log']->debug("select old password query: $query");
			$GLOBALS['log']->debug("return result of $row");

			if ($row == null) {
				$GLOBALS['log']->warn("Incorrect old password for ".$this->user_name."");
				$this->error_string = $mod_strings['ERR_PASSWORD_INCORRECT_OLD_1'].$this->user_name.$mod_strings['ERR_PASSWORD_INCORRECT_OLD_2'];
				return false;
			}
		}

		$user_hash = strtolower(md5($new_password));

		//set new password
		$query = "UPDATE $this->table_name SET user_hash='$user_hash' where id='$this->id'";
		$this->db->query($query, true, "Error setting new password for $this->user_name: ");
		return true;
	}

	function is_authenticated() {
		return $this->authenticated;
	}


	function retrieve_user_id($user_name) {
		$query = "SELECT id from users where user_name='$user_name' AND deleted=0";
		$result = $this->db->query($query, false, "Error retrieving user ID: ");
		$row = $this->db->fetchByAssoc($result);
		return $row['id'];
	}

	/**
	 * @return -- returns a list of all users in the system.
	 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc..
	 * All Rights Reserved..
	 * Contributor(s): ______________________________________..
	 */
	function verify_data($ieVerified=true) {
		global $mod_strings, $current_user;
		$verified = TRUE;

		if (!empty ($this->id)) {
			// Make sure the user doesn't report to themselves.
			$reports_to_self = 0;
			$check_user = $this->reports_to_id;
			$already_seen_list = array ();
			while (!empty ($check_user)) {
				if (isset ($already_seen_list[$check_user])) {
					// This user doesn't actually report to themselves
					// But someone above them does.
					$reports_to_self = 1;
					break;
				}
				if ($check_user == $this->id) {
					$reports_to_self = 1;
					break;
				}
				$already_seen_list[$check_user] = 1;
				$query = "SELECT reports_to_id FROM users WHERE id='".PearDatabase :: quote($check_user)."'";
				$result = $this->db->query($query, true, "Error checking for reporting-loop");
				$row = $this->db->fetchByAssoc($result);
				$check_user = $row['reports_to_id'];
			}

			if ($reports_to_self == 1) {
				$this->error_string .= $mod_strings['ERR_REPORT_LOOP'];
				$verified = FALSE;
			}
		}

		$query = "SELECT user_name from users where user_name='$this->user_name' AND deleted=0";
		if(!empty($this->id))$query .=  " AND id<>'$this->id'";
		$result = $this->db->query($query, true, "Error selecting possible duplicate users: ");
		$dup_users = $this->db->fetchByAssoc($result);
        
		if (!empty($dup_users)) {
			$this->error_string .= $mod_strings['ERR_USER_NAME_EXISTS_1'].$this->user_name.$mod_strings['ERR_USER_NAME_EXISTS_2'];
			$verified = FALSE;
		}

		if (($current_user->is_admin == "on")) {
            if($this->db->dbType == 'mssql'){
                $query = "SELECT user_name from users where is_admin = 1 AND deleted=0";
            }else{
                $query = "SELECT user_name from users where is_admin = 'on' AND deleted=0";                
            }
			$result = $this->db->query($query, true, "Error selecting possible duplicate users: ");
			$remaining_admins = $this->db->getRowCount($result);

			if (($remaining_admins <= 1) && ($this->is_admin != "on") && ($this->id == $current_user->id)) {
				$GLOBALS['log']->debug("Number of remaining administrator accounts: {$remaining_admins}");
				$this->error_string .= $mod_strings['ERR_LAST_ADMIN_1'].$this->user_name.$mod_strings['ERR_LAST_ADMIN_2'];
				$verified = FALSE;
			}
		}
		///////////////////////////////////////////////////////////////////////
		////	InboundEmail verification failure
		if(!$ieVerified) {
			$verified = false;
			$this->error_string .= '<br />'.$mod_strings['ERR_EMAIL_NO_OPTS'];
		}
		
		
		// longreach - added
		//if(! $this->_check_limit()) $verified = false;


		return $verified;
	}


	/**
	 * Generate the name field from the first_name and last_name fields.
	 */
	function _create_proper_name_field() {
        global $locale;
        $full_name = $locale->getLocaleFormattedName($this->first_name, $this->last_name);
        $this->name = $full_name;
        $this->full_name = $full_name; 

	}


	function getPreferredEmail() {
		$ret = array ();
		$prefName = $this->getPreference('mail_fromname');
		$prefAddr = $this->getPreference('mail_fromaddress');

		if (isset ($prefAddr) && !empty ($prefAddr)) {
			$ret['name'] = $prefName;
			$ret['email'] = $prefAddr;
		}
		elseif (isset ($this->email1) && !empty ($this->email1)) {
			$ret['name'] = trim($this->first_name.' '.$this->last_name);
			$ret['email'] = $this->email1;
		}
		elseif (isset ($this->email2) && !empty ($this->email2)) {
			$ret['name'] = trim($this->first_name.' '.$this->last_name);
			$ret['email'] = $this->email2;
		} else {
			require_once ('modules/Emails/Email.php');
			$email = new Email();
			$ret = $email->getSystemDefaultEmail();
		}

		return $ret;
	}
	
    /**
     * returns User's email address based on descending order of preferences
     * 
     * @param string id GUID of target user if needed
     * @return array Assoc array for an email and name
     */
    function getEmailInfo($id='') {
        $user = $this;
        if(!empty($id)) {
            $user = new User();
            $user->retrieve($id);
        }
        
        // from name
        $fromName = $user->getPreference('mail_fromname');
        if(empty($fromName)) {
        	// cn: bug 8586 - localized name format
            $fromName = $user->full_name;
        }
        
        // from address
        $fromAddr = $user->getPreference('mail_fromaddress');
        if(empty($fromAddr)) {
            if(!empty($user->email1) && isset($user->email1)) {
                $fromAddr = $user->email1;
            } elseif(!empty($user->email2) && isset($user->email2)) {
                $fromAddr = $user->email2;
            } else {
                $r = $user->db->query("SELECT value FROM config WHERE name = 'fromaddress'");
                $a = $user->db->fetchByAssoc($r);
                $fromAddr = $a['value'];
            }
        }
        
        $ret['name'] = $fromName;
        $ret['email'] = $fromAddr;
        
        return $ret;
    }
    
    
	/**
	 * gets a human-readable explanation of the format macro
	 * @return string Human readable name format
	 */
	static function getLocaleFormatDesc($plain = false) {
		global $locale;
		global $mod_strings;
		global $app_strings;
		
		$format['f'] = $mod_strings['LBL_LOCALE_DESC_FIRST'];
		$format['l'] = $mod_strings['LBL_LOCALE_DESC_LAST'];
		$format['s'] = $mod_strings['LBL_LOCALE_DESC_SALUTATION'];
		
		$name['f'] = $app_strings['LBL_LOCALE_NAME_EXAMPLE_FIRST'];
		$name['l'] = $app_strings['LBL_LOCALE_NAME_EXAMPLE_LAST'];
		$name['s'] = $app_strings['LBL_LOCALE_NAME_EXAMPLE_SALUTATION'];
		
		$macro = $locale->getLocaleFormatMacro();
		
		$ret1 = '';
		$ret2 = '';
		// longreach - modified
		for($i=0; $i<strlen($macro); $i++) {
			$char = $macro[$i];
			if(array_key_exists($char, $format)) {
				if ($plain) {
					$ret1 .= $format[$char];
					$ret2 .= $name[$char];
				} else {
					$ret1 .= "<i>".$format[$char]."</i>";
					$ret2 .= "<i>".$name[$char]."</i>";
				}
			} else {
				$ret1 .= $char;
				$ret2 .= $char;
			}
		}
		if ($plain) return $ret1 . "\n" . $ret2;
		return $ret1."<br />".$ret2;
	}




	// longreach - start added
	function is_team_leader()
	{
		$result = $this->db->query("select count(id) from `{$this->table_name}` where reports_to_id='" . PearDatabase::quote($this->id) . "' and deleted != 1");
		if ($row = $this->db->fetchByAssoc($result)) {
			if (0 < current($row))
				return true;
		}
		return false;
	}
 
 
	function getTeam($add_quotes=true)
	{
		if (is_null($this->team)) {
			$this->team = array($this->id);
			$result = $this->db->query("select id, reports_to_id from `{$this->table_name}` where deleted != 1 and status='Active'");
			$source = array();
			while ($source[] = $this->db->fetchByAssoc($result));
			array_pop($source);
			$this->createTeamList($source, $this->id, true);
		}
		$GLOBALS['log']->debug('TEAM for ' . $this->user_name);
		$GLOBALS['log']->debug($this->team);
		if($add_quotes) {
			foreach($this->team as $k => $v)
				$this->team[$k] = "'$v'";
		}
		return $this->team;
	}
 

	function createTeamList($source, $id, $override = false)
	{
		foreach ($source as $value) {
			if ($id == $value['reports_to_id']
				&& !in_array($value['id'], $this->team, true)
				) {
				$this->team[] = $value['id'];
				$this->createTeamList($source, $value['id']);
			}
		}
	}
	
	function getTeamLeaders()
	{
		if (is_null($this->teamLeaders)) {
			$this->teamLeaders = array($this->id);
			$id = $this->id;
			while (null != $id) {
				$result = $this->db->query(
					"SELECT other.id FROM users LEFT JOIN users other
					 ON other.id=users.reports_to_id AND other.status='Active' AND other.deleted=0
					 WHERE users.id='" . PearDatabase::quote($id) . "' AND users.deleted = 0 AND users.status='Active'",1);
				if ($row = $this->db->fetchByAssoc($result)) {
					if (null != ($id = current($row)))
						$this->teamLeaders[] = $id;
				}
				else $id = null;
			}
		}
		return $this->teamLeaders;
	}


	static function recalculate_forecasts(RowUpdate &$user, $status_changed) {
		global $db;
		
		require_once 'modules/Forecasts/Forecast.php';
		require_once 'modules/Forecasts/ForecastCalculator.php';
		$seedForecast = new Forecast();
		$period 			= AppConfig::setting('company.forecast_period');
		$fiscal_year_start	= AppConfig::setting('company.fiscal_year_start');
		$user_id = $user->getPrimaryKeyValue();
		
		if ($status_changed) {
			$db->query("UPDATE forecasts SET deleted = 1 WHERE user_id='{$user_id}'");
			if ($user->getField('status') == 'Active') {
				$forecast_periods 	= AppConfig::setting('company.retain_forecast_periods');
				$history_periods 	= AppConfig::setting('company.retain_history_periods');
				$user_data = array(
					$user_id => array(
						'id' => $user_id,
						'user_name' => $user->getField('user_name'),
						'reports_to_id' => $user->getField('reports_to_id'),
					)
				);
				$fcalc = new ForecastCalculator($period, $user_data);
				for ($i = -$history_periods; $i < $forecast_periods; $i ++) {
					$fcalc->fill_forecasts($i, $fiscal_year_start, true);
				}
				$fcalc->cleanup();
			}
		}
		
		$fcalc = new ForecastCalculator($period);
		
		$old_reports_to = $user->getField('reports_to_id', null, true);
		if ($old_reports_to && $user->getFieldUpdated('reports_to_id')) {
			$fcalc->update_user_team_forecasts($old_reports_to);
		}
		$fcalc->update_user_team_forecasts($user_id);
		
		$fcalc->cleanup();
		$seedForecast->cleanup();
	}

	// longreach - start added - insert a row in the activity log - called when a user logs in
	function insert_access_time() {
		if(! $this->id) return;
		$this->update_access_time();
		$query = "DELETE FROM $this->activity_table_name WHERE user_id='$this->id'".
			" AND access_time < NOW() - INTERVAL 1 HOUR";
		$this->db->query($query, false);
	}
	
	// longreach - added - update access time for the current user
	function update_access_time() {
		if(! $this->id) return;
		$sess_id = $this->db->quote(session_id());
		$query = "INSERT INTO $this->activity_table_name (user_id, session_id, access_time) "
				. "VALUES ('$this->id', '$sess_id', NOW()) "
				. "ON DUPLICATE KEY UPDATE access_time=VALUES(access_time)";
		$result = $this->db->query($query, false);
	}
	
	// longreach - added - get last access time for a user
	function get_last_access_time() {
		$query = "SELECT MAX(access_time) AS last_access,".
			" (MAX(access_time) > NOW() - INTERVAL 15 MINUTE) AS is_online".
			" FROM $this->activity_table_name".
			" WHERE user_id='$this->id' AND NOT deleted";
		$result = $this->db->limitQuery($query,0,1, false);
		if(!empty($result)) {
			$row = $this->db->fetchByAssoc($result);
			$this->last_access = $row['last_access'];
			$this->is_online = $row['is_online'];
		}
		return $this->last_access;
	}
	
	// longreach - added - mark user's activity session as deleted
	function logged_out_session() {
		$sess_id = session_id();
		$query = "UPDATE $this->activity_table_name SET deleted=1".
			" WHERE user_id='$this->id' AND session_id='$sess_id'";
		$this->db->query($query, false);
	}

	// longreach - added
	static function check_limit(RowUpdate &$upd) {
		global $db, $log;
		$old_status = $upd->getField('status', 'Info Only', true);
		$new_status = $upd->getField('status');
		$old_portal = $upd->getField('portal_only', 1, true);
		$new_portal = $upd->getField('portal_only');
		if($new_status == 'Active' && $old_status != 'Active' && !$new_portal) {
			$query = "SELECT COUNT(*) as num_users FROM users WHERE status='Active' AND NOT portal_only AND NOT deleted";
			$result = $db->query($query, true, "Error selecting user count");
			$count = $db->fetchByAssoc($result);
			if($count['num_users'] >= max_users()) {
				$_SESSION['user_update_error'] = translate('ERR_TOO_MANY_ACTIVE_USERS', 'Users');
				$log->fatal('User status update disallowed - active limit exceeded');
				if($old_status == 'Active')
					$upd->set('portal_only', 1);
				else
					$upd->set('status', $old_status);
				return false;
			}
		}
		return true;
	}

	function retrieveAdminUser()
	{
		// try standard admin user first
		if ($admin = $this->retrieve('1')) {
			return $admin;
		}
		$query = 'SELECT id FROM users WHERE is_admin AND ! deleted LIMIT 1';
		$res = $this->db->query($query, true);
		$row = $this->db->fetchByAssoc($res);
		return $this->retrieve($row['id']);
	}

	function createTempPreferences()
	{
		if (isset($_SESSION[$this->user_name."_PREFERENCES"])) {
			$_SESSION["_PREFERENCES"] = $_SESSION[$this->user_name."_PREFERENCES"];
		}
	}

	function removeTempPreferences()
	{
		unset($_SESSION["_PREFERENCES"]);
	}

	function fillNewUserDefaults()
	{
	}
	
	// longreach - end added

	function get_hide_portal_only_where($params)
	{
		return empty($params['value']) ? '1' : "!users.portal_only";
	}

	function getDefaultListWhereClause()
	{
		return "!users.portal_only";
	}
	
	function invalidate_emails_count() {
		$_SESSION['email_last_count_time'.$this->id] = 0;
	}
	
	function get_new_emails_count() {
		$import_ts = $this->getPreference('new_email_timestamp', 'email_import');
		$count_ts = array_get_default($_SESSION, 'email_last_count_time'.$this->id);
		$last_count = array_get_default($_SESSION, 'email_last_count');
		if(isset($count_ts) && $count_ts > time() - 3*3600 && (! isset($import_ts) || $import_ts < $count_ts))
			return $last_count;
		
		$new_msgs = 0;
		require_once 'modules/EmailFolders/EmailFolder.php';
		$folders = EmailFolder::get_folders_list($this->id);
		foreach ($folders as $folder) {
			if ($folder['reserved'] == STD_FOLDER_INBOX && $folder['newmessages'] > 0) {
				$new_msgs = $folder['newmessages'];
				break;
			}
		}
		$_SESSION['email_last_count'] = $new_msgs;
		$_SESSION['email_last_count_time'.$this->id] = time();
		return $new_msgs;
	}

    static function rewrite_onsubmit(DetailManager $mgr) {
        if ($mgr->layout_name == 'Display') {
            global $pageInstance;
            $pageInstance->add_js_literal("setOnSubmitEvent();", null, LOAD_PRIORITY_FOOT);
        }
    }

    /**
     * Get a list of all users that are direct
     * or indirect reports to the current user
     *
     * @static
     * @param string $user_id
     * @param bool $add_finder_id
     * @param bool $add_indirect
     *
     * @return array
     */
    static function get_team_list($user_id, $add_finder_id = true, $add_indirect = true) {
        $lq = new ListQuery('User', array('id', 'reports_to_id'));
        $lq->addSimpleFilter('status', 'Active');
        $result = $lq->fetchAll();

        $team = array();
        if ($result)
            $team = User::fill_team($result->rows, $user_id, $add_indirect);

        if ($add_finder_id)
            $team[] = $user_id;

        return $team;
    }

    /**
     * Grab team members IDs from Active users list
     *
     * @static
     * @param array $users
     * @param string $user_id
     * @param bool $override
     * @return array
     */
    static function fill_team($users, $user_id, $add_indirect = true, $override = false) {
        static $result = array();
        if ($override)
            $result = array();

        foreach ($users as $value) {
            if ($user_id == $value['reports_to_id'] && !in_array($value['id'], $result, true)) {
                $result[] = $value['id'];
                if ($add_indirect)
                    User::fill_team($users, $value['id']);
            }
        }

        return $result;
    }

    static function is_online($id) {
        global $db;

        $query = "SELECT (MAX(access_time) > NOW() - INTERVAL 15 MINUTE) AS is_online".
            " FROM `users_activity`".
            " WHERE user_id='$id' AND NOT deleted";

        $result = $db->limitQuery($query, 0, 1, false);
        $status = false;

        if(! empty($result)) {
            $row = $db->fetchByAssoc($result);
            if ($row['is_online'] == '1')
                $status = true;
        }

        return $status;
    }

    static function format_cost($projecttask_id, $cost, $symbol = true) {
        require_once('include/utils/html_utils.php');
        global $db;

        $query = "SELECT currency_id, exchange_rate FROM project_task WHERE id = '".$projecttask_id."'";
        $result = $db->limitQuery($query, 0, 1, false);
        $params = array('name' => 'actual_cost', 'type' => 'currency', 'entered_currency_id' => '-99');
        $currency_id = '-99';

        if (! $symbol)
            $params['currency_symbol'] = false;

        if(! empty($result)) {
            $row = $db->fetchByAssoc($result);
            if (sizeof($row) > 0) {
                $currency_id = $row['currency_id'];
                $params['exchange_rate'] = $row['exchange_rate'];
                $params['convert'] = true;
            }
        }

        $params['currency_id'] = $currency_id;
        $formatted_cost = html2plaintext(currency_format_number($cost, $params));

        return $formatted_cost;
    }

    static function get_hourly_rate($user_id) {
        global $db;
        $hourly_rate = 0;

        $query = "SELECT std_hourly_rate_usdollar AS hourly_rate FROM employees WHERE user_id = '".$user_id."'";
        $result = $db->limitQuery($query, 0, 1, false);

        if (! empty($result)) {
            $row = $db->fetchByAssoc($result);

            if (! empty($row['hourly_rate']))
                $hourly_rate = $row['hourly_rate'];
        }

        return $hourly_rate;
    }

    static function get_actual_data($projecttask_id, $user_id) {
        global $db;

        $query = "SELECT SUM(quantity) AS mins, SUM(paid_total_usd) AS cost FROM booked_hours WHERE NOT deleted AND related_type='ProjectTask' AND related_id='".$projecttask_id."'
            AND assigned_user_id='".$user_id."'";
        $result = $db->limitQuery($query, 0, 1, false);

        $data = array();

        if(! empty($result)) {
            $row = $db->fetchByAssoc($result);
            if (sizeof($row) > 0)
                $data = $row;
        }

        return $data;
    }

    static function calc_actual_hours($projecttask_id, $user_id) {
        $actual_hours = 0;
        $data = User::get_actual_data($projecttask_id, $user_id);

        if (isset($data['mins']))
            $actual_hours = $data['mins'];

        $fmt = new FieldFormatter('html', 'view');
        $spec = array('name' => 'quantity', 'type' => 'duration');
        $empty_row = array();
        $actual_hours = $fmt->formatRowValue($spec, $empty_row, $actual_hours);

        return $actual_hours;
    }

    static function calc_actual_cost($projecttask_id, $user_id) {
        $actual_cost = 0;
        $data = User::get_actual_data($projecttask_id, $user_id);

        if (isset($data['cost']))
            $actual_cost = $data['cost'];

        $actual_cost = User::format_cost($projecttask_id, $actual_cost);

        return $actual_cost;
    }

    static function calc_estim_cost($projecttask_id, $user_id, $estim_hours, $cost_custom, $cost_usdollar) {
        if (! $estim_hours)
            $estim_hours = 0;

        if ($cost_custom && ! empty($cost_usdollar)) {
            $hourly_rate = $cost_usdollar;
        } else {
            $hourly_rate = User::get_hourly_rate($user_id);
        }

        $estim_cost = $estim_hours * $hourly_rate;
        $estim_cost = User::format_cost($projecttask_id, $estim_cost);

        return $estim_cost;
    }
    
	static function get_status_options() {
		global $app_list_strings;
		return array('' => translate('LBL_ANY_STATUS', 'Users'), 'NotInactive' => translate('LBL_NOT_INACTIVE', 'Users')) + $app_list_strings['user_status_dom'];
	}
        
	static function get_search_status_where($param) {
		$name = $param['name'];
		if ($param['value'] == 'NotInactive') return "users.$name != 'Inactive'";
		else if(! $param['value']) return;
		else return "users.$name = '" . PearDatabase::quote($param['value']) . "'";
	}
	
	static function update_acl(RowUpdate $upd, $link_name) {
		$model = $upd->getModelName();
		if($model == 'User' && ($link_name == 'aclroles' || $link_name == 'securitygroups')) {
			AppConfig::invalidate_cache('acl');
		}
	}
	
} // end class definition

?>
