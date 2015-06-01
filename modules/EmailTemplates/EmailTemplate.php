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

// EmailTemplate is used to store email email_template information.
class EmailTemplate extends SugarBean {
	var $field_name_map = array();
	// Stored fields
	var $id;
	var $date_entered;
	var $date_modified;
	var $modified_user_id;
	var $created_by;
	var $assigned_user_id;
	var $created_by_name;
	var $modified_by_name;
	var $assigned_user_name;
	var $name;
	var $published;
	var $description;
	var $body;
	var $body_html;
	var $attachments;
	var $from_name;
	var $from_address;
	var $subject;

	var $table_name = "email_templates";
	var $object_name = "EmailTemplate";
	var $module_dir = "EmailTemplates";
	var $new_schema = true;
	// This is used to retrieve related fields from form posts.
	var $additional_column_fields = array();
	
	function EmailTemplate() {
		parent::SugarBean();
	}

	function get_summary_text() {
		return "$this->name";
	}

	function create_list_query($order_by, $where, $show_deleted=0) {
		$custom_join = $this->custom_fields && $this->custom_fields->getJOIN();
		$query = 'SELECT email_templates.id, email_templates.name, email_templates.description, email_templates.date_modified ';

		if($custom_join) {
   				$query .= $custom_join['select'];
 			}
		$query .= ' FROM email_templates ';
		if($custom_join) {
  				$query .= $custom_join['join'];
			}

		$where_auto = '1 = 1';
		if($show_deleted == 0) {
			$where_auto = "email_templates.deleted=0";
		}else if($show_deleted == 1) {
			$where_auto = "email_templates.deleted=1";
		}
			
		if($where != "")
			$query .= "WHERE $where AND ".$where_auto;
		else
			$query .= "WHERE ".$where_auto;

		if($order_by != "")
			$query .= " ORDER BY $order_by";
		else
			$query .= " ORDER BY email_templates.name";

		return $query;
	}

	function create_export_query(&$order_by, &$where) {
		return $this->create_list_query($order_by, $where);
	}

	function fill_in_additional_list_fields() {
		$this->fill_in_additional_parent_fields();
	}

	function fill_in_additional_detail_fields() {
		$this->created_by_name = get_assigned_user_name($this->created_by);
		$this->modified_by_name = get_assigned_user_name($this->modified_user_id);
		$this->assigned_user_name = get_assigned_user_name($this->assigned_user_id);
		$this->fill_in_additional_parent_fields();
	}

	function fill_in_additional_parent_fields() {
	}
	
	function get_list_view_data() {
		global $app_list_strings, $focus, $action, $currentModule;
		$fields = $this->get_list_view_array();
		return $fields;
	}

