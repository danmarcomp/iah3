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
require_once ('modules/Notes/Note.php');
require_once ('include/upload_file.php');
require_once ('modules/ExpenseReports/ExpenseReport.php');

class ExpenseItemsWidget extends FormTableSection {
    
	function init($params, $model=null) {
		parent::init($params, $model);
		if(! $this->id)
			$this->id = 'expense_items';
	}
	
	function renderHtml(HtmlFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		if($gen->getLayout()->getEditingLayout()) {
			$params = array();
			return $this->renderOuterTable($gen, $parents, $context, $params);		
		}

		$lstyle = $gen->getLayout()->getType();
        $layout =& $gen->getLayout();

        if($lstyle == 'editview') {
            //$report->currency_id = $row_result->getField('currency_id');
            $this->addJs($layout, $row_result, $gen->getFormName(), true);
			return $this->renderHtmlEdit($row_result);
		} else {
            $this->addJs($layout, $row_result, $gen->getFormName());
            return $this->renderHtmlView();
        }
	}
	
	function getRequiredFields() {
		return array();
	}
	
	function renderHtmlView() {
		$title = to_html($this->getLabel());
        
        $body = <<<EOQ
			<table width="100%" border="0" style="margin-top: 0.5em;" cellspacing="0" cellpadding="0" class="tabDetailView">
				<tr>
				<th align="left" class="tabDetailViewDL">
					   <h4 class="tabDetailViewDL">{$title}</h4>
				</th>
				</tr>
				<tr><td class="tabDetailViewDF">
					<div id='expense_groups'>&nbsp;</div>
				</td></tr>
			</table>
EOQ;

        return $body;
	}

	function renderHtmlEdit(RowResult &$row_result) {
        global $mod_strings;

        $tax = $row_result->getField('tax');
		$title = to_html($this->getLabel());

        $body = <<<EOQ
			<table class="tabForm" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-top: 0.5em">
			<tr><th align="left" class="dataLabel">
				<h4 class="dataLabel">{$title}</h4>
			</th></tr>
			<tr><td class="dataLabel">
				<input type="hidden" name="tax" id="tax" value="{$tax}" />    
				<input name="line_items" type="hidden" value="" />
				<div id='expense_groups'>&nbsp;</div>
				<div id='expense_footer'>&nbsp;</div>
				<button type="button" class="input-button input-outer" onclick="ExpenseEditor.addItem();ExpenseEditor.paint();"><div class="input-icon icon-add left"></div><span class="input-label">{$mod_strings['LBL_ADD_RECEIPT']}</span></button>
            </td></tr>
            </table>
EOQ;

        return $body;
	}

    /**
     * Add expense editor javascript to page
     *
     * @param FormLayout $layout
     * @param RowResult $report
     * @param string $form_name
     * @param bool $edit
     * @return void
     */
    function addJs(FormLayout &$layout, $report, $form_name, $edit = false) {
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
            };', LOAD_PRIORITY_FOOT);

        require_once 'modules/TaxCodes/TaxCode.php';
        $taxCode = new TaxCode();
        $layout->addScriptLiteral($taxCode->get_javascript(), LOAD_PRIORITY_FOOT);

        $images_meta = $this->getImagesMeta();

        $editorSetupJs = <<<EOS
            ExpenseEditor.init();
            ImageMeta = $images_meta;
EOS;

        if ($edit) {
            simpledialog_javascript_header();
        } else {
        	$layout->addJSModelCache('Currency');
        	$cid = $report->getField('currency_id');
        	$acid = $report->getField('advance_currency_id');
        	$editorSetupJs .= "ExpenseEditor.currency_id = '$cid';";
        	$editorSetupJs .= "ExpenseEditor.advance_currency_id = '$acid';";
			$editorSetupJs .= "ExpenseEditor.readOnly = true;";
        }

