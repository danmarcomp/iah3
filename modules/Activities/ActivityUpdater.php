<?php
class ActivityUpdater {

    /**
     * @var RowUpdate
     */
    private $activity_update;

    /**
     * @var array
     */
    private $parent_modules = array(
        'Leads', 'Accounts', 'Contacts', 'Prospects'
    );

    /**
     * @param RowUpdate $activity_update
     */
	public function __construct($activity_update) {
        $this->activity_update = $activity_update;
    }

    /**
     * Update activity's parent record
     *
     * @return void
     */
	public function update() {
       	$parent_type = $this->activity_update->getField('parent_type');
        $parent_id = $this->activity_update->getField('parent_id');

        if ( ($parent_type && $parent_id) && $this->needUpdate($parent_type) ) {
            $parent_result = ListQuery::quick_fetch(AppConfig::module_primary_bean($parent_type), $parent_id);

            if ($parent_result) {
                $parent_update = RowUpdate::for_result($parent_result);
                $parent_update->set(
                    array(
                        'last_activity_date' => gmdate('Y-m-d H:i:s'),
                        //'prohibit_workflow' => $upd->getField('prohibit_workflow')
                    )
                );

                $parent_update->prohibit_workflow = $this->activity_update->prohibit_workflow;
                $parent_update->save();
            }

        }
	}

    /**
     *
     * @param string $parent_type
     * @return bool
     */
    private function needUpdate($parent_type) {
        return in_array($parent_type, $this->parent_modules);
    }
}
?>