	//function all string that match the pattern {.} , also catches the list of found strings.
	//the cache will get refreshed when the template bean instance changes.
	//The found url key patterns are replaced with name value pairs provided as function parameter. $tracked_urls.
	//$url_template is used to construct the url for the email message. the template should have place holder for 1 varaible parameter, represented by %1
	//$template_text_array is a list of text strings that need to be searched. usually the subject, html body and text body of the email message.
	//$removeme_url_template, if the url has is_optout property checked then use this template.
	function parse_tracker_urls($template_text_array,$url_template,$tracked_urls,$removeme_url_template) {
		global $beanFiles,$beanList, $app_list_strings;	
        $this->parsed_urls=array();

		//parse the template and find all the dynamic strings that need replacement.
		$pattern = '/\{([^\}]+)\}/';
		foreach ($template_text_array as $key=>$template_text) {
			if (!empty($template_text)) {
				if(!isset($this->parsed_urls[$key])) {
					$matches=array();
                    $count=preg_match_all($pattern,$template_text,$matches,PREG_OFFSET_CAPTURE);
					$this->parsed_urls[$key]=$matches;		
				} else {
					$matches=$this->parsed_urls[$key];
					if(!empty($matches[0])) {
						$count=count($matches[0]);
					} else {
						$count=0;
					}
				}
				
				//navigate thru all the matched keys and replace the keys with actual strings.
				for ($i=($count -1); $i>=0; $i--) {
                    $new_pattern = '/(?:\{|%7B)tracker_([a-zA-Z0-9\-]+)_tracker_([^\}]*)(?:\}|%7D)/'; // cn: bug 6638, find multibyte strings
                    $new_matches = array();
                    if (preg_match($new_pattern, $matches[0][$i][0], $new_matches, PREG_OFFSET_CAPTURE)) {
                        $new_matches = array(
                            0 => array(
                                $i => array(
                                    0 => $matches[0][$i][0],
                                    1 => $matches[0][$i][1],
                                ),
                            ),
                            1 => array(
                                $i => array(
                                    0 => $new_matches[1][0],
                                    1 => $new_matches[1][1] + $matches[0][$i][1],
                                ),
                            ),
                            2 => array(
                                $i => array(
                                    0 => $new_matches[2][0],
                                    1 => $new_matches[2][1] + $matches[0][$i][1],
                                ),
                            ),
                        );


                        $url_key_name=$new_matches[1][$i][0];
        
                        if (!empty($tracked_urls[$url_key_name])) {
                            if ($tracked_urls[$url_key_name]['is_optout']==1){
                                $tracker_url = sprintf($removeme_url_template, $tracked_urls[$url_key_name]['id']);
                            } else {
                                $tracker_url = sprintf($url_template,$tracked_urls[$url_key_name]['id']);
                                $tracker_url .= '&_r=' . base64_encode($new_matches[2][$i][0]);
                            }
                        }
                        $template_text=substr_replace($template_text,$tracker_url,$new_matches[0][$i][1], strlen($new_matches[0][$i][0]));
                    } else {
                        foreach ($tracked_urls as $url_info) {
                            if ($url_info['tracker_name'] == $matches[1][$i][0]) {
                                if ($url_info['is_optout']) {
                                    $tracker_url = sprintf($removeme_url_template, $url_info['id']);
                                } else {
                                    $tracker_url = sprintf($url_template, $url_info['id']);
                                }
                                $template_text=substr_replace($template_text, $tracker_url, $matches[0][$i][1], strlen($matches[0][$i][0]));
                            }
                        }
                    }
				}
			}
			$return_array[$key]=$template_text;
        }
		return $return_array;		
	}
			
	function parse_email_template($template_text_array,$focus_name, $focus) {
		global $beanFiles,$beanList, $app_list_strings;	
		
		if(!isset($this->parsed_entities)) $this->parsed_entities=array();

		//parse the template and find all the dynamic strings that need replacement.
		$model = AppConfig::module_primary_bean($focus_name);
		$pattern_prefix='$'.strtolower($model).'_';
		$pattern_prefix_length=strlen($pattern_prefix);
		$pattern='/\\'.$pattern_prefix.'[A-Za-z_0-9]*/';
		foreach ($template_text_array as $key=>$template_text) {
			if(!isset($this->parsed_entities[$key])) {
				$matches=array();
				$count=preg_match_all($pattern,$template_text,$matches,PREG_OFFSET_CAPTURE);
				if($count != 0) {
					for ($i=($count -1); $i>=0; $i--) {
						if(!isset($matches[0][$i][2])) {
							//find the field name in the bean.	
							$matches[0][$i][2]=substr($matches[0][$i][0],$pattern_prefix_length,strlen($matches[0][$i][0]) - $pattern_prefix_length);
							
							//store the localized strings if the field is of type enum..
							if(isset($focus->field_defs[$matches[0][$i][2]]) && $focus->field_defs[$matches[0][$i][2]]['type']=='enum' && isset($focus->field_defs[$matches[0][$i][2]]['options'])) {
								$matches[0][$i][3]=$focus->field_defs[$matches[0][$i][2]]['options'];	
							}
						}
					}	
				}
				$this->parsed_entities[$key]=$matches;						
			} else {
				$matches=$this->parsed_entities[$key];
				if(!empty($matches[0])) {
					$count=count($matches[0]);
				} else {
					$count=0;
				}
			}
			for ($i=($count -1); $i>=0; $i--) {
				$field_name=$matches[0][$i][2];
				$value=$focus->{$field_name};
				//check dom
				if(isset($matches[0][$i][3])) {
					if(isset($app_list_strings[$matches[0][$i][3]][$value])) {
						$value=$app_list_strings[$matches[0][$i][3]][$value];
					}					
				} 
				$template_text=substr_replace($template_text,$value,$matches[0][$i][1], strlen($matches[0][$i][0]));
			}
			
			//parse the template for tracker url strings. patter for these strings in {[a-zA-Z_0-9]+}

			$return_array[$key]=$template_text;
		}
		
		return $return_array;
	}

