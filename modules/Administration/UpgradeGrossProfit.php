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


action_restricted_for('demo');

global $current_user, $db;
if(! is_admin($current_user))
	die('Admin Only Section');

$query = "
UPDATE invoice inv SET inv.gross_profit =
( (SELECT IFNULL(SUM(ilg.subtotal),0) FROM invoice_line_groups ilg WHERE ilg.parent_id=inv.id AND NOT deleted)
- (SELECT IFNULL(SUM(ia.amount),0) FROM invoice_adjustments ia WHERE ia.invoice_id=inv.id AND ia.related_type='Discounts' AND NOT deleted)
- (SELECT IFNULL(SUM(il.cost_price*il.ext_quantity),0) FROM invoice_lines il WHERE il.invoice_id=inv.id AND NOT deleted) ),
inv.gross_profit_usdollar = ( (SELECT IFNULL(SUM(ilg.subtotal_usd),0) FROM invoice_line_groups ilg WHERE ilg.parent_id=inv.id AND NOT deleted)
- (SELECT IFNULL(SUM(ia.amount_usd),0) FROM invoice_adjustments ia WHERE ia.invoice_id=inv.id AND ia.related_type='Discounts' AND NOT deleted)
- (SELECT IFNULL(SUM(il.cost_price_usd*il.ext_quantity),0) FROM invoice_lines il WHERE il.invoice_id=inv.id AND NOT deleted) )
WHERE inv.gross_profit IS NULL
";
$db->query($query);


$query = "
UPDATE quotes inv SET inv.gross_profit =
( (SELECT IFNULL(SUM(ilg.subtotal),0) FROM quote_line_groups ilg WHERE ilg.parent_id=inv.id AND NOT deleted)
- (SELECT IFNULL(SUM(ia.amount),0) FROM quote_adjustments ia WHERE ia.quote_id=inv.id AND ia.related_type='Discounts' AND NOT deleted)
- (SELECT IFNULL(SUM(il.cost_price*il.ext_quantity),0) FROM quote_lines il WHERE il.quote_id=inv.id AND NOT deleted) ),
inv.gross_profit_usdollar = ( (SELECT IFNULL(SUM(ilg.subtotal_usd),0) FROM quote_line_groups ilg WHERE ilg.parent_id=inv.id AND NOT deleted)
- (SELECT IFNULL(SUM(ia.amount_usd),0) FROM quote_adjustments ia WHERE ia.quote_id=inv.id AND ia.related_type='Discounts' AND NOT deleted)
- (SELECT IFNULL(SUM(il.cost_price_usd*il.ext_quantity),0) FROM quote_lines il WHERE il.quote_id=inv.id AND NOT deleted) )
WHERE inv.gross_profit IS NULL
";
$db->query($query);


require_once('modules/Versions/Version.php');
unset($_SESSION['upgrade_gross_profit']);
Version::mark_upgraded('Invoice Gross Profit', '6.7.0', '6.7.0');
echo translate('LBL_UPGRADE_COMPLETE', 'Administration');

?>
