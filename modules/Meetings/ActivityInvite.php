<?php
class ActivityInvite {

    /**
     * @var array
     */
    private $ids_list;

    /**
     * @var null|string
     */
    private $main_model;

    /**
     *
     * @param string $main_model_name
     * @param array $ids_list - list of invitee participants IDs:
     *['user_invitees'], ['contact_invitees'], ['resource_invitees']
     */
    public function __construct($main_model_name = null, $ids_list = null) {
        $this->main_model = $main_model_name;
    	if(! is_array($ids_list)) $ids_list = array();
        $this->ids_list = $ids_list;
    }

    /**
     * Get invitees list
     *
     * @param RowResult $row_result
     * @return array: ['id'], ['_display'], ['module']
     */
    public function getList(RowResult $row_result) {
        $invitees = array();
        $to_load = array();

        if (! empty($this->ids_list['user_invitees']) || ! empty($this->ids_list['contact_invitees']) || ! empty($this->ids_list['resource_invitees'])) {
            $this->fillInviteesByIds($invitees);
        } elseif (! $row_result->new_record){
            $this->fillInvitees($row_result->getField('id'), $invitees);
        } else {
        	if(! empty($_REQUEST['contact_ids'])) {
				$to_load['Contacts'] = array_map('trim', explode(',', $_REQUEST['contact_ids']));
			} else if ($row_result->getField('parent_type') == 'Contacts' && $row_result->getField('parent_id')) {
				$to_load['Contacts'] = array($row_result->getField('parent_id'));
			} elseif ($row_result->getField('parent_type') == 'Leads') {
				$to_load['Contacts'] = array($this->getLeadContactId($row_result->getField('parent_id')));
			}
			
			if(! empty($_REQUEST['user_ids'])) {
				$to_load['Users'] = array_map('trim', explode(',', $_REQUEST['user_ids']));
			}
			
			if(! empty($_REQUEST['resource_ids'])) {
				$to_load['Resources'] = array_map('trim', explode(',', $_REQUEST['resource_ids']));
			}
		}

        if ($to_load) {
        	foreach($to_load as $mod => $items) {
        		foreach($items as $item) {
        			if(! $item) continue;
					$targ = $this->loadParticipant($mod, $item);
					if ($targ) $invitees[] = $targ;
				}
			}
        }

        return $invitees;
    }
    
    public static function update_links(RowUpdate $upd, $links) {
        $inv = new ActivityInvite($upd->model_name);
        return $inv->updateLinks($upd, $links);
    }
    
    public function updateLinks(RowUpdate $upd, $ids_list=null) {
        if (! $upd->new_record && is_array($ids_list))
            $this->removeLinks($upd);
        if($upd->new_record || is_array($ids_list)) {
        	if(is_array($ids_list)) $this->ids_list = $ids_list;
			return $this->linkAll($upd);
		}
    }

    /**
     * Link invitees with main model
     *
     * @param RowUpdate $update
     * @return bool
     */
    public function linkAll(RowUpdate &$update) {
        $this->addStandardParticipants($update);
        $participants = array('user' => 'users', 'contact' => 'contacts', 'resource' => 'resources');
        $linked = false;

        foreach ($participants as $name => $link) {
			$invitees = $this->formatInvites($name);
			if($invitees) {
				foreach($invitees as $i) {
					if($name == 'user' && $i == AppConfig::current_user_id()) {
						$i = array('id' => $i, 'accept_status' => 'accept');
					}
					$update->addUpdateLink($link, $i);
				}
                $linked = true;
            }
        }

        return $linked;
    }

    /**
     * Remove invitees links
     *
     * @param RowUpdate $upd
     */
    public function removeLinks(RowUpdate &$upd) {
        $upd->removeAllLinks('users');
        $upd->removeAllLinks('contacts');
        $upd->removeAllLinks('resources');
    }

    /**
     * Update users vcal data
     *
     * @static
     * @param array $user_ids
     */
    public static function update_vcals(array $user_ids) {
    	foreach($user_ids as $id) {
    		self::update_vcal($id);
    	}
    }

