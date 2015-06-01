<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
/*********************************************************************************
 * The contents of this file are subject to the SugarCRM Public License Version
 * 1.1.3 ("License"); You may not use this file except in compliance with the
 * License. You may obtain a copy of the License at http://www.sugarcrm.com/SPL
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License
 * for the specific language governing rights and limitations under the
 * License.
 *
 * All copies of the Covered Code must include on each user interface screen:
 *    (i) the "Powered by SugarCRM" logo and
 *    (ii) the SugarCRM copyright notice
 * in the same form as they appear in the distribution.  See full license for
 * requirements.
 *
 * The Original Code is: SugarCRM Open Source
 * The Initial Developer of the Original Code is SugarCRM, Inc.
 * Portions created by SugarCRM are Copyright (C) 2004-2006 SugarCRM, Inc.;
 * All Rights Reserved.
 * Contributor(s): ______________________________________.
 ********************************************************************************/
/*********************************************************************************

 * Description:  TODO: To be written.
 * Portions created by SugarCRM are Copyright (C) SugarCRM, Inc.
 * All Rights Reserved.
 * Contributor(s): ______________________________________..
 ********************************************************************************/

require_once('data/SugarBean.php');

class Favorite extends SugarBean {

	var $id;
	var $deleted;
	var $created_by;
    var $date_entered;
    var $module;
    var $module_record_id;

	var $table_name = "iah_favorites";

	var $object_name = "Favorite";
	var $object_names = "Favorites";
	var $module_dir = "Favorites";

    /**
     * Fields for ListView
     *
     * @return array
     */
    public static function get_list_fields() {
        $fields = array(
            'favorite' => array(
                'type' => 'ref',
                'bean_name' => 'Favorite',
                'name' => 'favorite',
                'source' => array('type' => 'field', 'field' => 'favorites.deleted', 'alias_field' => 'favorites.deleted'),
                'module_dir' => 'Favorites',
            ),
            'favorites.deleted' => array(
                'type' => 'bool',
                'name' => 'deleted',
                'source' => array('type' => 'db') ,
                'no_query' => true,
                'link' => 'favorites',
            )
        );

        return $fields;
    }

    /**
     * @return ModelDef
     */
    public static function get_model_def() {
        $link_model = new ModelDef('Favorite');
        $link_model->table_alias = 'favorites';
        return $link_model;
    }

    /**
     * Add Favourites join to query
     *
     * @param ListQuery $query
     */
    public static function add_join(ListQuery &$query) {
        require_once('modules/Favorites/Favorite.php');
        $fields = self::get_list_fields();

        foreach ($fields as $name => $spec) {
            $query->fields[$name] = $spec;
        }

        $query->links['favorites'] = Favorite::get_model_def();
        $query->join_favorites = true;
    }

    /**
     * Get favorites add/remove star link
     *
     * @param string $module
     * @param string $record_id
     * @param string $list_id
     * @param string $deleted
     * @return string
     */
    public static function get_star($module, $record_id, $list_id, $deleted) {
        global $app_strings;

        $title = $app_strings['LBL_ADD_TO_FAVORITES'];
        $class = '';

        if ($deleted === '0') {
            $title = $app_strings['LBL_REMOVE_FROM_FAVORITES'];
            $class = ' active';
        }

        if ($list_id) {
            $onclick = 'sListView.addToFavorites(\'' . $list_id . '\', this, \''.$module.'\', \''.$record_id.'\');';
        } else {
            $onclick = 'SUGAR.ui.addToFavorites(this, \''.$module.'\', \''.$record_id.'\');';
        }

        $star_link = '<div class="input-icon active-icon icon-star'.$class.'" onclick="'.$onclick.'" title="'.$title.'"></div>';

        return $star_link;
    }

    /**
     * Add or remove module's record to/from Favorites
     *
     * @param string $record_id
     * @param string $record_module
     */
    public function addOrRemoveNew($record_id, $record_module) {
        $result = $this->load($record_id, $record_module);

        if (! $result->failed) {
            $update = RowUpdate::for_result($result);

            if ($update->getField('deleted')) {
                $this->currentUnDelete($update);
            } else {
                $this->currentDelete($update);
            }

        } else {
            $this->add($record_id, $record_module);
        }


    }

    /**
     * @param string $record_id
     * @param string $record_module
     * @return RowResult
     */
    private function load($record_id, $record_module) {
        $lq = new ListQuery($this->object_name);

        $clauses = array(
            "module" => array(
                "value" => $record_module,
                "field" => 'module'
            ),
            "record" => array(
                "value" => $record_id,
                "field" => 'module_record_id'
            ),
            "user" => array(
                "value" => AppConfig::current_user_id(),
                "field" => 'created_by'
            )
        );

        $lq->addFilterClauses($clauses);
        $lq->filter_deleted = false;

        return $lq->runQuerySingle();
    }

    /**
     * @param string $record_id
     * @param string $record_module
     */
    private function add($record_id, $record_module) {
        $update = RowUpdate::blank_for_model($this->object_name);

        $update->set(
            array(
                'module' => $record_module,
                'module_record_id' => $record_id
            )
        );

        $update->save();
    }

    /**
     * @param RowUpdate $update
     */
    private function currentDelete(RowUpdate $update) {
        $update->markDeleted();
    }

    /**
     * @param RowUpdate $update
     */
    private function currentUnDelete(RowUpdate $update) {
        $update->markDeleted(false);
    }
}
?>
