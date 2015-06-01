<?php

class RTFMerge 
{
	static function listupdate_perform($mu, $perform, &$listFmt, &$list_result, $uids) {
		if ($perform != 'RTFMerge')
			return;
		@set_time_limit(0); 
		// RTF mail merge (by Jason Eggers)
		$mass_mod = $_REQUEST['merge_module'];
		require_once('include/upload_file.php');
		$upload_file = new UploadFile('merge_file');
		if(!$upload_file->confirm_upload(true)) {
			throw new IAHError("Missing RTF template file");
		}

		$file_content = "";
		$file_content = file_get_contents($upload_file->temp_file_location);

		$start_tag = '<<i@h:start>>';
		$start_nobr_tag = '<<i@h:start nopagebreak>>';
		$end_tag = '<<i@h:end>>';

		$startp = strrpos($file_content, $st_tag = $start_tag);
		$s2 = strrpos($file_content, $start_nobr_tag);
		if($s2 !== false && ($s1 === false || $s2 > $s1)) {
			$startp = $s2;
			$st_tag = $start_nobr_tag;
			$nobreak_found = true;
		}
		else
			$nobreak_found = false;
		if($startp === false) {
			$pat = '/^(.*?)(\\\\par)/';
			if(preg_match($pat, $file_content, $m)) {
				$startp = strlen($m[1]);
				$st_tag = '';
			} else {
				throw new IAHError(htmlentities("The template is missing a beginning marker $start_tag"));
			}
		}
		$startp2 = $startp + strlen($st_tag);
		$header = substr($file_content, 0, $startp);
		$endp = strpos($file_content, $end_tag, $startp2);
		if($endp === false) {
			throw new IAHError(htmlentities("The template is missing an ending marker $end_tag"));
		}
		$body = substr($file_content, $startp2, $endp - $startp2 - 1);
		$footer = substr($file_content, $endp + strlen($end_tag));
		$new_body = "";
		$first = true;
						
		require_once('modules/EmailTemplates/EmailTemplate.php');
		$GLOBALS['email_template_use_rtf_format'] = true;
		$mod_strings = return_module_language(null, 'MailMerge');

		//now parse content for each input and add to master
		while(! $list_result->failed) {
			foreach($list_result->getRowIndexes() as $idx) {
				$row = $list_result->getRowResult($idx);
				$main_id = $row->getField('id');
				$object_arr = array();
				$object_arr[$mass_mod] = $main_id;

				if($first == false && $nobreak_found == 0) $new_body .= "\\page";
				$new_body .= EmailTemplate::parse_template($body, $object_arr);
				$first = false;

				$parent_type = '';
				$parent_id = '';

				if ($mass_mod == 'Contacts') {
					$contact = ListQuery::quick_fetch_row('Contact', $main_id, array('id', 'primary_account_id'));
					if ($contact && ! empty($contact['primary_account_id'])) {
						$parent_type = 'Accounts';
						$parent_id = $contact['primary_account_id'];
						$contact_id = $main_id;
					}
				} elseif ($mass_mod == 'Leads') {
					$parent_type = $mass_mod;
					$parent_id = $main_id;
					$contact_id = null;
				}

				if (! empty($parent_type) && ! empty($parent_id)) {
					$note_upd = RowUpdate::for_model('Note');
					$note_upd->loadBlankRecord();

					$note_data = array(
						'name' => $mod_strings['LBL_DOCUMENT_SENT'].": ".$upload_file->original_file_name,
						'description' => $mod_strings['LBL_DOCUMENT']." ".$upload_file->original_file_name." ".$mod_strings['LBL_DOCUMENT_SENT_DESC']." ". gmdate('Y-m-d H:i:s', time()),
						'parent_type' => $parent_type,
						'parent_id' => $parent_id,
						'assigned_user_id' => AppConfig::current_user_id(),
						'contact_id' => $contact_id
					);

					$note_upd->set($note_data);
					$save_result = $note_upd->save();
					if ($save_result && $mass_mod == 'Contacts')
						$note_upd->addUpdateLink('contact', $main_id);
				}
			}
			
			if($list_result->page_finished)
				break;
			$listFmt->pageResult($list_result, true);
		}

		$full_file = $header.$new_body.$footer;

		ob_clean();
		header("Content-type: text/rtf;");
		header("Content-Disposition: attachment; filename=mail_merge.rtf");
		header("Content-transfer-encoding: binary");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT" );
		header('Pragma: public');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Content-Length: ".strlen($full_file));

		print $full_file;

		sugar_cleanup(true);
	}
}

