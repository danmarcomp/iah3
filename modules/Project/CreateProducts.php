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



global $current_user;

$create_contract = !empty($_POST['create_contract']) && $_POST['create_contract'] == 'create';
$select_contract = !empty($_POST['create_contract']) && $_POST['create_contract'] != 'create';

$use_contract = $create_contract || $select_contract;

if ($use_contract) {
	require_once 'modules/Service/Contract.php';
	require_once 'modules/SubContracts/SubContract.php';
	$contract = new Contract;
	$subcontract = new SubContract;
	if ($create_contract) {
		$query = "SELECT id FROM service_maincontracts WHERE account_id = '{$_POST['account_id']}' AND deleted = 0";
		$res = $contract->db->query($query, true);
		if ($row = $contract->db->fetchByAssoc($res)) {
			$contract->retrieve($row['id']);
		} else {
			$counts = get_contract_counts();
			$initial = substr($_POST['account_name'], 0, 1);
			if (isset($counts[$initial])) $number = $counts[$initial];
			else $number = 1;
			$contract->account_id = $_POST['account_id'];
			$contract->contract_no = $initial . sprintf('%04d', $number);
			$contract->status = 'Active';
			$contract->save();
		}
		$subcontract->main_contract_id = $contract->id;
		$subcontract->name = $_POST['new_contract_name'];
		if (!strlen($subcontract->name)) {
			$subcontract->name = $_POST['account_name'];
		}
		$subcontract->status = 'Active';
		$subcontract->contract_type_id = $_POST['contract_type'];
		$subcontract->save();
	} else {
		$subcontract->retrieve($_POST['service_subcontract_id']);
	}
	if (!empty($_POST['selected'])) {
		require_once 'modules/Assets/Asset.php';
		require_once 'modules/SupportedAssemblies/SupportedAssembly.php';
		foreach ($_POST['selected'] as $num => $dummy) {
			$is_assembly = $_POST['is_assembly'][$num];
			if ($is_assembly) {
				$seed = new SupportedAssembly;
			} else {
				$seed = new Asset;
			}
			if (!empty($_POST['id'][$num]) && $seed->retrieve($_POST['id'][$num])) {
				if ($is_assembly) {
					$seed->load_relationship('assets');
					$parts = $seed->assets->getBeans(new Asset);
					foreach ($parts as $part) {
						$part->service_subcontract_id = $subcontract->id;
						$part->save();
					}
				}
				$seed->service_subcontract_id = $subcontract->id;
				$seed->save();
			}

		}
	}
}

exit;

?>
<script type="text/javascript">
window.close();
</script>
