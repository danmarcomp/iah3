<?php
require_once 'modules/NewStudio/wizards/StudioWizardBase.php';

class EditDropdownsWizard extends StudioWizardBase
{
	var $lang;
	var $mod;
	var $dom;
	var $perform;
	
	public function process()
	{
		foreach(array('lang', 'mod', 'dom') as $f)
			$this->$f = array_get_default($this->params, $f, '');
		if(! $this->lang)
			$this->lang = AppConfig::setting('locale.base_language');
		if(! $this->mod)
			$this->mod = 'app';
		
		$perform = array_get_default($this->params, 'dom_action');
		$data = array_get_default($this->params, 'dom_data');
		
		if($perform == 'save' && $data && $this->dom) {
			$json = getJSONObj();
			$rows = $json->decode($data);
			if(is_array($rows)) {
				$dom = array();
				foreach($rows as $r) {
					$dom[$r['key']] = $r['value'];
				}
				AppConfig::set_local("lang.lists.{$this->lang}.{$this->mod}.{$this->dom}", $dom);
				$do_save = true;
			}
		}
		
		else if($perform == 'reset' && $this->dom) {
			$languages = AppConfig::get_languages();
			foreach($languages as $lang => $_) {
				$old = AppConfig::setting("lang.lists.{$lang}.{$this->mod}.{$this->dom}", null, true);
				AppConfig::set_local("lang.lists.{$lang}.{$this->mod}.{$this->dom}", $old);
			}
		}
		
		else if($perform == 'delete' && $this->dom) {
			$languages = AppConfig::get_languages();
			foreach($languages as $lang => $_) {
				AppConfig::set_local("lang.lists.$lang.{$this->mod}.{$this->dom}", null);
			}
			$do_save = true;
			$this->dom = '';
		}
		
		else if($perform == 'create') {
			$this->perform = $perform;
			$this->dom = '';
		}
		
		else if($perform == 'reset_all') {
			$languages = AppConfig::get_languages();
			foreach($languages as $lang => $_)
				AppConfig::set_local("lang.lists.$lang", null);
			$do_save = true;
			$this->dom = '';
		}
		
		if(! empty($do_save)) {
			AppConfig::save_local('lang');
			AppConfig::cache_reset();
			AppConfig::invalidate_cache('lang');
			LanguageManager::cleanJSCache();
		}
	}