	function parse_template_bean($string, $bean_name, &$focus) {
		global $beanFiles, $beanList;
		$repl_arr = array();

		// cn: bug 9277 - create a replace array with empty strings to blank-out invalid vars
		if(!class_exists('Account')) require_once('modules/Accounts/Account.php');
		if(!class_exists('Contact')) require_once('modules/Contacts/Contact.php');
		if(!class_exists('Lead')) require_once('modules/Leads/Lead.php');
		if(!class_exists('Prospect')) require_once('modules/Prospects/Prospect.php');
		$acct = new Account();
		$contact = new Contact();
		$lead = new Lead();
		$prospect = new Prospect();

		// longreach - removed
		/*
		foreach($lead->field_defs as $field_def) {
			if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
         		continue;
			}
			$repl_arr["contact_".$field_def['name']] = '';
			$repl_arr["contact_account_".$field_def['name']] = '';
		}
		foreach($prospect->field_defs as $field_def) {
			if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
         		continue;
			}
			$repl_arr["contact_".$field_def['name']] = '';
			$repl_arr["contact_account_".$field_def['name']] = '';
		}
		 */

		// feel for Parent account, only for Contacts traditionally, but written for future expansion
		if(isset($focus->account_id) && !empty($focus->account_id)) {
			$acct->retrieve($focus->account_id);
		}

		foreach($focus->field_defs as $field_def) {
			if(isset($focus->$field_def['name'])) {
				if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
             		continue;
				}

				if($field_def['type'] == 'enum') {
					$translated = translate($field_def['options'],$bean_name,$focus->$field_def['name']);

					if(isset($translated) && ! is_array($translated)) {
						$repl_arr[strtolower($beanList[$bean_name])."_".$field_def['name']] = $translated;
					} else { // unset enum field, make sure we have a match string to replace with ""
						$repl_arr[strtolower($beanList[$bean_name])."_".$field_def['name']] = '';
					}
				} else {
					$repl_arr[strtolower($beanList[$bean_name])."_".$field_def['name']] = $focus->$field_def['name'];
				}
			} else {
				if($field_def['name'] == 'full_name') {
					$repl_arr[strtolower($beanList[$bean_name]).'_full_name'] = $focus->get_summary_text();
				} else {
					$repl_arr[strtolower($beanList[$bean_name])."_".$field_def['name']] = '';
				}
			}
		} // end foreach()

