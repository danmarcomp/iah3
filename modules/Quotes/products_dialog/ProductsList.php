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
require_once 'include/ListView/ListViewManager.php';
require_once('include/Tally/TallyUpdate.php');

class ProductsList {

    const PRODUCT_MODULE  = 'ProductCatalog';

    const PRODUCT_MODEL  = 'Product';

    const LAYOUT_NAME  = 'Simple';

    const LIST_TITLE = 'LBL_POPUP_TITLE1';

    const NO_LIMIT = -1;

    /**
     * Parent record's model name
     *
     * @var string
     */
    private $parent_model;

    /**
     * Parent record's ID
     *
     * @var string
     */
    private $parent_id;

    /**
     * @var ListViewManager
     */
    private $manager;

    /**
     *
     * @param string $parent_model
     * @param string $parent_id
     */
    public function __construct($parent_model, $parent_id) {
        $this->parent_model = $parent_model;
        $this->parent_id = $parent_id;

        if (! $this->initManager())
            ACLController::displayNoAccess();
    }

    /**
     * @return ListViewManager
     */
    public function getManager() {
        return $this->manager;
    }

    /**
     * Render Products List View
     *
     * @return void
     */
    public function render() {
        $ostyle = $this->manager->outer_style;
        $oclass = 'listViewOuter '.($this->manager->is_primary ? 'listViewPrimary ' : '') . $this->manager->outer_class;
        echo '<div id="' . $this->manager->list_id . '-outer" style="position: relative; '.$ostyle.'" class="'.$oclass.'">';

        $this->manager->renderTitle();

        $fmt =& $this->manager->getFormatter();
        $list_result = $this->getListResult();
        $fmt->formatResult($list_result);
        $this->manager->renderResult($fmt, $list_result);

        echo '</div>';

        $this->manager->addPageState();
    }

    /**
     * Initialize and set ListVIewManager
     *
     * @return bool
     */
    private function initManager() {
        //reset list record limit
        unset($_REQUEST['list_limit']);

        $manager = new ListViewManager('listview', $this->getManagerParams());
        $manager->loadRequest();

        if(! $manager->initModuleView(self::PRODUCT_MODULE, self::LAYOUT_NAME)) {
        	return false;
        }

        $fmt =& $manager->getFormatter();
        $fmt->show_additional_details = false;
        if(! $manager->initFormatter($fmt))
        	return false;

        $this->manager = $manager;

        return true;
    }

    /**
     * Get fake ListResult object (initializing  fields and rows manually)
     *
     * @return ListResult
     */
    private function getListResult() {
        $list_result = new ListResult();
        $list_result->base_model = self::PRODUCT_MODEL;
        $list_result->module_dirs = self::PRODUCT_MODULE;
        $list_result->primary_key = 'id';

        $list_result->fields = $this->getFieldDefinitions();
        $list_result->rows = $this->getParentLines();

        return $list_result;
    }

    /**
     * Get Products field definitions
     *
     * @return array
     */
    private function getFieldDefinitions() {
        $product_model = new ModelDef(self::PRODUCT_MODEL);
        $field_defs = $product_model->getFieldDefinitions();
        //hide detail link
        $field_defs['name']['type'] = 'varchar';

        return $field_defs;
    }

    /**
     * Get parent products lines
     *
     * @return array
     */
    private function getParentLines() {
        $focus_result = ListQuery::quick_fetch($this->parent_model, $this->parent_id);
        $tally = TallyUpdate::for_result($focus_result);
        $groups = $tally->getGroups();

        $rows = array();
        foreach ($groups as $key => $grp) {
            foreach ($grp['lines'] as $line_id => $line) {
                if ( ! empty($line['parent_id']) || (! empty($line['is_comment']) && $line['is_comment']) ) {
                    continue;
                }
                $rows[$line_id] = array(
                    'id' => $line['id'],
                    'name' => $line['name'],
                    'cost' => $line['cost_price'],
                    'list_price' => $line['list_price'],
                    'purchase_price' => $line['unit_price']
                );
            }

        }

        return $rows;
    }

    /**
     * Get ListViewManager configuration params
     *
     * @return array
     */
    private function getManagerParams() {
        $params = array(
            'show_create_button' => false,
            'show_mass_update' => false,
            'show_tabs' => false,
            'show_filter' => false,
            'show_help' => false,
            'custom_title_html' => translate(self::LIST_TITLE),
            'hide_navigation_controls' => true,
            'no_sort' => true,
            'default_list_limit' => self::NO_LIMIT
        );

        return $params;
    }
}
