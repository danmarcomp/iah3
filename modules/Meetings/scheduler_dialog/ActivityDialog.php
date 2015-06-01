<?php
require_once('include/layout/forms/FormGenerator.php');

class ActivityDialog {

    /**
     * @var ModelDef
     */
    var $model;

    /**
     * @var string
     */
    var $bean_name;

    /**
     * @var HtmlFormGenerator
     */
    var $form;

    /**
     * @var string
     */
    var $record_id;

    /**
     * @var RowResult
     */
    var $record;

    /**
     * @var string
     */
    var $duplicate_of_id;

	var $parent_type;
	var $parent_id;

    /**
     * @var string (save, duplicate, delete)
     */
    var $perform;

    const DEFAULT_START_TIME = '09:00';
    const DEFAULT_END_TIME = '18:00';
    const DEFAULT_MODULE = 'Meetings';

    /**
     * @param string $model_name
     */
    function __construct($model_name) {
        $this->init($model_name);
    }

    /**
     * @param string $model_name
     * @return void
     */
    function init($model_name) {
        $this->setModel($model_name);
        $this->loadRequest();
        $this->initForm();
        $this->loadRecord();
    }

    /**
     * Set dialog model
     *
     * @param  string $bean_name (model name)
     * @return void
     */
    function setModel($bean_name) {
        $this->bean_name = $bean_name;
        $this->model = new ModelDef($bean_name);        
    }

    /**
     * Load main params from request
     *
     * @return void
     */
    function loadRequest() {
        if(empty($this->record_id)) {
            $record_id = array_get_default($_REQUEST, 'record');
            $this->record_id = $record_id;
        }

        if(empty($this->perform)) {
            $perform = array_get_default($_REQUEST, 'record_perform', 'edit');
            $this->perform = $perform;
        }

        if(! empty($_REQUEST['duplicate_of_id']))
			$this->duplicate_of_id = $_REQUEST['duplicate_of_id'];

		if (!empty($_REQUEST['parent_type']))
			$this->parent_type = $_REQUEST['parent_type'];
		if (!empty($_REQUEST['parent_id']))
			$this->parent_id = $_REQUEST['parent_id'];
    }

    /**
     * Initialize dialog form object
     *
     * @return void
     */
    function initForm() {
        $module = 'Meetings';
        $js_editor = 'MeetingsEditView';
        $action = 'activityEditView';
        
        if ($this->bean_name == 'BookedHours') {
            $module = 'Booking';
            $js_editor = 'HoursEditView';
            $action = 'bookingEditView';
        }

        $layout = new FormLayout();
        $layout->addFormHooks(array('onsubmit' => "return $js_editor.save();"), false);
        $hidden = array('module' => $module, 'action' => $action, 'record' => $this->record_id);

        if($this->perform == 'duplicate') {
            $hidden['record'] = '';
            $hidden['duplicate_of_id'] = $this->record_id;
        } else if(! empty($this->duplicate_of_id)) {
            $hidden['duplicate_of_id'] = $this->duplicate_of_id;
        }

        $layout->addFormHiddenFields($hidden, false);
        $layout->addFormButtons($this->getFormButtons());

        $tabs = array(
            array('label' => 'LBL_SCHEDULE_MEETING', 'name' => 'Meeting', 'perform' => "MeetingsEditView.switchTab('Meeting')"),
            array('label' => 'LBL_SCHEDULE_CALL', 'name' => 'Call', 'perform' => "MeetingsEditView.switchTab('Call')"),
            //array('label' => 'LBL_BOOKING_TAB_LABEL', 'name' => 'Booking')
        );

		if($module != 'Booking') {
			$layout->addTabs($tabs);
			$layout->setCurrentTab($this->getCurrentTab());
		}

        $layout_def = array('type' => 'editview', 'sections' => array($this->getWidget()));
        $layout->init($layout_def, $this->model);

        $this->form = FormGenerator::new_form('html', $this->model, $layout, 'asyncEditView');
        $this->form->setPerform($this->perform);
    }

    /**
     * Load record (bean) object
     *
     * @throws IAHRecordLoadError
     * @return void
     */
    function loadRecord() {
        if($this->record_id)
            $result = $this->form->getRowResult($this->record_id);
        else
            $result = $this->form->getBlankResult();

        if($result->failed)
            throw new IAHRecordLoadError($result->error_msg);

        $this->record = $result;
    }

    /**
     * Save (insert or update), delete record or
     * execute init hook (load some data from request)
     *
     * @return void
     */
    function performUpdate() {
        $perf = $this->perform;

        if($perf == 'edit' || $perf == 'save' || $perf == 'delete') {
            require_once('include/database/RowUpdate.php');

            $upd = RowUpdate::for_result($this->record);
			$upd->loadRequest();

			require_once('include/layout/FieldFormatter.php');
			$fmt = new FieldFormatter('html', 'editview');
			$origDate = array_get_default($_REQUEST, 'date_start');
			$input = $fmt->unformatRow($upd->fields, $_REQUEST);
			if (array_get_default($_REQUEST, 'date_is_gmt'))
				$input['date_start']  = $origDate;
            $this->form->loadUpdateRequest($upd, $input);

            if ($upd->new_record && $perf == 'edit') {
				$this->model->execSpecialHook('init_dialog', array(&$upd, $input));
                $this->record->secondary_queries = true;
            }

            if ($perf == 'delete') {
                if(! $upd->markDeleted())
                    pr2('delete failed');
				else
					return true;
            } elseif ($perf == 'save') {
                $this->form->beforeUpdate($upd);
                if(! $upd->new_record) $act = 'update';
                else $act = 'insert';

                if($upd->save($act)) {
                    $this->record_id = $upd->getPrimaryKeyValue();
                    $this->form->afterUpdate($upd);
                } else {
                    pr2('error saving');
                    $this->perform = $perf = 'edit';
                }
            }

            if($perf != 'save')
				$upd->updateResult($this->record);
			else
				return true;
		}
    }

