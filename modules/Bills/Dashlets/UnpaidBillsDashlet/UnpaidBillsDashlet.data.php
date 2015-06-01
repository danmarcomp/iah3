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




global $current_user;

$dashletData['UnpaidBillsDashlet']['searchFields'] =  array(
                                                            'date_entered'     => array('default' => ''),                                    
                                                            'bill_date'         => array('default' => ''),
                                                            'due_date'        => array('default' => ''),
                                                          	'cancelled'      => array('default' => ''),
                                                            'assigned_user_id' => array('type'    => 'assigned_user_name', 
                                                                                        'default' => $current_user->name),
                                                            );
                                                            
$dashletData['UnpaidBillsDashlet']['columns'] = array('full_number' => array('width'   => '15', 
                                                                       'label'   => 'LBL_LIST_NUMBER',
                                                                       'link'    => true,
																	   'related_fields' => array('prefix', 'bill_number'),
                                                                       'default' => true), 
                                                       'name' => array('width'   => '25',
                                                                       'label'   => 'LBL_LIST_SUBJECT',
                                                                       'link'    => true,
                                                                       'default' => true),
                                                       'supplier_name' => array('width' => '25',
                                                                         'label' => 'LBL_LIST_SUPPLIER_NAME',
																		 'id'      => 'SUPPLIER_ID',
																         'module'  => 'Accounts',
																         'default' => true,
																         'link' => true,
																         'icon' => array('name' => 'Accounts'),
																		 'related_fields' => array('supplier_id'),
																         'ACLTag' => 'SUPPLIER_ACCOUNT'),
														'amount_usdollar' => array('width'   => '15', 
																			'label'   => 'LBL_LIST_AMOUNT',
																			'default' => true,
																			'align' => 'right',
																			'related_fields' => array('amount', 'amount_usdollar', 'currency_id', 'exchange_rate'),
																			'currency_format' => true),
													   'amount_due_usdollar' => array(
																		  'width'   => '15', 
																		  'label'   => 'LBL_LIST_AMOUNT_DUE',
																		  'default' => false,
																		  'align' => 'right',
																		  'currency_format' => true,
																		  'related_fields' => array('amount_due', 'amount_due_usdollar', 'currency_id', 'exchange_rate',)),
													   'due_date' => array(
													        			  'width' => '10', 
																		  'label' => 'LBL_LIST_DUE_DATE',
																		  'default' => false),
													   'assigned_user_name' => array(
																		  'width' => '5', 
																		  'label' => 'LBL_LIST_ASSIGNED_USER',
													        			  'default' => false),
	);

?>