        $editorSetupJs .= $this->getItemsJs($report);
   		$editorSetupJs .= "SUGAR.ui.registerInput('$form_name', ExpenseEditor);";
		$layout->addScriptInclude('modules/ExpenseReports/expense_reports.js');
        $layout->addScriptLiteral($editorSetupJs, LOAD_PRIORITY_FOOT);
    }

    /**
     * Get images metadata
     *
     * @return string
     */
    function getImagesMeta() {
        global $app_strings, $mod_strings;

        $images = array(
            'up' => array('name' => 'uparrow_big', 'title' => $app_strings['LNK_UP']),
            'down' => array('name' => 'downarrow_big', 'title' => $app_strings['LNK_DOWN']),
            'insert' => array('name' => 'plus_inline', 'title' => $app_strings['LNK_INS']),
            'remove' => array('name' => 'delete_inline', 'title' => $app_strings['LNK_REMOVE']),
            'expand' => array('name' => 'plus_inline', 'title' => $mod_strings['LBL_EXPAND']),
            'plus' => array('name' => 'plus_inline'),
            'collapse' => array('name' => 'minus_inline', 'title' => $mod_strings['LBL_COLLAPSE']),
            'split' => array('name' => 'split', 'title' => $mod_strings['LBL_SPLIT']),
            'tree_dots' => array('name' => 'treeDots'),
            'tree_branch' => array('name' => 'treeDotsBranch'),
            'tree_end' => array('name' => 'treeDotsEnd'),
            'calendar' => array('name' => 'jscalendar'),
            'blank' => array('name' => 'blank', 'width' => 12, 'height' => 12),
            'Notes' => array('title' => $mod_strings['LBL_ATTACHMENT']),
            'pdf' => array('name' => 'pdf_image_inline',),
        );

        foreach($images as $idx => $img) {
            $src = isset($img['name']) ? $img['name'] : $idx;
            unset($img['name']);
            $images[$idx] = array_merge(get_image_info($src), $img);
        }

        $json = getJSONObj();
        $images_meta = $json->encode($images);

        return $images_meta;
    }

    /**
     * Init ExpenseItems js
     *
     * @param RowResult $report
     * @return string
     */
    function getItemsJs($report) {
        global $timedate;

        $itemsArray = ExpenseReport::get_line_items($report->getField('id'));
        $defaultImageName = "def_image_inline";

        $categories = $this->getCategories();
        $cat_data = array('order' => array_keys($categories), 'values' => $categories);
        $json = getJSONObj();
        $reportItemsJS = 'ExpenseEditor.Categories = ' . $json->encode($cat_data) . ';';

        foreach ($itemsArray as $reportItem) {
            $reportItem['date'] = $timedate->to_display_date($reportItem['date'], false);
            if ($reportItem['note_id']) {
                $note = ListQuery::quick_fetch_row('Note', $reportItem['note_id']);

                if ($note) {
                    $reportItem['attachmentSrc'] = UploadFile::get_url($note['filename'], $note['id']);
                    $reportItem['attachmentName'] = basename($reportItem['attachmentSrc']);

                    $imageName = $defaultImageName;
                    $m = array();
                    if (preg_match('/\.([^.]+)$/', $note['filename'], $m)) {
                        $imageName = $m[1] . "_image_inline";
                    }
                    $imageInfo = get_image_info($imageName);
                    if (!$imageInfo['found']) {
                        $imageInfo = get_image_info($defaultImageName);
                    }
                    $reportItem['iconSrc'] = $imageInfo['src'];
                }
            }
            $reportItemsJS .= "ExpenseEditor.addItem('{$reportItem['parent_id']}', " . $json->encode($reportItem) . ", true);\n";
        }
        unset($note);

        return $reportItemsJS;
    }

    /**
     * Get expenses booking categories 
     *
     * @return array
     */
    function getCategories() {
        $lq = new ListQuery('BookingCategory', array('name', 'expenses_unit', 'paid_rate_usd'));
        $lq->addPrimaryKey();
        $lq->addAclFilter('list');
        $lq->addFilterClause(array('field' => 'booking_class', 'value' => 'expenses'));
        $result = $lq->fetchAll();

        $categories = array();

        if (! $result->failed)
            $categories = $result->rows;

        return $categories;
    }

	function loadUpdateRequest(RowUpdate &$update, array $input) {
        $upd_items = array();

        if (!empty($input['line_items'])) {
            $json = getJSONObj();
			$upd_items = $json->decode(from_html($input['line_items']));
        }

        $update->setRelatedData($this->id.'_rows', $upd_items);
	}
	
	function validateInput(RowUpdate &$update) {
		return true;
	}
	
	function afterUpdate(RowUpdate &$update) {
		$row_updates = $update->getRelatedData($this->id.'_rows');
        $report_id = $update->getPrimaryKeyValue();

        if ($report_id != null) {
            global $current_user;
            $keep = array();
            $id_map = array();

            $update_fields = array (
                'amount', 'quantity', 'category', 'split', 'line_order', 'parent_id',
                'description', 'date', 'amount_usdollar', 'paid_rate', 'paid_rate_usd',
                'unit', 'tax', 'tax__usdollar', 'total', 'total_usdollar', 'tax_class_id',
                'report_id', 'note_id', 'item_number'
            );

            $model = new ModelDef('ExpenseItem');
            $item_number = 0;

            foreach ($row_updates as $line_item) {
                $note = null;
                $uploadName = 'upload_' . $line_item['id'];
                $upload_file = new UploadFile($uploadName);

				if (!empty($line_item['hasNewAttachment']) && $upload_file->confirm_upload(true)) {
                    $note = new Note;
                    $note->id = create_guid();
                    $note->new_with_id = true;
                    $note->filename = $upload_file->get_stored_file_name();
                    $note->file_mime_type = $upload_file->mime_type;
                    $upload_file->final_move($note->id);
                    $note->name = $line_item['description'];
                    if (empty($note->name)) {
                        $note->name = basename($note->filename);
                    }
                    $note->assigned_user_id = $current_user->id;
                    $note->parent_type = 'ExpenseReports';
                    $note->parent_id = $report_id;
                    $note->save();
				}

                $upd = RowUpdate::blank_for_model($model);
                $upd->limitFields($update_fields);
                $result = null;

                if (preg_match('/~newitem~/', $line_item['id'])) {
                    $upd = RowUpdate::blank_for_model($model);
                } else {
                    $result = ListQuery::quick_fetch($model, $line_item['id'], $update_fields);
                    if(! $result) continue;
                    $upd = RowUpdate::for_result($result);
                }

                $line_item['item_number'] = $item_number++;
                $line_item['report_id'] = $report_id;
				
				if (!empty($line_item['removeOriginalAttachment'])) {
					$nr = ListQuery::quick_fetch('Note', $line_item['note_id']);
					if ($nr) {
						$nu = RowUpdate::for_result($nr);
						$nu->markDeleted();
						unset($result->row['note_id']);
						$upd->set('note_id', null);
					}
				}

                if ($note ) {
                    if (isset($result->row['note_id']))
                        $note->mark_deleted($result->row['note_id']);
                    $line_item['note_id'] = $note->id;
                }

                if (isset($line_item['parent_id']) && preg_match('/~newitem~/', $line_item['parent_id'])) {
                    $line_item['parent_id'] = $id_map[$line_item['parent_id']];
                }

				$input_id = $line_item['id'];
				unset($line_item['id']);
                $this->fillTaxFields($line_item);
                $this->formatCurrencyFields($line_item);

				$upd->loadInput($line_item);
				$upd->save();

				$id = $upd->getPrimaryKeyValue();
				$keep[] = $id;
				$id_map[$input_id] = $id;
            }

            ExpenseReport::delete_items($keep, $report_id);

            require_bean('ExpenseReport');
            ExpenseReport::update_project_costs($update);
        }
	}

    /**
     * Fill Expense Item tax fields
     *
     * @param array $line_item
     */
    function fillTaxFields(&$line_item) {
        $tax = array_get_default($line_item, 'total', 0) - array_get_default($line_item, 'amount', 0);
        $tax_usdollar = array_get_default($line_item, 'total_usdollar', 0) - array_get_default($line_item, 'amount_usdollar', 0);

        $line_item['tax'] = ($tax >= 0) ? $tax : 0;
        $line_item['tax_usdollar'] = ($tax_usdollar >= 0) ? $tax_usdollar : 0;
    }

    /**
     * Format currency fields for supporting different decimal separators
     *
     * @param array $line_item
     */
    function formatCurrencyFields(&$line_item) {
        $currency_fields = array('amount', 'amount_usdollar', 'total', 'total_usdollar',
            'tax_usdollar', 'paid_rate', 'paid_rate_usd');

        for ($i = 0; $i < sizeof($currency_fields); $i++) {
            $field = $currency_fields[$i];
            if (isset($line_item[$field])) {
                $line_item[$field] = format_number($line_item[$field]);
            }
        }
    }

	function renderPdf(PdfFormGenerator &$gen, RowResult &$row_result, array $parents, array $context) {
		global $mod_strings;
		$itemsArray = ExpenseReport::get_line_items($row_result->getField('id'));
		$topLevel = array();
		$subItems = array();
		foreach ($itemsArray as $item) {
			if ($item['parent_id']) $subItems[$item['parent_id']][] = $item;
			else $topLevel[] = $item;
		}
		$data = array();
		$cols = array(
			'date' => array('width' => '10%', 'title' => $mod_strings['LBL_PDF_ITEM_DATE']),
			'category' => array('width' => '15%', 'title' => $mod_strings['LBL_ITEM_CATEGORY']),
			'qty' => array('width' => '6%', 'title' => $mod_strings['LBL_ITEM_QTY']),
			'rate' => array('width' => '9%', 'title' => $mod_strings['LBL_ITEM_RATE']),
			'pretax' => array('width' => '9%', 'title' => $mod_strings['LBL_ITEM_PRETAX_ABBR']),
			'tax_code' => array('width' => '10%', 'title' => $mod_strings['LBL_ITEM_TAX']),
			'total' => array('width' => '9%', 'title' => $mod_strings['LBL_ITEM_AMOUNT']),
			'details' => array('width' => '32%', 'title' => $mod_strings['LBL_ITEM_DESCRIPTION']),
			/*
			'tx_code' => array(),
			'total' => array(),
			 */
		);
		$categories = $this->getCategories();
		$codes = AppConfig::db_all_objects('TaxCode');
		foreach ($topLevel as $item) {
			$this->renderPdfItem($item, $subItems, $data, $codes, $categories);
		}
		$opts = array(
			'border' => 0,
		);
		$gen->pdf->DrawTable($data, $cols, $mod_strings['LBL_EXPENSE_ITEMS'], true, $opts);
	}
	
	function renderPdfItem($item, $subItems, &$data, $codes, $categories, $child = false) {
		global $app_list_strings;
		$code = isset($codes[$item['tax_class_id']]) ? $codes[$item['tax_class_id']]->code : '';
		$unit = array_get_default($app_list_strings['expense_units_short_dom'], $item['unit']);
		$split = !$child && isset($subItems[$item['id']]);
		$data[] = array(
			'date' => $child ? '' : $item['date'],
			'category' => ($child ? '--' : '') . array_get_default(array_get_default($categories, $item['category'], array()), 'name'),
			'qty' => $split ? '' : ($item['quantity'] . ' ' . $unit),
			'rate' => $split ? '' : $item['paid_rate'],
			'pretax' => $item['amount'],
			'tax_code' => $code,
			'total' => $item['total'],
			'details' => $item['description'],
		);
		if ($split) {
			foreach ($subItems[$item['id']] as $si) {
				$this->renderPdfItem($si, array(), $data, $codes, $categories, true);
			}
		}
	}

}
?>
