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


class CustomProductFormBase {
	
	function getFormBody($prefix, $mod='', $formname='') {
		if(!empty($mod)){
			global $current_language;
			$mod_strings = return_module_language($current_language, $mod);
		}
		else {
			global $mod_strings;
		}
		global $module;
		$show_list = ($module != 'Assemblies' && $module != 'SupportedAssemblies');
		global $app_strings;
		$lbl_required_symbol = $app_strings['LBL_REQUIRED_SYMBOL'];
		$lbl_name = $mod_strings['LBL_NAME'];
		$lbl_part_no = $mod_strings['LBL_PRODUCT_SUPPLIER_PART_NO'];
		$lbl_list_price = $mod_strings['LBL_PRODUCT_LIST_PRICE'];
		$form = <<<EOQ
		$lbl_name&nbsp;<span class="required">$lbl_required_symbol</span><br>
		<input name="_custom_name" type="text" value="" size="25" tabindex="101"><br>
		$lbl_part_no<br>
		<input name='_custom_manufacturers_part_no' type="text" value="" size="15" tabindex="101"><br>
EOQ;
		if($show_list) {
			$form .= <<<EOQ
		$lbl_list_price<br>
		<input name='_custom_raw_list_price' type="text" value="" size="8" onchange="validateNumberInput(this);" tabindex="101"><br>
EOQ;
		}
		$form .= <<<EOQ
		<input name='_custom_tax_code_id' type="hidden" value=""><br>
		<input name='_custom_currency_id' type="hidden" value=""><br>
		<script type="text/javascript">
		<!--
			var custom_line_fields = ['name', 'manufacturers_part_no', 'raw_list_price', 'tax_code_id', 'currency_id'];
			window.load_custom_line_item = function() {
				var form = document.forms.CustomSave;
				if(!form || ! window.request_data || !request_data.passthru_data)
					return;
				var custom = request_data.passthru_data.custom_line_item;
				if(custom) {
					for(var idx = 0; idx < custom_line_fields.length; idx++) {
						var f = custom_line_fields[idx];
						if(isset(custom[f]) && form.elements['_custom_' + f]) {
							form.elements['_custom_' + f].value = custom[f];
						}
					}
					document.getElementById('addform').style.display = 'block';
					document.getElementById('addformlink').style.display = 'none';
				}
				else {
					if(form._custom_raw_list_price)
						form._custom_raw_list_price.value = stdFormatNumber('0.00');
				}
				if(request_data.passthru_data.currency_id) {
					form._custom_currency_id.value = request_data.passthru_data.currency_id;
				}
				form.addformSave.tabIndex = '102';
			}
			window.send_back_custom = function() {
				var form = document.forms.CustomSave;
				var custom = {ID: ''};
				for(var idx = 0; idx < custom_line_fields.length; idx++) {
					var f = custom_line_fields[idx];
					if(form.elements['_custom_' + f]) {
						var newval = form.elements['_custom_' + f].value;
						if(f == 'raw_list_price') {
							newval = stdUnformatNumber(newval);
						}
						custom[f.toUpperCase()] = newval;
					}
				}
				associated_javascript_data['~custom'] = custom;
				send_back(form.module.value, '~custom');
				return false;
			}
		-->
		</script>
EOQ;
		return $form;
	}
	
}

?>