		if($bean_name == 'Contacts') {
			// cn: bug 9277 - email templates not loading account/opp info for templates
			if(!empty($acct->id)) {
				$defs = AppConfig::setting('model.fields.Account');
				foreach($defs as $field_def) {
					if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
	             		continue;
					}

					if($field_def['type'] == 'enum') {
						$translated = translate($field_def['options'], 'Accounts' ,$acct->$field_def['name']);

						if(isset($translated) && ! is_array($translated)) {
							$repl_arr["account_".$field_def['name']] = $translated;
							$repl_arr["contact_account_".$field_def['name']] = $translated;
						} else { // unset enum field, make sure we have a match string to replace with ""
							$repl_arr["account_".$field_def['name']] = '';
							$repl_arr["contact_account_".$field_def['name']] = '';
						}
					} else {
						$repl_arr["account_".$field_def['name']] = $acct->$field_def['name'];
						$repl_arr["contact_account_".$field_def['name']] = $acct->$field_def['name'];
					}
				}
			}
		// longreach - modified
		} elseif($bean_name == 'Accounts') {
			// assumed we have an Account in focus
			$defs = AppConfig::setting('model.fields.Contact');
			foreach($defs as $field_def) {
				if(($field_def['type'] == 'relate' && empty($field_def['custom_type'])) || $field_def['type'] == 'assigned_user_name') {
             		continue;
				}

				if($field_def['type'] == 'enum') {
					$translated = translate($field_def['options'], 'Accounts' ,$contact->$field_def['name']);

					if(isset($translated) && ! is_array($translated)) {
						$repl_arr["contact_".$field_def['name']] = $translated;
						$repl_arr["contact_account_".$field_def['name']] = $translated;
					} else { // unset enum field, make sure we have a match string to replace with ""
						$repl_arr["contact_".$field_def['name']] = '';
						$repl_arr["contact_account_".$field_def['name']] = '';
					}
				} else {
					$repl_arr["contact_".$field_def['name']] = $contact->$field_def['name'];
					$repl_arr["contact_account_".$field_def['name']] = $contact->$field_def['name'];
				}
			}
		}

		// longreach - start added - RTF mail merge
		if(! empty($GLOBALS['email_template_use_rtf_format'])) {
			foreach ($repl_arr as $name=>$value) {
				$repl_arr[$name] = rtf_encode($value);
			}
		}
		// longreach - end added


		krsort($repl_arr);
		reset($repl_arr);

		foreach ($repl_arr as $name=>$value) {
			if($value != '') {
				$string = str_replace("\$$name",$value,$string);
			} else {
				$string = str_replace("\$$name", ' ', $string);
			}
		}

		return $string;
	}

	// longreach - modified -- added $override
    // temp method for old code
	function parse_template_temp($string, &$bean_arr, $override = array()) {
		global $beanFiles,$beanList;

		foreach ($bean_arr as $bean_name=>$bean_id) {
			require_once($beanFiles[$beanList[$bean_name]]);

			$focus = new $beanList[$bean_name];
			$result=$focus->retrieve($bean_id);
			if(empty($result)) {
				//sugar_die("bean not found by id: ".$bean_id);
				continue;
			}
			
			if($bean_name == 'Leads' || $bean_name == 'Prospects') {
				$bean_name = 'Contacts';
			}
			// longreach - start added
			if (isset($override[$bean_id])) {
				$bean_name = $override[$bean_id];
			}
			// longreach - end added
			// longreach - start added
			$focus->field_defs['iah_url'] = array(
				'name' => 'iah_url',
				'type' => 'varchar',
				'source' => 'non-db',
			);
			$focus->iah_url = AppConfig::site_url() . '/index.php?module=' . $focus->module_dir . '&action=DetailView&record='.$focus->id;
			// longreach - end added
			if(isset($this) && $this->module_dir == 'EmailTemplates') {
				$string = $this->parse_template_bean($string, $bean_name, $focus);
			} else {
				$string = EmailTemplate::parse_template_bean($string, $bean_name, $focus);
			}
		}
		// longreach - modified
		return preg_replace('/\\$[a-z][a-z_0-9]*\b/i', '', $string);
	}

    /**
     * Parse email template (replace variables to values)
     *
     * @static
     * @param string $string - email body
     * @param  array $bean_arr: array('parent_type' => 'parent_id')
     * @param array $override
     * @return mixed
     */
    static function parse_template($string, &$bean_arr, $override = array()) {

        foreach ($bean_arr as $module=>$bean_id) {
            //if($module == 'Leads' || $module == 'Prospects')
                //$module = 'Contacts';
            $bean =  AppConfig::setting("modinfo.primary_beans.$module");
            $lq = new ListQuery($bean);
            $lq->addFilterPrimaryKey($bean_id);
            $result = $lq->runQuerySingle();
            if($result->failed) continue;


            if (isset($override[$bean_id]))
                $module = $override[$bean_id];

            $string = EmailTemplate::replace_fields($string, $module, $result, $lq);
        }

        return preg_replace('/\\$[a-z][a-z_0-9]*\b/i', '', $string);
    }

    /**
     * Replace template variables to values
     * 
     * @static
     * @param string $string - email body
     * @param string $module - parent email object module
     * @param RowResult $row_result
     * @param ListQuery $query
     * @return mixed
     */
    static function replace_fields($string, $module, RowResult &$row_result, ListQuery &$query) {
        $bean =  AppConfig::setting("modinfo.primary_beans.$module");

        if($module == 'Leads' || $module == 'Prospects') {
            $bean_index = array(strtolower($bean), 'contact');
        } else {
            $bean_index = strtolower($bean);
        }

        $repl_arr = EmailTemplate::fill_bean_fields($row_result->row, $query, $module, $bean_index);
        $repl_arr[$bean_index.'_iah_url'] = AppConfig::site_url() . '/index.php?module='.$module.'&action=DetailView&record='.$row_result->getField('id');

        $repl_acc_arr = array();
        $repl_cont_arr = array();

        if ($module == 'Contacts') {
            $account_id = $row_result->getField('primary_contact_for');
            if (! $account_id)
            	$account_id = $row_result->getField('primary_account_id');
            if ($account_id) {
                $account_query = new ListQuery('Account');
                $account_query->addFilterPrimaryKey($account_id);
                $account_result = $account_query->runQuerySingle();
                if(! $account_result->failed) {
                    $index = array('account', 'contact_account');
                    $repl_acc_arr = EmailTemplate::fill_bean_fields($account_result->row, $account_query, 'Account', $index);
                }
            }
        } elseif ($module == 'Accounts') {
            //TODO I think bug in old code because account can has many contacts
            //$index = array('contact', 'contact_account');
        }

        $repl_arr = $repl_arr + $repl_acc_arr + $repl_cont_arr;

        //RTF mail merge
        if(! empty($GLOBALS['email_template_use_rtf_format'])) {
            foreach ($repl_arr as $name => $value) {
                $repl_arr[$name] = rtf_encode($value);
            }
        }

        krsort($repl_arr);
        reset($repl_arr);

        foreach ($repl_arr as $name => $value) {
            if($value != '') {
                $string = str_replace("\$$name", $value, $string);
            } else {
                $string = str_replace("\$$name", ' ', $string);
            }
        }

        return $string;
    }

    /**
     * Fill array with bean fields values (for replace)
     * 
     * @static
     * @param array $fields: array('name' => 'value')
     * @param ListQuery $query
     * @param string $module
     * @param mixed $bean_index - string or array
     * just bean name in lower case or combination of bean names
     * for dependent beans (example: account or contact_account)
     * @return array
     */
    static function fill_bean_fields($fields, ListQuery &$query, $module, $bean_index) {
        $repl_arr = array();
        $bean =  AppConfig::setting("modinfo.primary_beans.$module");
        $err = '';
        $spec = array('name' => '');

        foreach ($fields as $field_name => $field_value) {
            $spec['name'] = $field_name;
            $def = $query->getFieldDefinition($spec, $err);
            if ($field_value == null)
                $field_value = '';

            if ($def) {

                if ($def['type'] == 'id' && empty($def['custom_type']))
                    continue;

                if ($def['type'] == 'enum') {
                    $translated = translate($def['options'], $module, $field_value);

                    if ($translated && ! is_array($translated)) {
                        $field_value = $translated;
                    } else {
                        $field_value = '';
                    }
                }

                if (! is_array($bean_index)) {
                    $repl_arr[$bean_index.'_'.$field_name] = $field_value;
                } else {
                    $repl_arr[$bean_index[0].'_'.$field_name] = $field_value;
                    $repl_arr[$bean_index[1].'_'.$field_name] = $field_value;
                }
            }
        }

        $spec['name'] = 'name';
        $name_def =  $query->getFieldDefinition($spec, $err);
        if (isset($name_def['source']['fields'])) {
            $full_name = ListQuery::quick_fetch_row($bean, $fields['id'], array('name'));
            if ($full_name) {
                if (! is_array($bean_index)) {
                    $repl_arr[$bean_index.'_full_name'] = $full_name['name'];
                } else {
                    $repl_arr[$bean_index[0].'_full_name'] = $full_name['name'];
                    $repl_arr[$bean_index[1].'_full_name'] = $full_name['name'];
                }
            }
        }

        return $repl_arr;
    }

	function bean_implements($interface) {
		switch($interface) {
			case 'ACL':return true;
		}
		return false;
	}

    static function get_folder_options() {
        global $app_strings;
        $lq = new ListQuery('EmailTemplate', array('id', 'name'));
        $acl = $lq->getAclOwnerClauses('list');
        $lq->addFilterClauses($acl);
        $result = $lq->fetchAll('name');

        $options = array('' => $app_strings['LBL_NONE']);

        if (! $result->failed) {
            $templates = $result->rows;

            foreach ($templates as $id => $details) {
                $options[$id] = $details['name'];
            }
        }

        return $options;
    }
}
?>