    /**
     * Update user vcal data
     *
     * @static
     * @param string $user_id
     */
    public static function update_vcal($user_id) {
    	require_once('modules/vCals/vCal.php');
		vCal::clear_sugar_vcal($user_id);
    }

    /**
     * @static
     * @param RowUpdate $upd
     */
    public static function handle_delete(RowUpdate &$upd) {
    	$model = $upd->getModelName();
    	$id = $upd->getField('id');
    	if($model && $id) {
			$lq = new ListQuery($model, array('id'), array('link_name' => 'users', 'parent_key' => $id));
			$ids = array();
			foreach($lq->fetchAllRows() as $invite) {
				$ids[] = $invite['id'];
			}
			self::update_vcals($ids);
		}
    }

    /**
     * Fill invitee participants
     *
     * @param string $record_id
     * @param array $invitees
     */
    private function fillInvitees($record_id, &$invitees) {
    	if(! $record_id) return;
        $links = array('users', 'contacts');
        if($this->main_model == 'Meeting')
            $links[] = 'resources';

        for ($i = 0; $i < sizeof($links); $i++) {
            $lq = new ListQuery($this->main_model, array('id', '_display'), array('link_name' => $links[$i], 'parent_key' => $record_id));

            foreach($lq->fetchAllRows() as $invite) {
                $invite['module'] = ucfirst($links[$i]);
                $invitees[] = $invite;
            }
        }
    }

    /**
     * Fill invite participants from user input
     *
     * @param array $invitees
     */
    private function fillInviteesByIds(&$invitees) {
        $participants = array('user' => 'Users', 'contact' => 'Contacts');
        if($this->main_model == 'Meeting')
            $participants['resource'] = 'Resources';

        foreach ($participants as $name => $module) {
			$ids = $this->formatInvites($name);
			foreach($ids as $id) {
				$invite = $this->loadParticipant($module, $id);
				if ($invite)
					$invitees[]= $invite;
			}
        }
    }

    /**
     * Load participant's data
     *
     * @param string $module
     * @param string $id
     * @return array|null
     */
    private function loadParticipant($module, $id) {
    	if(! $id) return null;
        $participant = ListQuery::quick_fetch_row(AppConfig::module_primary_bean($module), $id, array('id', '_display'));

        if ($participant) {
            $participant['module'] = $module;
            return $participant;
        } else {
            return null;
        }
    }

    /**
     * Get Lead's converted contact id
     *
     * @param string $lead_id
     * @return null|string
     */
    private function getLeadContactId($lead_id) {
        $lead = ListQuery::quick_fetch_row('Lead', $lead_id, array('contact_id'));
        if ($lead && ! empty($lead['contact_id']))
            return $lead['contact_id'];
        return null;
    }

    /**
     * Add standard invitee participants (assigned user, related contact)
     *
     * @param RowUpdate $update
     */
    private function addStandardParticipants(RowUpdate $update) {
        if (! is_array($this->ids_list))
            $this->ids_list = array();
        if(empty($this->ids_list['user_invitees'])) {
            $this->ids_list['user_invitees'] = $update->getField('assigned_user_id');
        }

        $parent_type = $update->getField('parent_type');

        if ($parent_type == 'Contacts') {
            if (! empty($this->ids_list['contact_invitees'])) {
                $this->ids_list['contact_invitees'] .= ','.$update->getField('parent_id');
            } else {
                $this->ids_list['contact_invitees'] = $update->getField('parent_id');
            }
        }
    }

    /**
     * Set accept status for current user
     *
     * @param RowUpdate $upd
     */
    private function acceptInviteForCurrentUser(RowUpdate $upd) {
        $user = ListQuery::quick_fetch('User', AppConfig::current_user_id());
        if ($user) {
            require_bean('Meeting');
            Meeting::set_invite_status($upd, $user, 'accept');
        }
    }

    /**
     * Format invites ids string and explode
     *
     * @static
     * @param string $category
     * @return array|mixed
     */
    private function formatInvites($category) {
		$inv = array_get_default($this->ids_list, $category . '_invitees', '');
        $formatted_invitees = preg_replace('/(\s|\,$)/', '', $inv);
        $formatted_invitees = explode(',', $formatted_invitees);
        return $formatted_invitees;
    }
}
?>