	public function render()
	{
		global $pageInstance;
		global $mod_strings;
		
		$sep = translate('LBL_SEPARATOR', 'app');
		echo get_module_title('Administration', $mod_strings['LBL_MODULE_TITLE'] . $sep . $mod_strings['LBL_EDIT_DROPDOWNS'], false);
		
		require_once('include/layout/forms/EditableForm.php');
		require_once('include/layout/forms/FormButton.php');
		$frm = new EditableForm('configform', 'ConfigForm');
		$frm->addHiddenFields(array(
			'module' => $pageInstance->module,
			'action' => $pageInstance->action,
			'wizard' => 'EditDropdowns',
		));
		$frm->addHooks(array(
			'onsubmit' => 'return SUGAR.ui.sendForm(this);',
		));
		
		$languages = AppConfig::get_languages();
		$lang_spec = array(
			'name' => 'lang',
			'type' => 'enum',
			'options' => $languages,
			'required' => true,
			'auto_submit' => true,
		);
		$sel_lang = $frm->renderSelect($lang_spec, $this->lang);
		
		$modules = array('app' => translate('LBL_APPLICATION', 'app'));
		$modinfo = AppConfig::setting("lang.index.{$this->lang}.have_lists");
		foreach ($modinfo as $mod => $have_lst) {
			if(! $have_lst || $mod == 'app') continue;
			$data = AppConfig::setting("lang.lists.{$this->lang}.$mod");
			if(isset($data) && count($data)) {
				if(! AppConfig::setting("lang.detail.{$this->lang}.$mod.lists_locked_all"))
					$modules[$mod] = array_get_default($app_list_strings['moduleList'], $mod, $mod);
			}
		}
		$mod_spec = array(
			'name' => 'mod',
			'type' => 'enum',
			'options' => $modules,
			'required' => true,
			'auto_submit' => true,
		);
		$sel_mod = $frm->renderSelect($mod_spec, $this->mod);
		
		$ret_warn = javascript_escape(translate('NTC_DROPDOWN_RESET_ALL'));
		$buttons = array(
			'return' => array(
				'icon' => 'icon-return',
				'label' => translate('LBL_RETURN'),
				'perform' => 'SUGAR.util.loadUrl("index.php?module=Administration&action=index");',
				'type' => 'button',
			),
			'reset_all' => array(
				'icon' => 'icon-delete',
				'label' => translate('LBL_DROPDOWN_RESET_ALL'),
				'perform' => 'if(confirm("'.$ret_warn.'")) return SUGAR.ui.sendForm(this.form, {dom_action:"reset_all"});',
				'type' => 'button',
			),
		);
		$button_bar = array();
		foreach($buttons as $k => $b) {
			$b['name'] = $k;
			$button_bar[] = $frm->renderFormButton(new FormButton($b));
		}

		echo $frm->open();
		
		echo implode(' ', $button_bar);
		
		echo '<table class="tabForm configForm" cellpadding="0" cellspacing="5" style="margin-top: 0.5em" width="500"><tr>';
		echo '<td class="dataField"><p class="topLabel">' . translate('LBL_LANGUAGE') . '</p>' . $sel_lang . '</td>';
		echo '<td class="dataField"><p class="topLabel">' . translate('LBL_MODULE') . '</p>' . $sel_mod . '</td>';
		echo '</tr>';
		
		if($this->lang && $this->mod) {
			$doms = array();
			$locked = AppConfig::setting("lang.detail.{$this->lang}.{$this->mod}.lists_locked", array());
			$hidden = array_merge(
				AppConfig::setting("lang.detail.{$this->lang}.{$this->mod}.lists_generated", array()),
				AppConfig::setting("lang.detail.{$this->lang}.{$this->mod}.lists_hidden", array())
			);
			$all_locked = AppConfig::setting("lang.detail.{$this->lang}.{$this->mod}.lists_locked_all");
			$lists = AppConfig::setting("lang.lists.{$this->lang}.{$this->mod}", array());
			$old_lists = AppConfig::setting("lang.lists.{$this->lang}.{$this->mod}", array(), true);
			if(! $all_locked && ! empty($lists)) {
				foreach ($lists as $key => $list) {
					if (in_array($key, $hidden)) continue;
					if($key == $this->dom)
						$sel_list_data = $list;
					$lbl = $key;
					if(! isset($old_lists[$key]) || $old_lists[$key] != $list)
						$lbl .= ' *';
					$doms[$key] = $lbl;
				}
			}
			ksort($doms);
			if($this->dom && ! isset($sel_list_data))
				$this->dom = '';
			
			if($this->perform != 'create') {
				$dom_spec = array(
					'name' => 'dom',
					'type' => 'enum',
					'options' => $doms,
					'auto_submit' => true,
					'width' => '30em',
				);
				$sel_dom = $frm->renderSelect($dom_spec, $this->dom);
			
				$create_spec = array(
					'name' => 'create_dom',
					'label' => translate('LBL_DROPDOWN_CREATE'),
					'icon' => 'icon-add',
					'perform' => 'return SUGAR.ui.sendForm(this.form, {dom_action:"create"});',
				);
				$create_btn = '&nbsp;' . $frm->renderButton($create_spec);
			}
			
			if($this->dom) {
				$old = array_get_default($old_lists, $this->dom);
				if(isset($sel_list_data) && isset($old) && $old != $sel_list_data) {
					$reset_spec = array(
						'name' => 'reset_dom',
						'label' => translate('LBL_DROPDOWN_RESET'),
						'icon' => 'icon-return',
						'perform' => 'return SUGAR.ui.sendForm(this.form, {dom_action:"reset"});',
					);
					$reset_btn = $frm->renderButton($reset_spec) . '&nbsp;';
				} else
					$reset_btn = '';

				if(! isset($old)) {
					$delete_spec = array(
						'name' => 'delete_dom',
						'label' => translate('LBL_DROPDOWN_DELETE'),
						'icon' => 'icon-delete',
						'perform' => 'return SUGAR.ui.sendForm(this.form, {dom_action:"delete"});',
					);
					$delete_btn = $frm->renderButton($delete_spec);
				} else
					$delete_btn = '';
			} else
				$reset_btn = $delete_btn = '';
			
			if($this->perform == 'create') {
				$name_spec = array(
					'name' => 'dom',
					'len' => 40,
					'required' => true,
				);
				$dom_name = $frm->renderText($name_spec, '');
				
				$cancel_spec = array(
					'name' => 'cancel_dom',
					'label' => translate('LBL_CANCEL_BUTTON_LABEL'),
					'icon' => 'icon-cancel',
					'perform' => 'return SUGAR.ui.sendForm(this.form, null, {no_validate: true});',
				);
				$cancel_btn = '&nbsp;' . $frm->renderButton($cancel_spec);
				
				echo '<tr><td class="dataField" colspan="2"><p class="topLabel">' . translate('LBL_DROPDOWN_NEW_NAME') . '<span class="requiredStar">*</span></p>' . $dom_name . $cancel_btn . '</td></tr>';
			} else {
				echo '<tr><td class="dataField" colspan="2"><p class="topLabel">' . translate('LBL_DROPDOWN') . '</p>' . $sel_dom . $create_btn . '</td></tr>';
				if($reset_btn || $delete_btn)
					echo '<tr><td class="dataField" colspan="2">'. $reset_btn . $delete_btn . '</td></tr>';
			}

			
			if($this->dom || $this->perform == 'create') {
				$dom_locked = in_array($this->dom, $locked);

				require_once('include/layout/widgets/DynamicListInput.php');
				$attrs = array(
					'name' => 'dom_data',
					'cols' => array(
						array(
							'name' => 'key',
							'label' => translate('LBL_DROPDOWN_KEY'),
							'width' => '50%',
							'editable' => ! $dom_locked,
							'size' => 30,
							'format' => 'ascii',
						), array(
							'name' => 'value',
							'label' => translate('LBL_DROPDOWN_VALUE'),
							'width' => '50%',
							'editable' => true,
							'multiline' => true,
						),
					),
					'width' => '500',
					'depth_attrib' => '',
					'show_delete' => ! $dom_locked,
				);
				$dom_data = new DynamicListInput($attrs);
				$rows = array();
				if(! empty($sel_list_data)) {
					foreach($sel_list_data as $key => $value)
						$rows[] = compact('key', 'value');
				}
				$dom_body = $dom_data->render($frm, $rows);
				
				echo '<tr><td class="dataField" colspan="2">' . $dom_body . '</td></tr>';
				
				$dom_data->exportIncludes();
				
				if(! $dom_locked) {
					$add_spec = array(
						'name' => 'add_row',
						'label' => translate('LBL_ADD_ROW'),
						'icon' => 'icon-add',
						'perform' => 'SUGAR.ui.getFormInput(this.form, "dom_data").insertRow({key: "", value: ""});',
					);
					$add_btn = $frm->renderButton($add_spec) . '&nbsp;';
				} else
					$add_btn = '';
				$save_spec = array(
					'name' => 'save_dom',
					'label' => translate('LBL_DROPDOWN_SAVE'),
					'icon' => 'icon-accept',
					'perform' => 'return SUGAR.ui.sendForm(this.form, {dom_action:"save"});',
				);
				$save_btn = $frm->renderButton($save_spec);
				
				echo '<tr><td class="dataField" colspan="2">' . "{$add_btn}{$save_btn}</td></tr>";
			}
		}
		
		echo '</table>';
		echo $frm->close();
		
		$frm->exportIncludes();
	}

}

