<?php
require_once('modules/LicenseInfo/LicenseHistory.php');

class SystemUpdateManager {

    public static function check_for_updates() {
        $manager = new self();
        return $manager->checkUpdates();
    }

    public static function reset_updates() {
        $manager = new self();
        $manager->resetUpdates();
    }

    public static function is_enabled() {
    	return !! AppConfig::setting('site.features.update_check_enabled');
    }

    /**
     * @param  array $updates
     */
    public static function add_updates($updates) {
        $manager = new self();
        $manager->saveUpdates($updates);
    }

    public function checkUpdates() {
        $params = $this->prepareData();
        return $this->sendRequest($params);
    }

    public function getUpdatesTable($only_body = false) {
        global $mod_strings, $timedate;
        $html = '';
        $system_updates = $this->getAllUpdates();

        if (! $only_body)
            $html .= '<table width="100%" border="0" cellspacing="0" cellpadding="0" class="tabDetailView"><tbody id="updates_table">';

        $last_time_checked = AppConfig::setting('scheduler.updates_last_checked');
        if (empty($last_time_checked))
            $last_time_checked = $mod_strings['LBL_UPDATES_NOT_CHECKED'];
        else
            $last_time_checked = $timedate->to_relative_date_time($last_time_checked);

        if(sizeof($system_updates) > 0) {
            $msg_updates = '';
        } else {
            $msg_updates = $mod_strings['LBL_NO_UPDATES'] . ' ';
        }
        $msg_checked = str_replace('{TIME}', $last_time_checked, $mod_strings['LBL_UPDATED_TIME_CHECKED']);

        $html .= <<<EOQ
            <tr><th class="tabDetailViewDL" colspan="5">
                <h4 class="tabDetailViewDL">{$mod_strings['LBL_UPDATES_TITLE']}</h4>
            </th></tr>
            <tr><td class="tabDetailViewDL" colspan="5" style="text-align: left; padding-top: 0.8em; padding-bottom: 0.8em">
                <p>{$msg_updates}{$msg_checked}</p>
                <button onclick="return check_for_updates();" class="form-button input-outer" type="button" name="check_updates" id="check_updates">
                    <div class="input-icon left icon-recur"></div><span class="input-label">{$mod_strings['LBL_UPDATES_BUTTON_LABEL']}</span>
                </button>
            </td></tr>
EOQ;

        if (sizeof($system_updates) > 0) {
            $html .= <<<EOQ
                <tr>
                    <td width="16%" nowrap="" style="text-align: center; font-weight: bold;" class="tabDetailViewDL">{$mod_strings['LBL_PRODUCT']}</td>
                    <td width="12%" nowrap="" style="text-align: center; font-weight: bold;" class="tabDetailViewDL">{$mod_strings['LBL_DATE_POSTED']}</td>
                    <td width="12%" nowrap="" style="text-align: center; font-weight: bold;" class="tabDetailViewDL">{$mod_strings['LBL_PACKAGE']}</td>
                    <td width="20%" nowrap="" style="text-align: center; font-weight: bold;" class="tabDetailViewDL">{$mod_strings['LBL_NOTES_LINK']}</td>
                    <td width="40%" nowrap="" style="text-align: center; font-weight: bold;" class="tabDetailViewDL">{$mod_strings['LBL_PACKAGE_NOTES']}</td>
                </tr>
EOQ;

            foreach ($system_updates as $id => $update) {
                $notes = $update['notes'];
                if (empty($update['notes']))
                    $notes = '&mdash;';
                else
                	$notes = nl2br(to_html($notes));

                $notes_link = '&mdash;';
                if (! empty($update['notes_link']))
                    $notes_link = '<a class="tabDetailViewDFLink" target="_blank" href="' .to_html($update['notes_link']). '">' .$mod_strings['LBL_RELEASE_NOTES']. '</a>';

                $name = $update['version'] .'-'. $update['package'] .'-'. $update['type'];
                
                if (! empty($update['download_link'])) {
                    $package = '<a class="tabDetailViewDFLink" target="_blank" href="' .to_html($update['download_link']). '">' .to_html($name). '</a>';
                } else {
                    $package = $name;    
                }
                
                $html .= <<<EOQ
                    <tr>
                        <td nowrap="" style="text-align: center;" class="tabDetailViewDL">{$update['product']}</td>
                        <td nowrap="" style="text-align: center;" class="tabDetailViewDF">{$update['date_posted']}</td>
                        <td nowrap="" style="text-align: center;" class="tabDetailViewDF">{$package}</td>
                        <td nowrap="" style="text-align: center;" class="tabDetailViewDF">{$notes_link}</td>
                        <td style="text-align: left;" class="tabDetailViewDF">{$notes}</td>
                    </tr>
EOQ;
            }
        }

        if (! $only_body) {
            $html .= '</tbody></table>';

            /** @var $pageInstance IncludeManager */
            global $pageInstance;
            $pageInstance->add_js_include('modules/LicenseInfo/system_updates.js', null, LOAD_PRIORITY_FOOT);
        }

        return $html;
    }

