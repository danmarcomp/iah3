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
//require_once('modules/Skills/Skill.php');

class iFrameWidget extends FormTableSection {
	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'iframe_site';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}
		return $this->renderHtmlView($gen, $row_result);
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
        $body = '';
        $record = $row_result->getField('id');
        if ($record) {
            $iframe = ListQuery::quick_fetch_row('iFrame', $record, array('url'));

            if ($iframe != null) {
                $url = add_http($iframe['url']);
                $body .= '<iframe frameborder="0"  marginwidth="0" marginheight="0" style="border: 1px solid #000000; background-color: #ffffff;" src="'.$url.'"  name="iahframe" id="iahframe" width="100%" height="800"></iframe>';
            }

        }

        return $body;
	}

	function loadUpdateRequest(RowUpdate &$update, array $input) {}
	
	function validateInput(RowUpdate &$update) { return true; }
	
	function afterUpdate(RowUpdate &$update) {}
}
?>