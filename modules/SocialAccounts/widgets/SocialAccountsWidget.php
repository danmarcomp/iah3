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
require_once('modules/SocialAccounts/SocialAccount.php');

class SocialAccountsWidget extends FormTableSection {

    function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'social_accounts';
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
    	if(! $l) $l = translate('LBL_SOCIAL_ACCOUNTS', 'SocialAccounts');
    	return $l;
    }

	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $gen->getLayout()->addScriptInclude('modules/SocialAccounts/social.js', LOAD_PRIORITY_FOOT);
        $gen->getLayout()->addJSLanguage('SocialAccounts');

        $body = <<<EOQ
            <table width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top: 0.5em" class="tabForm">
            <thead>
                <tr>
                <th align="left" colspan="4" class="dataLabel"><h4 class="dataLabel">
                		{$this->getLabel()}
                		<span style="margin: -2px 0 -2px 2em">{$this->renderMenuSource($gen, 'add_social_network')}</span>
                	</h4></th>
                </tr>
            </thead>
            <tbody style="" id="social_accounts">
            </tbody>
            </table>
EOQ;

		$rel_mod = $row_result->getModuleDir();
		$rel_id = $row_result->getField('id');
		if($rel_mod == 'Contacts' && ! $rel_id && ($lead_id = array_get_default($_REQUEST, 'lead_id')) ) {
			$rel_mod = 'Leads';
			$rel_id = $lead_id;
		}
        $list = SocialAccount::getList($rel_mod, $rel_id);
        $networks = SocialAccount::getSupportedNetworks();
        $rows = array();
        $networks_dom = array();

        foreach ($networks as $type => $details) {
			$vmod = array_get_default($details, 'vname_module', 'SocialAccounts');
			$networks_dom[$type] = array('label' => translate($details['vname'], $vmod));

            if (isset($list[$type])) {
                $social_account = new SocialAccount($list[$type]);
				$rows[] = $social_account->getJsonData();
            }
        }

        $json = getJSONobj();
        $networks_opts = array('keys' => array_keys($networks_dom), 'values' => $networks_dom);
        $networks_json = $json->encode($networks_opts);
        $rows_json = $json->encode($rows);
        
        $gen->getLayout()->addScriptLiteral(
        	"SocialAccount.init('{$row_result->getModuleDir()}', $networks_json, $rows_json); SUGAR.ui.registerInput('{$gen->getFormName()}', SocialAccount);",
        	LOAD_PRIORITY_FOOT);

        return $body;
	}

    /**
     * Render menu source
     *
     * @param HtmlFormGenerator $gen
     * @param string $name
     * @return string
     */
    function renderMenuSource(HtmlFormGenerator &$gen, $name) {
        $spec = array('name' => $name);

		$attribs = array(
			'id' => $name,
			'name' => $gen->getFormName() . '_' . $name,
			'class' => 'input-button input-outer menu-source',
			'icon' => 'input-icon icon-add',
			'type' => 'button',
			'label' => translate('LBL_SOCIAL_SELECT_SITE', 'SocialAccounts'),
			'show_arrow' => true,
		);
		$ret = EditableForm::render_button($attribs);
		$gen->getLayout()->addScriptLiteral(
			"SUGAR.ui.registerMenuSource('{$attribs['id']}', {icon_key: 'icon'}, '{$gen->getFormName()}');",
			LOAD_PRIORITY_FOOT);
        return $ret;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        if(isset($input['social_accounts'])) {
        	$json = getJSONobj();
        	$data = $json->decode($input['social_accounts']);
        	if(isset($data))
        		$update->setRelatedData($this->id.'_rows', $data);
        }
	}

	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$new_rows = $update->getRelatedData($this->id.'_rows');
		if(isset($new_rows) && is_array($new_rows)) {
		    $existing = SocialAccount::getList($update->getModuleDir(), $update->getPrimaryKeyValue());
			$networks = SocialAccount::getSupportedNetworks();
			$seen = array();

			foreach ($new_rows as $details) {
				if(empty($details['type'])) continue;
				$type = $details['type'];
				if(! isset($networks[$type])) continue;
				$seen[$type] = true;
				$details['related_type'] = $update->getModuleDir();
				$details['related_id'] = $update->getPrimaryKeyValue();
				$account = new SocialAccount($details);
				$account->load();
				$account->save();
			}
			
			foreach($existing as $type => $details) {
				if(empty($seen[$type])) {
					$account = new SocialAccount($details);
					$account->load();
					$account->delete();
				}
			}
		}
	}
}
?>