    /**
     * Render dialog body
     *
     * @return string
     */
    function render() {
        $this->form->renderForm($this->record);
        $result = $this->form->getResult();
        $this->form->exportIncludes();

        return $result;
    }

    /**
     * Get widget object by bean name (model name)
     *
     * @return array
     */
    function getWidget() {
        switch ($this->bean_name) {
            case "BookedHours":
                $widget = array('id' => 'booking_dialog', 'widget' => 'BookingDialogWidget');
                break;
            case "Call":
            case "Meeting":
            default:
                $widget = array('id' => 'meeting_dialog', 'widget' => 'MeetingDialogWidget');
                break;
        }

        return $widget;
    }

    /**
     * Get selected tab name
     *
     * @return string
     */
    function getCurrentTab() {
        switch ($this->bean_name) {
            case "BookedHours":
                $tab = 'Booking';
                break;
            case "Call":
                $tab = 'Call';
                break;
            case "Meeting":
            default:
                $tab = 'Meeting';
                break;
        }

        return $tab;
    }

    /**
     * Get dialog form buttons
     *
     * @return array
     */
    function getFormButtons() {
        $js_editor = 'MeetingsEditView';
        if ($this->bean_name == 'BookedHours') {
            $js_editor = 'HoursEditView';
        }

        $buttons = array(
            'btn_mtg_save' => array(
            	'icon' => 'icon-accept',
                'vname' => 'LBL_SAVE_BUTTON_LABEL',
                'title' => 'LBL_SAVE_BUTTON_TITLE',
                'accesskey' => 'LBL_SAVE_BUTTON_KEY',
                'type' => 'submit',
                'onclick' => "return $js_editor.save();",
                'order' => 1
            ),
            'cancel' => array(
            	'icon' => 'icon-cancel',
                'vname' => 'LBL_CANCEL_BUTTON_LABEL',
                'title' => 'LBL_CANCEL_BUTTON_TITLE',
                'accesskey' => 'LBL_CANCEL_BUTTON_KEY',
                'type' => 'button',
                'onclick' => "$js_editor.hide();",
                'order' => 5
            ),
        );

        if (! empty($this->record_id) && $this->perform != 'duplicate') {
            $buttons['duplicate'] = array(
            	'icon' => 'icon-duplicate',
                'vname' => 'LBL_DUPLICATE_BUTTON_LABEL',
                'title' => 'LBL_DUPLICATE_BUTTON_TITLE',
                'accesskey' => 'LBL_DUPLICATE_BUTTON_KEY',
                'type' => 'button',
                'onclick' => "$js_editor.duplicate();",
                'order' => 3
            );
            $buttons['delete'] = array(
            	'icon' => 'icon-delete',
                'vname' => 'LBL_DELETE_BUTTON_LABEL',
                'title' => 'LBL_DELETE_BUTTON_TITLE',
                'accesskey' => 'LBL_DELETE_BUTTON_KEY',
                'type' => 'button',
                'onclick' => "$js_editor.deleteActivity();",
                'order' => 4
            );
        }

        if ($this->bean_name == 'Meeting') {
            global $app_strings;

            $onclick = 'return SUGAR.popups.openUrl("index.php?module=Recurrence&action=Popup'
                . '&form=asyncEditView&form_submit=false&parent_type='
                . $this->model->module_dir . '&parent_id=' . $this->record_id
                . '&recur_rules="+this.form.recurrence_rules.value,'
                . 'null, {width: "450px", title_text: "'.$app_strings['LBL_RECURRENCE_EDIT'].'"});';
            $onclick = 'MeetingsEditView.toggleButtons();' . $onclick;

            $buttons['recurrence'] = array(
                'vname' => 'LBL_RECUR_BUTTON_LABEL',
                'title' => 'LBL_RECUR_BUTTON_TITLE',
                'accesskey' => 'LBL_RECUR_BUTTON_KEY',
                'icon' => 'icon-recur',
                'type' => 'button',
                'onclick' => $onclick,
                'order' => 2
            );
        }

		$params = array('primary_name' => 'id', 'primary_value' => $this->record_id, 'module' => $this->model->module_dir, 'return_module' => $this->parent_type, 'return_record' => $this->parent_id);
        $json = getJSONobj();
        $params = $json->encode($params);

        $buttons['full_form'] = array(
            'type' => 'button',
            'vname' => 'LBL_FULL_FORM_BUTTON_LABEL',
            'order' => 80,
            'icon' => 'icon-popup',
            'onclick' => 'return SUGAR.ui.loadFullForm({FORM}, '.$params.');',
            'align' => 'right',
        );

        return $buttons;
    }
}
?>
