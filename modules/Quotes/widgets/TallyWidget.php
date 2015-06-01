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
require_once('include/Tally/TallyUpdate.php');

class TallyWidget extends FormTableSection {
	var $model_name;
	var $show_detail_extended = true;

	function init($params, $model=null) {
		parent::init($params, $model);
		if($model)
			$this->model_name = $model->name;
		if(! $this->id)
			$this->id = 'line_items';
		if($this->model_name == 'Shipping' || $this->model_name == 'Receiving')
			$this->show_detail_extended = false;
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
		return $this->renderHtmlView($gen, $row_result);
	}
	
	function getRequiredFields() {
		return array('currency_id', 'exchange_rate', 'shipping_provider_id', 'date_entered');
	}
	
	function getLabel() {
		$lbl = parent::getLabel();
		if(! $lbl) {
			$mod = $this->model->getModuleDir();
			$lbl = translate('LBL_LINE_ITEMS', $mod);
		}
		return $lbl;
	}
	
	function renderHtmlView(HtmlFormGenerator &$gen, RowResult &$row_result) {
		global $app_list_strings, $mod_strings, $locale, $current_user;
		$sep_lbl = translate('LBL_SEPARATOR', 'app');

		$all_shipping_taxed = -1;
		$targ = RowUpdate::for_result($row_result);

		$groups = $targ->getGroups();
		$grp_total = $targ->getTotals();
		$grp_pretax = 0.0;
		//
		$currency_id = $targ->getField('currency_id');
		$currency = AppConfig::db_object('Currency', $currency_id);
		$currency_name = $currency ? $currency->name : '';
		$show_space = $locale->getLocaleSpaceSeparateCurrency() ? '&nbsp;&nbsp;' : false;
		$format_params = array('currency_id' => $currency_id, 'symbol_space' => $show_space);
		$format_params_nosym = $format_params + array('currency_symbol' => false);
		$format_params_fixed = $format_params + array('round' => null);
		//
		$shipper_id = $targ->getField('shipping_provider_id');
		$shipping_provider = AppConfig::db_object('ShippingProvider', $shipper_id);
		$shipper = $shipping_provider ? $shipping_provider->name : '';
		unset($shipping_provider);
		$title = to_html($this->getLabel());
		//
		$form = <<<EOQ
		<table width="100%" border="0" style="margin-top: 0.5em;" cellspacing="0" cellpadding="0" class="tabDetailView">
		    <tr>
			<th align="left" class="tabDetailViewDL" colspan="10">
			       <h4 class="tabDetailViewDL">{$title}</h4>
			    </th>
		    </tr>
EOQ;

		foreach ($groups as $key => $grp)
		{
			$group_stage = '&nbsp;';
			if($this->model_name == 'Quote') {
				$stage_lbl = $mod_strings['LBL_GROUP_STAGE'].$sep_lbl;
				$group_stage = array_get_default($app_list_strings['quote_stage_dom'], array_get_default($grp, 'status'), '');
				if(strlen(''.$grp['name']))
					$group_stage = ' (<em>'.$stage_lbl.$group_stage.'</em>)';
				else
					$group_stage = "<b>$stage_lbl</b>$group_stage";
			}
			if(isset($grp['group_type'])) {
				$group_type = '<b>'.$mod_strings['LBL_GROUP_TYPE'].$sep_lbl.'</b>&nbsp;';
				$group_type .= $app_list_strings['quote_group_type_dom'][$grp['group_type']];
			}
			else {
				$group_type = '&nbsp;';
			}

			$pmethod = array_get_default($grp, 'pricing_method', '');
			$group_pricing = '<b>'.$mod_strings['LBL_PRICING_METHOD'].$sep_lbl.'</b>&nbsp;'.
				$app_list_strings['quote_pricing_method_dom'][$pmethod];
			if($pmethod == 'margin' || $pmethod == 'markup')
				$group_pricing .= '&nbsp;('.format_number($grp['pricing_percentage']).'%)';
			else if($pmethod == 'discount')
				$group_pricing .= '&nbsp;(-'.$grp['pricing_percentage'].'%)';
			$prc_span = $this->show_detail_extended ? 3 : 2;
			if ($this->model_name != 'Shipping' && $this->model_name != 'Receiving' && $this->model_name != 'CreditNote') {
			$form .= <<<EOQ
			<tr>
			       <td class="tabDetailViewDL" width="1%" valign="top" style="text-align: left;" colspan='6'>
			   <b>{$grp['name']}</b>$group_stage
			       </td>
			       <td class="tabDetailViewDL" width="45%" valign="top" style="text-align: left;">
			   $group_type
			       </td>
			       <td class="tabDetailViewDL" width="45%" valign="top" style="text-align: left;" colspan='{$prc_span}'>
			   $group_pricing
			       </td>
			   </tr> 
EOQ;
			}

			$end_sp = '&nbsp;&nbsp;&nbsp;&nbsp;';
			$show_cost = (@$grp['group_type'] != 'service');
			$show_cost = (bool)$current_user->getPreference('product_costs');
			$form .= '<tr>';

			if($this->model_name == 'PurchaseOrder' || $this->model_name == 'Bill' || $this->model_name == 'CreditNote'){ 
			$form .= '
			       <td class="tabDetailViewDL" width="1%" valign="top" style="text-align: center;" colspan="2" nowrap="nowrap"><b>#</b></td>
			       <td class="tabDetailViewDL" width="10%" valign="top" style="text-align: center;" nowrap="nowrap"><b>'.$mod_strings['LBL_QUANTITY'].'</b></td>
			       <td class="tabDetailViewDL" width="45%" valign="top" style="text-align: left;" colspan="3" nowrap="nowrap"><b>'.$mod_strings['LBL_PRODUCT'].'</b></td>
			       <td class="tabDetailViewDL" width="45%" valign="top" style="text-align: right;" colspan="3" nowrap="nowrap"><b>'.$mod_strings['LBL_UNIT_PRICE'].'</b></td>';
			}
			else{
				$form .= '
			       <td class="tabDetailViewDL" width="1%" valign="top" style="text-align: center;" colspan="2">#</td>
			       <td class="tabDetailViewDL" width="10%" valign="top" style="text-align: center;" nowrap="nowrap"><b>'.$mod_strings['LBL_QUANTITY'].'</b></td>
				   <td class="tabDetailViewDL" width="45%" valign="top" style="text-align: left;" colspan="3" nowrap="nowrap"><b>'.$mod_strings['LBL_PRODUCT'].'</b></td>';
				if ($this->model_name != 'Shipping' && $this->model_name != 'Receiving') {
					$cost_lbl = $show_cost ? $mod_strings['LBL_COST'] : '&nbsp;';
					$form .= '<td class="tabDetailViewDL" width="15%" valign="top" style="text-align: right;" nowrap="nowrap"><b>'.$cost_lbl.$end_sp.'</b></td>
			       <td class="tabDetailViewDL" width="15%" valign="top" style="text-align: right;" nowrap="nowrap"><b>'.$mod_strings['LBL_LIST_PRICE'].$end_sp.$end_sp.'</b></td>
				   <td class="tabDetailViewDL" width="15%" valign="top" style="text-align: right;" nowrap="nowrap"><b>'.$mod_strings['LBL_UNIT_PRICE'].'</b></td>';
				}
			}
			
			if($this->show_detail_extended) {
				   $form .= '<td class="tabDetailViewDL" width="15%" valign="top" style="text-align: right;" nowrap="nowrap"><b>'.$mod_strings['LBL_EXT_PRICE'].'</b></td>';
			}
			$form .= '</tr>';
	
		$taxes = array();
		$discounts = array();
		$row_shipping = $raw_shipping = '';
		$row_shipping_taxed = false;
		$attribs_by_line = array();
		
		if(! empty($grp['adjusts'])) {
			foreach($grp['adjusts'] as $idx => $adj) {
				if(! empty($adj['line_id'])) {
					if($adj['type'] == 'ProductAttributes')
						$attribs_by_line[$adj['line_id']][] = $adj;
					/*
					else if($adj['type'] == 'StandardTax' || $adj['type'] == 'CompoundedTax') {
						$adj['desc'] = $adj['name'];
						$adj['desc'] .= ' (' . format_number($adj['rate'], -1) . '%)';
						$adj['amount'] = currency_format_number($adj['amount'], $format_params_fixed);
						$taxes[] = $adj;
					}
					*/
					continue;
				}
				$adj['desc'] = $adj['name'];
				if($adj['type'] != 'StdFixedDiscount')
					$adj['desc'] .= ' (' . format_number($adj['rate'], -1) . '%)';
				$adj['raw_amount'] = $adj['amount'];
				if($adj['type'] == 'StandardDiscount' || $adj['type'] == 'StdPercentDiscount' || $adj['type'] == 'StdFixedDiscount')
					$adj['amount'] = -$adj['amount'];
				$adj['amount'] = currency_format_number($adj['amount'], $format_params_fixed);
				if($adj['type'] == 'StandardDiscount' || $adj['type'] == 'StdPercentDiscount' || $adj['type'] == 'StdFixedDiscount')
					$discounts[] = $adj;
				else if($adj['type'] == 'StandardTax' || $adj['type'] == 'CompoundedTax')
					$taxes[] = $adj;
				else if($adj['type'] == 'TaxedShipping' || $adj['type'] == 'UntaxedShipping') {
					$raw_shipping = $adj['raw_amount'];
					$row_shipping = $adj['amount'];
					$row_shipping_taxed = $adj['type'] == 'TaxedShipping';
				}
			}
		}

		if(! empty($grp['lines'])) {
			$top_index = 1;
			$next_line = array();
			$idx = '';
			foreach(array_keys($grp['lines']) as $nidx) {
				$next_line[$idx] = $nidx; $next_line[$nidx] = ''; $idx = $nidx;
			}
			foreach ($grp['lines'] as $idx => $line) {
				$treedots = '';
				$namespan = 'colspan="2"';
				if($line['depth']) {
					if(empty($line['is_comment']))
						$part_index ++;
					$show_top_index = '&nbsp;';
					global $image_path;
					$next = array_get_default($grp['lines'], $next_line[$idx]);
					$is_last = (! $next || $next['depth'] != $line['depth']);
					$bg = $image_path.($is_last ? 'treeDotsEnd.gif' : 'treeDotsBranch.gif');
					$treedots = 'style="background-image: url('.$bg.'); background-position: 4px 0px; background-repeat: no-repeat">&nbsp;</td><td class="tabDetailViewDF" width="1%" valign="top"';
					$namespan = '';
				}
				else {
					$show_top_index = $top_index;
					$part_index = '';
				}
				if(! empty($line['is_comment']))
					$line['related_type'] = 'Notes';
				$type = @$grp['group_type'] == 'expenses' ?  'Expenses' : $line['related_type'];
				$image = $this->get_icon_image($type);
				
				if(! empty($line['is_comment'])) {
					$text = nl2br($line['body']);
					$form .= <<<EOQ
					<tr>
						<td class="tabDetailViewDF" width='1' valign="top" colspan="3">
							&nbsp;
						</td>
						<td class="tabDetailViewDF" valign="top" width="1%" $treedots>
							$image
						</td>
						<td class="tabDetailViewDF" valign="top" $namespan>
							$text&nbsp;
						</td>
EOQ;
					if ($this->model_name != 'Shipping' && $this->model_name != 'Receiving') {
					$cspan = $this->show_detail_extended ? 4 : 3;
					$form .= <<<EOQ
						<td class="tabDetailViewDF" valign="top" colspan="{$cspan}">
							&nbsp;
						</td>
EOQ;
					}
					$form .= '</tr>';
				}
				else {
					if($this->model_name != 'PurchaseOrder' && $this->model_name != 'Bill' && $this->model_name != 'CreditNote') {
						$cost_price = currency_format_number($line['cost_price'], $format_params_fixed);
						$list_price = currency_format_number($line['list_price'], $format_params_fixed);
					}
					$unit_price = currency_format_number($line['unit_price'], $format_params_fixed);
					$ext_price = currency_format_number($line['ext_price'], $format_params_fixed);
					if((isset($grp['group_type']) && $grp['group_type'] == 'service') || !$show_cost)
						$cost_price = '&nbsp;';
					else if(isset($cost_price))
						$cost_price = "$cost_price";
					
					$name = $line['name'];
					if(! empty($line['related_id']))
						$name = '<a href="index.php?module='.$line['related_type'].'&action=DetailView&record='.$line['related_id']."\" class=\"tabDetailViewDFLink\">$name</a>";
					if($line['sum_of_components'])
						$name = "<b>$name</b>";
					$quantity = format_number($line['quantity'], -1);
					$part_index_text = $part_index ? $part_index : '&nbsp;';

					$assigned_person = "";
					if($line['related_type'] == 'Booking') {
						$book_bean = ListQuery::quick_fetch_row('BookedHours', $line['related_id'], array('id', 'assigned_user'));
						if($book_bean)
						$assigned_person = "[<a href=\"index.php?module=Users&action=DetailView&record=".$book_bean['assigned_user_id']."\" class=\"tabDetailViewDFLink\">".$book_bean['assigned_user']."</a>]";
					}
					
					$form .= <<<EOQ
					<tr>
						<td class="tabDetailViewDF" valign="top">
						   <b><em><small>$show_top_index</small></em></b>
						</td>
						<td class="tabDetailViewDF" valign="top">
						   <b><em><small>$part_index_text</small></em></b>
						</td>
					   <td class="tabDetailViewDF" valign="top" style="text-align: center;">
							$quantity
					   </td>
					   <td class="tabDetailViewDF" valign="top" width="1%" $treedots>
							$image
					   </td>
					   <td class="tabDetailViewDF" valign="top" style="text-align: left;" $namespan>
							$name $assigned_person
					   </td>
EOQ;
					if ($this->model_name == 'Shipping' || $this->model_name == 'Receiving') {
						;
					} elseif ($this->model_name != 'PurchaseOrder' && $this->model_name != 'Bill' && $this->model_name != 'CreditNote') {
						$form .= <<<EOQ
						<td class="tabDetailViewDF" valign="top" style="text-align: right; white-space:nowrap">
							{$cost_price}
						</td>
						<td class="tabDetailViewDF" valign="top" style="text-align: right; white-space:nowrap">
							{$list_price}$end_sp
						</td>
EOQ;
						$span = 1;
					}
					else
						$span = 3;

					if ($this->model_name != 'Shipping' && $this->model_name != 'Receiving') {
					$form .= <<<EOQ
						<td class="tabDetailViewDF" valign="top" style="text-align: right; white-space:nowrap" colspan="$span">
							{$unit_price}
						</td>
EOQ;
					}
					
					if($this->show_detail_extended) {
					$form .= <<<EOQ
						<td class="tabDetailViewDF" valign="top" style="text-align: right; white-space:nowrap" colspan="1">
							{$ext_price}
						</td>
EOQ;
					}
					
					$form .= <<<EOQ
					</tr>
EOQ;

					if($line['depth']) {
						$span1 = 3;
						$span2 = 2;
					}
					else {
						$span1 = $span2 = 3;
					}
					
					if(! $line['depth']) {
						$treedots = '';
					}
					else {
						if($is_last)
							$style = '';
						else
							$style = ' style="background-image: url('.$image_path.'treeDots.gif); background-position: 4px 0px; background-repeat: no-repeat" ';
						$treedots = '<td class="tabDetailViewDF" width="1%" '.$style.'>&nbsp;</td>';
					}
							
					if(! empty($line['event_session_id'])) {
						$event_icon = $this->get_icon_image('EventSessions');
						$form .= <<<EOQ
								<tr>
								<td class="tabDetailViewDF" valign="top" colspan="$span1">&nbsp;</td>
								$treedots
								<td class="tabDetailViewDF" width="1%">$event_icon</td>
								<td class="tabDetailViewDF" valign="top" colspan="$span2"><a href="index.php?module=EventSessions&action=DetailView&record={$line['event_session_id']}">{$line['event_session_name']}</a> ({$line['event_session_date']})</td>
								<td class="tabDetailViewDF" valign="top" colspan="3">&nbsp;</td>
								</tr>
EOQ;
					}
					if(! empty($attribs_by_line[$line['id']])) {
						$attribs_text = '';
						foreach($attribs_by_line[$line['id']] as $adj) {
							if($attribs_text) $attribs_text .= ', ';
							$attribs_text .= '<nobr>'.$adj['name'];
							if($adj['amount']) {
								$amt = currency_format_number($adj['amount'], $format_params_nosym);
								if(strlen($amt) && $amt{0} != '-')
									$amt = '+'.$amt;
								$attribs_text .= " ($amt)";
							}
							$attribs_text .= '</nobr>';
						}
						//$attrs_icon = $this->get_icon_image('ProductAttributes');
						$form .= <<<EOQ
								<tr>
								<td class="tabDetailViewDF" valign="top" colspan="$span1">&nbsp;</td>
								$treedots
								<td class="tabDetailViewDF">&nbsp;</td>
								<td class="tabDetailViewDF" valign="top" colspan="$span2"><small>{$attribs_text}</small></td>
								<td class="tabDetailViewDF" valign="top" colspan="3">&nbsp;</td>
								</tr>
EOQ;
					}

					if(! empty($line['pricing_adjust_id']) && !$line['sum_of_components']) {
						$lineadj = array_get_default($grp['adjusts'], $line['pricing_adjust_id']);
						if($lineadj) {
							$prc_methods =& $app_list_strings['quote_line_pricing_method_dom'];
							$prc_method = $lineadj['type'];
							$method2 = array_get_default($this->pricing_method_map, $prc_method, '');
							$prc_text = '';
							if($method2 == 'stddiscount') {
								if($lineadj['related_id'])
									$prc_text .= $lineadj['name'] . ' ';
							}
							else if($method2 && ! empty($prc_methods[$method2]))
								$prc_text .= $prc_methods[$method2] . ' ';
							if($lineadj['amount'] || $lineadj['rate']) {
								if($prc_method == 'StdFixedDiscount') {
									$amtt = currency_format_number($lineadj['amount'], $format_params);
									$prc_text .= '(-'.$amtt.')';
								}
								else if($method2 == 'margin' || $method2 == 'markup') {
									$prc_text .= '('.$lineadj['rate'].'%)';
								}
								else if($method2 && $method2 != 'list') {
									$prc_text .= '(-'.$lineadj['rate'].'%)';
								}
							}
							//$prc_name = $lineadj['type'];
							$discount_icon = $this->get_icon_image('Discounts');
							if($prc_text)
							$form .= <<<EOQ
								<tr>
								<td class="tabDetailViewDF" valign="top" colspan="$span1">&nbsp;</td>
								$treedots
								<td class="tabDetailViewDF" width="1%" valign="middle">$discount_icon</td>
								<td class="tabDetailViewDF" valign="top" colspan="$span2">$prc_text</td>
								<td class="tabDetailViewDF" valign="top" colspan="3">&nbsp;</td>
								</tr>
EOQ;
						}
					}
					
					if(! $line['depth'])
						$top_index ++;
				}
			}
		}

		if(! $row_shipping_taxed && $raw_shipping)
			$all_shipping_taxed = 0;
		else if($all_shipping_taxed < 0)
			$all_shipping_taxed = 1;

		$pretax = $grp['subtotal'] + ($row_shipping_taxed ? $raw_shipping : 0.0);
		foreach($discounts as $d)
			$pretax -= $d['raw_amount'];
		$grp_pretax += $pretax;
		
		if(count($groups) > 1 && $this->model_name != 'Shipping'  && $this->model_name != 'Receiving') {
			// only show group totals if more than one group exists
			foreach(array('subtotal', 'total') as $f) {
				$f2 = "row_$f";
				$$f2 = currency_format_number($grp[$f], $format_params);
			}
			$span = 7;
			$lspan = $this->show_detail_extended ? 2 : 1;
			$form .= <<<EOQ
			<tr>
			    <td class="tabDetailViewDF" colspan='10' NOWRAP>
				<hr style="margin: 0.3em 0.2em" />
			    </td>
			</tr>
			<tr>
			    <td class="tabDetailViewDF" NOWRAP colspan="{$span}">&nbsp;</td>
			    <td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$lspan">
                <b>{$mod_strings['LBL_SUBTOTAL']}</b>
			    </td>
			    <td class="tabDetailViewDF" NOWRAP style="text-align: right">
				{$row_subtotal}
			    </td>
			</tr>
EOQ;

			foreach($discounts as $d) {
				$form .= <<<EOQ
			<tr>
				    <td class="tabDetailViewDF" NOWRAP colspan="{$span}">&nbsp;</td>
				    <td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$lspan">
					{$d['desc']}
				    </td>
				    <td class="tabDetailViewDF" NOWRAP style="text-align: right">
					{$d['amount']}
			    </td>
			    </tr>
EOQ;
			}

			$shipping_str = '';
			if($row_shipping)
				$shipping_str = <<<EOQ
			    <tr>
				    <td class="tabDetailViewDF" NOWRAP colspan="{$span}">&nbsp;</td>
				    <td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$lspan">
					{$mod_strings['LBL_SHIPPING']}
				    </td>
				    <td class="tabDetailViewDF" NOWRAP style="text-align: right">
					{$row_shipping}
				    </td>
			    </tr>
EOQ;
			if($row_shipping_taxed)
				$form .= $shipping_str;

			if(count($taxes) && AppConfig::setting('company.show_pretax_totals')) {
				$pretax = currency_format_number($pretax, $format_params);
				$form .= <<<EOQ
				<tr>
						<td class="tabDetailViewDF" NOWRAP colspan="{$span}">&nbsp;</td>
						<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$lspan">
						<b>{$mod_strings['LBL_PRETAX_TOTAL']}</b>
						</td>
						<td class="tabDetailViewDF" NOWRAP style="text-align: right">
						{$pretax}
					</td>
					</tr>
EOQ;
			}

			foreach($taxes as $t) {
				$form .= <<<EOQ
			<tr>
				    <td class="tabDetailViewDF" NOWRAP colspan="{$span}">&nbsp;</td>
				    <td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$lspan">
					{$t['desc']}
				    </td>
				    <td class="tabDetailViewDF" NOWRAP style="text-align: right">
					{$t['amount']}
			    </td>
			    </tr>
EOQ;
			}
			
			if(! $row_shipping_taxed)
				$form .= $shipping_str;

			if ($this->model_name != 'Shipping' && $this->model_name != 'Receiving') {	
			$form .= <<<EOQ
			    <tr>
				<td class="tabDetailViewDF" colspan='{$span}' NOWRAP>&nbsp;</td>
				<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$lspan">
				     <b>{$mod_strings['LBL_TOTAL']}</b>
				</td>
				    <td class="tabDetailViewDF" NOWRAP style="text-align: right">
					{$row_total}
				    </td>
			</tr>
			<tr>
				<td class="tabDetailViewDF" colspan='10' NOWRAP><br></td>
			</tr>
EOQ;
			}
		}
	}
		
	if ($this->model_name != 'Shipping' && $this->model_name != 'Receiving') {	
		$form .= <<<EOQ
		<tr>
			<td class="tabDetailViewDL" colspan='10' valign="top" style="text-align: left;"><b>{$mod_strings['LBL_GRAND_TOTALS']}</b></td>
		</tr>
EOQ;

		foreach(array('subtotal', 'total', 'tax', 'discount', 'shipping') as $f) {
			$f2 = "grand_$f";
			$fv = $grp_total[$f];
			if($f == 'discount')
				$fv = -$fv;
			$$f2 = currency_format_number($fv, $format_params);
			if(($f == 'tax' || $f == 'discount') && empty($grp_total[$f]))
				 $$f2 = '';
		}
		$grp_pretax = currency_format_number($grp_pretax, $format_params);
		$span = $this->show_detail_extended ? 2 : 1;
		
		$form .= <<<EOQ
		<tr>
			<td class="tabDetailViewDF" NOWRAP colspan="5">&nbsp;</td>
			<td class="tabDetailViewDF" style="text-align: right;"><b>{$mod_strings['LBL_CURRENCY']}</b></td>
			<td class="tabDetailViewDF">$currency_name</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span"><b>{$mod_strings['LBL_SUBTOTAL']}</b></td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">$grand_subtotal</td>
		</tr>
EOQ;

		$grand_shipping_str = '';
		if($shipper_id || $grp_total['shipping'])
			$grand_shipping_str = <<<EOQ
		<tr>
			<td class="tabDetailViewDF" NOWRAP colspan="5">&nbsp;</td>
			<td class="tabDetailViewDF" style="text-align: right;"><b>{$mod_strings['LBL_SHIPPING_PROVIDER']}</b></td>
			<td class="tabDetailViewDF">$shipper</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span">{$mod_strings['LBL_SHIPPING']}</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">$grand_shipping</td>
		</tr>
EOQ;

		$form_pretax = '';
		if(AppConfig::setting('company.show_pretax_totals')) {
			$form_pretax .= <<<EOQ
		<tr>
			<td class="tabDetailViewDF" NOWRAP colspan="5">&nbsp;</td>
			<td class="tabDetailViewDF" style="text-align: right;">&nbsp;</td>
			<td class="tabDetailViewDF">&nbsp;</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span"><b>{$mod_strings['LBL_PRETAX_TOTAL']}</b></td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">$grp_pretax</td>
		</tr>
EOQ;
		}

		if(count($groups) > 1) {
			if($grand_discount)
				$form .= <<<EOQ
		<tr>
			<td class="tabDetailViewDF" NOWRAP colspan="5">&nbsp;</td>
			<td class="tabDetailViewDF" style="text-align: right;">&nbsp;</td>
			<td class="tabDetailViewDF">&nbsp;</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span">{$mod_strings['LBL_DISCOUNT']}</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">$grand_discount</td>
		</tr>
EOQ;
			if($all_shipping_taxed == 1)
				$form .= $grand_shipping_str;
			if($grand_tax)
				$form .= $form_pretax;
			if($grand_tax)
				$form .= <<<EOQ
		<tr>
			<td class="tabDetailViewDF" NOWRAP colspan="5">&nbsp;</td>
			<td class="tabDetailViewDF" style="text-align: right;">&nbsp;</td>
			<td class="tabDetailViewDF">&nbsp;</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span">{$mod_strings['LBL_TAX']}</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">$grand_tax</td>
		</tr>
EOQ;
		} else {
			if(!empty($discounts)) {
				foreach($discounts as $d) {
					$form .= <<<EOQ
		<tr>
			<td class="tabDetailViewDF" NOWRAP colspan="5">&nbsp;</td>
			<td class="tabDetailViewDF" style="text-align: right;">&nbsp;</td>
			<td class="tabDetailViewDF">&nbsp;</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span">{$d['desc']}</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">{$d['amount']}</td>
		</tr>
EOQ;
				}
			}
			if($all_shipping_taxed == 1)
				$form .= $grand_shipping_str;
			if(!empty($taxes)) {
				$form .= $form_pretax;
				foreach($taxes as $t) {
					$tax_amount = currency_format_number($t['amount'], $format_params);
					$form .= <<<EOQ
		<tr>
			<td class="tabDetailViewDF" NOWRAP colspan="5">&nbsp;</td>
			<td class="tabDetailViewDF" style="text-align: right;">&nbsp;</td>
			<td class="tabDetailViewDF">&nbsp;</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span">{$t['desc']}</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">{$t['amount']}</td>
		</tr>
EOQ;
				}
			}
		}
		
		if($all_shipping_taxed != 1)
			$form .= $grand_shipping_str;
		
		$form .= <<<EOQ
		<tr>
			<td class="tabDetailViewDF" colspan='7' NOWRAP>&nbsp;</td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right" colspan="$span"><b>{$mod_strings['LBL_TOTAL']}</b></td>
			<td class="tabDetailViewDF" NOWRAP style="text-align: right">$grand_total</td>
		</tr>
EOQ;

		}
		$form .= "</table>";		
		return $form;
	}
	
	
	function &get_transfer_fields() {
		// ajrw - this will go away in favour of better app list string representation in javascript
		$transfer = array();
		$transfer['app_list'] = array(
			'quote_pricing_method_dom' => 'pricing_method_dom',
			'quote_line_pricing_method_dom' => 'line_pricing_method_dom',
			'quote_stage_dom' => 'stage_dom',
			'quote_group_type_dom' => 'group_type_dom',
		);
		return $transfer;
	}
	
	
	function renderHtmlEdit(HtmlFormGenerator &$gen, RowResult &$row_result) {
		global $app_strings, $app_list_strings, $mod_strings, $image_path, $current_user;

		$can_edit = true;
		$editable = $row_result->new_record;
		$layout =& $gen->getLayout();
		$json = getJSONobj();
		
		$transfer = $this->get_transfer_fields();
		$strings = array();
		foreach($transfer['app_list'] as $d => $f) {
			$l = $app_list_strings[$d];
			$strings[$f] = array('order' => array_keys($l), 'values' => $l);
		}
		$add_language = 'LANGUAGE = {app_list_strings: '.$json->encode($strings)."};\n";
		
		$dupe_id = $row_result->new_record ? $row_result->getField('id') : null;
        $line_items = array_get_default($row_result->row, 'line_items', array());
		$targ = RowUpdate::for_result($row_result, $dupe_id);
        if (! empty($line_items))
            $targ->set('line_items', $line_items);
		$groups = $targ->getGroups();
		$payments = $targ->fetchPayments();
		if($payments)
			$can_edit = $editable = false;

		// FIXME - populate attributes in TallyUpdate
		/*if(count($groups)) {
			$c = AppConfig::db_object('Currency', $focus->currency_id);
			if($c) {
				if($focus->exchange_rate)
					$c->conversion_rate = unformat_number($focus->exchange_rate);
				$enc->populate_attribute_options($groups, $c);
				$c->cleanup();
			}
		}*/

		foreach ($groups as $gid => $group) {
			foreach ($group['adjusts'] as $adj_id => $adj) {
				if (! empty($adj['line_id'])) {
					foreach ($group['lines'] as $lid => $line) {
						if ($line['id'] == $adj['line_id']) {
							$groups[$gid]['lines'][$lid]['adjusts'][$adj['id']] = $adj;
							break;
						}
					}
					unset($groups[$gid]['adjusts'][$adj_id]);
				}
			}
			$groups[$gid]['adjusts_order'] = array_keys($groups[$gid]['adjusts']);
			$groups[$gid]['lines_order'] = array_keys($groups[$gid]['lines']);
		}
		
		$line_items_json = $json->encode(array('data' => $groups, 'order' => array_keys($groups)));

		$images = array(
			'ProductCatalog' => array('title' => $mod_strings['LBL_PRODUCT']),
			'Assemblies' => array('title' => $mod_strings['LBL_ASSEMBLY']),
			'BookingCategories' => array('title' => $mod_strings['LBL_BOOKING_CATEGORY']),
			'SupportedAssemblies' => array('title' => $mod_strings['LBL_SUPPORTED_ASSEMBLY']),
			'Assets' => array('title' => $mod_strings['LBL_ASSET']),
			'Booking' => array('title' => $app_strings['LBL_BOOKED_HOURS']),
			'Notes' => array('title' => $mod_strings['LBL_COMMENT']),
			'Expenses' => array('name' => 'ExpenseReports', 'title' => $mod_strings['LBL_EXPENSE']),
			'up' => array('name' => 'uparrow_big', 'title' => $app_strings['LNK_UP']),
			'down' => array('name' => 'downarrow_big', 'title' => $app_strings['LNK_DOWN']),
			'insert' => array('name' => 'plus_inline', 'title' => $app_strings['LNK_INS']),
			'remove' => array('name' => 'delete_inline', 'title' => $app_strings['LNK_REMOVE']),
			'tree_dots' => array('name' => 'treeDots'),
			'tree_branch' => array('name' => 'treeDotsBranch'),
			'tree_end' => array('name' => 'treeDotsEnd'),
		);
		foreach($images as $idx => $img) {
			$src = isset($img['name']) ? $img['name'] : $idx;
			unset($img['name']);
			$images[$idx] = array_merge(get_image_info($src), $img);
		}
		$image_meta_json = $json->encode($images);
		
		$layout->addScriptLiteral('
			SysData = {
				taxrates: {},
				taxrates_order: [],
				taxcodes: {},
				taxcodes_order: [],
				discounts: {},
				discounts_order: [],
				get_tax_rates: function(clsid) {
					var ret = [];
					if(isset(this.taxcodes[clsid]))
						ret = this.taxcodes[clsid].rates;
					return ret;
				}
			};', LOAD_PRIORITY_BODY);
		
		require_once('modules/TaxCodes/TaxCode.php');
		$tax = new TaxCode();
		$layout->addScriptLiteral($tax->get_javascript(), LOAD_PRIORITY_BODY);
        require_once('modules/TaxRates/TaxRate.php');
		$rate = new TaxRate();
		$layout->addScriptLiteral($rate->get_javascript(), LOAD_PRIORITY_BODY);

		require_once('modules/Discounts/ListDiscounts.php');
		$disc = new ListDiscounts();
		$disc->lookupDiscounts('Active', '');
		$layout->addScriptLiteral($disc->getJavascript(), LOAD_PRIORITY_BODY);
		
		$add_comments = AppConfig::setting('company.quote_add_comments');
		$add_booked_hours_comments = AppConfig::setting('company.add_booked_hours', 0) ? 'true' : 'false';
		$add_product_description = ($add_comments == 'products' || $add_comments == 'all') ? 'true' : 'false';
		$add_assembly_description = ($add_comments == 'assemblies' || $add_comments == 'all') ? 'true' : 'false';
		$add_related_events = AppConfig::setting('company.invoice_line_events', 0) ? 'true' : 'false';
		$add_serials = AppConfig::setting('company.invoice_line_serials', 0) ? 'true' : 'false';
		$show_pretax = AppConfig::setting('company.show_pretax_totals', 0) ? 'true' : 'false';
		$tax_shipping = AppConfig::setting('company.tax_shipping', 0) ? 'true' : 'false';
		$shipping_code = AppConfig::setting('company.shipping_tax_code', 'all');
		
		//if(! $editable)
		//	$xtpl->assign('CURRENCY_DISABLED', 'disabled="disabled"');

		$default_discount = $targ->getField('default_discount_id');
		if($default_discount == '-99')
			$default_discount = '';
		$default_tax_code = $targ->getField('default_tax_code_id');
		if(! $default_tax_code)
			$default_tax_code = '';
		
		$add_initial = $row_result->new_record ? 'true' : 'false';
		$init_popup = "''";
		if(array_get_default($_REQUEST, 'return_module') == 'Cases' && ($case_id = array_get_default($_REQUEST, 'return_record'))) {
			if(! AppConfig::setting('site.feature.auto_add_case_line_items')) {
				$add_initial = 'false';
				$case_id = javascript_escape($case_id);
				$init_popup = "{module: 'Cases', action: 'InvoicePopup', filter: 'record=$case_id', inline: true, width: 700, height: 500}";
			}
		}
		$tparams = array(
			'add_product_description' => $add_product_description,
			'add_assembly_description' => $add_assembly_description,
			'canAddRelatedEvents' => $add_related_events,
			'add_serials' => $add_serials,
			'add_booked_hours_comments' => $add_booked_hours_comments,
			'default_discount_id' => "'$default_discount'",
			'default_tax_code_id' => "'$default_tax_code'",
			'default_tax_shipping' => $tax_shipping,
			'default_shipping_tax_code' => "'$shipping_code'",
			'show_pretax_totals' => $show_pretax,
			'add_initial_group' => $add_initial,
			'init_popup_options' => $init_popup,
			'label' => '"' . javascript_escape($this->getLabel()) . '"',
		);
		if($current_user->getPreference('noncatalog_products')) {
			$tparams['catalogOnly'] = 'false';
			$tparams['fixedPrices'] = 'false';
		}
		else if($current_user->getPreference('nonstandard_prices')) {
			$tparams['catalogOnly'] = 'true';
			$tparams['fixedPrices'] = 'false';
		}
		if($current_user->getPreference('manual_discounts'))
			$tparams['manualDiscounts'] = 'true';
		if($current_user->getPreference('standard_discounts'))
			$tparams['standardDiscounts'] = 'true';
		if($current_user->getPreference('product_costs'))
			$tparams['productCosts'] = 'true';
		$tparam_str = array();
		foreach($tparams as $k=>$v)
			$tparam_str[] = "TallyEditor.$k = $v;";
		$tparam_str = implode("\n\t\t\t", $tparam_str);

		$form_name = $gen->getFormName();
		$module = $targ->getModuleDir();
		$editable_js = $editable ? 'true' : 'false';
		$date_entered = javascript_escape($targ->getField('date_entered'));
		$editor_setup_js = <<<EOS
			$add_language;
			SavedLineItems = $line_items_json;
			ImageMeta = $image_meta_json;
			{$tparam_str}
			TallyEditor.init('{$module}', SavedLineItems, $editable_js);
			SUGAR.ui.registerInput('$form_name', TallyEditor);
			var date_entered = '$date_entered';
EOS;
		$layout->addScriptInclude('modules/Quotes/quotes.js');
		$layout->addScriptLiteral($editor_setup_js);

		return $this->renderEditorBody($can_edit && ! $row_result->new_record);
	}
	
	
	function renderEditorBody($show_edit_button=true) {
		$module = 'Quotes';
		$title = $this->getLabel();
		$lbl_currency = translate('LBL_CURRENCY', $module);
		$lbl_shipper = translate('LBL_SHIPPING_PROVIDER', $module);
		$lbl_gross_profit = translate('LBL_GROSS_PROFIT', $module);
		$lbl_tax_info = translate('LBL_TAX_INFORMATION', $module);
		$lbl_tax_exempt = translate('LBL_TAX_EXEMPT', $module);
		$lbl_disc_before_tax = translate('LBL_DISCOUNT_BEFORE_TAXES', $module);
		
		// FIXME - pre-tax total should be optional
		$lbl_grand_totals = translate('LBL_GRAND_TOTALS', $module);
		$lbl_subtotal = translate('LBL_SUBTOTAL', $module);
		$lbl_discount = translate('LBL_DISCOUNT', $module);
		$lbl_pretax_total = translate('LBL_PRETAX_TOTAL', $module);
		$lbl_tax = translate('LBL_TAX', $module);
		$lbl_shipping = translate('LBL_SHIPPING', $module);
		$lbl_total = translate('LBL_TOTAL', $module);
		
		if($show_edit_button) {
			$btn_label = translate('LBL_EDIT_BUTTON_LABEL');
			$edit_button = ' <button type="button" class="input-button input-outer" title="" accesskey="" onclick="TallyEditor.make_editable(); this.style.display=\'none\';" style="margin-left: 2em; margin-top: -4px">'
				. '<div class="input-icon icon-edit left"></div><span class="input-label" style="text-transform:none; letter-spacing:normal">' . $btn_label . '</span></button>';
		} else {
			$edit_button = '';
		}
	$body = <<<EOF
<table class="tabForm" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 0.5em">
<tr><th align="left" class="dataLabel">
	<h4 class="dataLabel">{$title}{$edit_button}&nbsp;<span id="tax_exempt_warning" class="required"></span></h4>
</th></tr>
<tr><td class="dataLabel">
	<div id='tally_groups'></div>
	<div id='tally_grand_totals' style='display:none'>
		<h4 class="dataLabel">$lbl_grand_totals</h4>
		<table  border="0" cellspacing="0" cellpadding="0">
		<tr>
			<td class="dataLabel" nowrap style="text-align: left;"><b>$lbl_subtotal</b></td>
			<td class="dataLabel" align="right" nowrap><input id='grand_subtotal' style="background: transparent; border: none; text-align: right" size="13" readonly="readonly" value=""></td>
		</tr><tr>
			<td class="dataLabel" nowrap style="text-align: left;">$lbl_discount</td>
			<td class="dataLabel" align="right" nowrap><input id='grand_discounts' style="background: transparent; border: none; text-align: right" size="13" readonly="readonly" value=""></td>
		</tr>
			<td class="dataLabel" nowrap style="text-align: left;"><b>$lbl_pretax_total</b></td>
			<td class="dataLabel" align="right" nowrap><input id='grand_pretax' style="background: transparent; border: none; text-align: right" size="13" readonly="readonly" value=""></td>
		</tr>
			<td class="dataLabel" nowrap style="text-align: left;">$lbl_tax</td>
			<td class="dataLabel" align="right" nowrap><input id='grand_taxes' style="background: transparent; border: none; text-align: right" size="13" readonly="readonly" value=""></td>
		</tr><tr>
			<td class="dataLabel" nowrap style="text-align: left;">$lbl_shipping</td>
			<td class="dataLabel" align="right" nowrap><input id='grand_shipping' style="background: transparent; border: none; text-align: right" size="13" readonly="readonly" value=""></td>
		</tr><tr>
			<td class="dataLabel" nowrap style="text-align: left;"><b>$lbl_total</b></td>
			<td class="dataLabel" align="right" nowrap><input id='grand_total' style="background: transparent; border: none; text-align: right" size="13" readonly="readonly" value=""></td>
		</tr>
		</table>
	</div>
	<div id='tally_footer'>
		&nbsp;
	</div>
</td>
</tr></table>
</p>
EOF;
		return $body;
	}
	
	
	function get_icon_image($id) {
		global $app_strings, $mod_strings;
		static $images = array();
		if(isset($images[$id]))
			return $images[$id];
		switch($id) {
			case 'Expenses':
				$img = 'ExpenseReports'; $caption = $mod_strings['LBL_EXPENSE']; break;
			case 'Discount': case 'Discounts':
				$img = 'Discounts'; $caption = $mod_strings['LBL_DISCOUNT']; break;
			case 'TaxRate': case 'TaxRates':
				$img = 'TaxRates'; $caption = $mod_strings['LBL_TAX_RATE']; break;
			case 'Product': case 'ProductCatalog':
				$img = 'ProductCatalog'; $caption = $mod_strings['LBL_PRODUCT']; break;
			case 'Assembly': case 'Assemblies':
				$img = 'Assemblies'; $caption = $mod_strings['LBL_ASSEMBLY']; break;
			case 'Asset': case 'Assets':
				$img = 'Assets'; $caption = $mod_strings['LBL_ASSET']; break;
			case 'SupportedAssembly': case 'SupportedAssemblies':
				$img = 'SupportedAssemblies'; $caption = $mod_strings['LBL_SUPPORTED_ASSEMBLY']; break;
			case 'BookingCategories': case 'BookingCategory':
				$img = 'BookingCategories'; $caption = $app_strings['LBL_BOOKING_CATEGORY']; break;
			case 'Booking': case 'BookedHours':
				$img = 'Booking'; $caption = $app_strings['LBL_BOOKED_HOURS']; break;
			case 'EventSessions':
				$img = 'EventSessions'; $caption = $mod_strings['LBL_EVENT']; break;
			case 'Comment': case 'Notes':
				$img = 'Notes'; $caption = $mod_strings['LBL_COMMENT']; break;
			case 'up':
				$img = 'uparrow_big'; $caption = $app_strings['LNK_UP']; break;
			case 'down':
				$img = 'downarrow_big'; $caption = $app_strings['LNK_DOWN']; break;
			case 'insert':
				$img = 'plus_inline'; $caption = $app_strings['LNK_INS']; break;
			case 'remove':
				$img = 'delete_inline'; $caption = $app_strings['LNK_REMOVE']; break;
			default:
				return '';
		}
		$caption = to_html($caption);
		$images[$id] = get_image($img, 'align="absmiddle" alt="'.$caption.'" title="'.$caption.'" border="0"');
		return $images[$id];
	}
	
	
	function validateInput(RowUpdate &$update) {
		// need more flexible line management for this
		return true;
	}
	
	function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context)
	{
		$gen->pdf->set_focus($row_result);
		$gen->pdf->print_main();
	}
}

?>
