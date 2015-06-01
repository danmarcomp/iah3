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


require_once('include/layout/forms/FormTableSection.php');

class BackupHistoryWidget extends FormTableSection {
	static $SHOW_BACKUP_FILES_NUM = 10;
	
    function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'backup_history';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

		$lstyle = $gen->getLayout()->getType();
		if($lstyle == 'editview') {
			return $this->renderHtmlEdit($gen, $row_result);
		}
		return '';
	}
	
	function getRequiredFields() {
        return array();
    }
    
    function getLabel() {
    	$l = parent::getLabel();
    	if(! $l) $l = translate('LBL_BACKUP_HISTORY', 'Configurator');
    	return $l;
    }

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
		global $mod_strings;
	
		$backupFiles = getBackupsList();
		$backupCount = min(count($backupFiles), self::$SHOW_BACKUP_FILES_NUM);
	
		$header = $body = '';
		if ($backupCount) {
			$header = str_replace('{NUM}', self::$SHOW_BACKUP_FILES_NUM, $mod_strings['LBL_BACKUP_LAST_BACKUPS']);
			$header = "<tr><td class=\"dataLabel\" colspan=\"2\"><b>{$header}:</b></td></tr>";
			for($i = 0; $i < $backupCount; $i++) {
				$fileInfo = $backupFiles[$i];
				$style = ($i == 0 ? "font-weight: bold" : "");
				$number = $i + 1;
				$size = format_file_size($fileInfo['size']);
				$body .= "<tr style=\"{$style}\">
						<td class=\"dataLabel\">{$number}. {$fileInfo['name']}</td>
						<td class=\"dataLabel\">({$size})</td>
					</tr>";
			}
		} else {
			$body = '<tr><td class="dataLabel" colspan="2">'. $mod_strings['LBL_BACKUP_NO_FILES'] . '</td></tr>';
		}
	
		$table = <<<EOQ
			<table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top: 0.5em" class="tabForm" id="{$this->id}">
			<thead>
				<tr>
					<th align="left" colspan="4" class="dataLabel"><h4 class="dataLabel">{$mod_strings['LBL_BACKUP_HISTORY']}</h4></td>
				</tr>
				{$header}
			</thead>
			<tbody>
				{$body}
			</tbody>
			</table>
EOQ;
			
		return $table;
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {
		
	}

	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {

	}
}
?>