    private function prepareData() {
        /** @var $db DBManager */
    	global $db;
    	global $timedate;

        $data = array(
        	'license_id' => '',
        	'iah_version' => AppConfig::version(),
        	'php_version' => phpversion(),
        	'db_lib' => $db->getLibraryName(),
        	'db_version' => $db->getServerVersion(),
        	'active_users' => $this->countActiveUsers(),
        	'os_name' => php_uname('s'),
        	'os_version' => php_uname('v'),
        	'host_name' => php_uname('n'),
        );

        $license_history = new LicenseHistory();
        if($license_history->retrieve_latest() !== null) {
            $exts = $license_history->get_extensions();
            $data['license_id'] = $license_history->license_id;
            $data['license_vendor_id'] = $license_history->vendor_id;
            $data['active_limit'] = $license_history->ext_active_limit;
            $data['date_support_end'] = $timedate->to_db_date($license_history->ext_support_end, false);
            $data['product_list'] = $license_history->ext_product_list;
        }

        $params = array();
        foreach($data as $key => $value) {
            array_push($params, array('name' => $key, 'value' => $value));
        }

        return array('data' => $params, 'modules' => $this->getInstalledModules($data));
    }

    private function getInstalledModules() {
        $lq = new ListQuery('UpgradeHistory');
        $result = $lq->runQuery();
        $mods = array();

        if ($result->getResultCount()) {
            foreach ($result->getRows() as $row) {

                if (isset($mods[$row['id_name']])) {
                    $compare = version_compare($row['version'], $mods[$row['id_name']]);
                    if ($compare < 1)
                        continue;
                }

                $mods[$row['id_name']] = $row['version'];
            }
        }

        $return = array();
        foreach($mods as $id => $version) {
            array_push($return, array('name' => $id, 'value' => $version));
        }

        return $return;
    }

	private function countActiveUsers() {
        /** @var $db DBManager */
		global $db;
	
		$query = "SELECT COUNT(*) as active_users FROM users WHERE status = 'Active' AND NOT portal_only AND NOT deleted";
		$result = $db->query($query, false);
	
		if($result && ($count = $db->fetchByAssoc($result)) !== null) {
			return $count['active_users'];
		} else {
			return 0;
		}
	}

    private function sendRequest($request) {
        $result = null;

        if (is_array($request) && sizeof($request) > 0) {
            global $vendor_info;
            require_once('vendor_info.php');
            $error = null;

            if(class_exists('SoapClient')) {
				try {
					$sc = @new SoapClient($vendor_info['license_server'].'?wsdl', array(
						'encoding' => 'utf-8',
						'exceptions' => true,
						//'trace' => true,
						'cache_wsdl' => WSDL_CACHE_NONE,
						'features' => SOAP_USE_XSI_ARRAY_TYPE));
					$result = $sc->check_updates(array('data' => $request['data'], 'modules' => $request['modules']));

                    if($result)
						$updates = $result->updates;
					else
						$error = "No response from server";

				} catch (SoapFault $e) {
					$error = $e->faultstring;
				}
			} else {
				require_once('include/nusoap/nusoap.php');
				$soap = new nusoap_client($vendor_info['license_server'], false, false, false, false, false, 30, 30);

				$data = $soap->serialize_val($request['data']); // bit of a hack for 4.2 nusoap class
                $modules = $soap->serialize_val($request['modules']); // bit of a hack for 4.2 nusoap class
                $result = $soap->call("check_updates", array('data' => $data, 'modules' => $modules));
            	$error = $soap->getError();
            	if(! $error)
            		$updates = $result['updates'];
            }

            if ($error)
                return $error;
            
            if(! empty($updates) && is_array($updates)) {
				$result = array();
				foreach($updates as $update) {
					$upd = array();
					foreach($update as $arr) {
						if(is_object($arr))
							$upd[$arr->name] = $arr->value;
						else
							$upd[$arr['name']] = $arr['value'];
					}
					if(! empty($upd['package']))
						$result[$upd['package']] = $upd;
				}
            }
        }

        return $result;
    }

    private function resetUpdates() {
        /** @var $db DBManager */
        global $db;
        $db->query('TRUNCATE TABLE system_updates');
    }

    private function saveUpdates($updates) {
        if (sizeof($updates) > 0) {
            foreach ($updates as $package => $data) {
                $upd = RowUpdate::blank_for_model('system_updates');
                $upd->set($data);
                $upd->save();
            }
        }
    }

    /**
     * @return array
     */
    private function getAllUpdates() {
        $result = ListQuery::quick_fetch_all('system_updates');
        return $result->getRows();
    }
}
