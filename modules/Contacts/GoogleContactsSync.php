<?php

require_once 'include/upload_file.php';
require_once 'include/Localization/Localization.php';
require_once 'include/GoogleSync.php';
require_once 'modules/Contacts/Contact.php';

class GoogleContactsSync extends GoogleSync
{
	protected $locale;

	public function __construct($user = null)
	{
		parent::__construct($user);
		$this->locale = new Localization($this->user);
		$this->login = new Zend_Gdata_ClientLogin;
	}


	protected function getConfigKey()
	{
		return 'google_contacts';
	}

	protected function getService()
	{
		return 'cp';
	}

	protected function getFeedSource()
	{
		$username = $this->user->getPreference('google_calendar_user');
		return new Zend_Gdata_Contacts($username, $this->client);
	}

	protected function getFeed($query)
	{
	}

	protected function getQuery()
	{
	}

	protected function createIAHItem($sql_row = null)
	{
		$id = null;
		if (is_array($sql_row))
			$id = array_get_default($sql_row, 'id');
		if ($id) {
			$lq = new ListQuery('Contact', true);
			$lq->addField('primary_account');
			$row = $lq->queryRecord($id);
			if (!$row || $row->failed)
				$row = null;
			if ($row)
				return RowUpdate::for_result($row);
		}
		return RowUpdate::blank_for_model('Contact');
	}

	protected function setIAHItemFields($iahItem, $item)
	{
	}

	protected function getSQLQuery($lastSyncTime)
	{
		global $db;
		$all = $this->getPreference('which');

		$query = "SELECT c.id FROM contacts c "
			." LEFT JOIN google_sync s ON s.related_id=c.id AND s.user_id='".$this->user->id . "' AND s.related_type='Contacts'"
			." WHERE (IFNULL(s.google_id,'')!='' OR NOT c.deleted) ";
		$query .= " AND (c.date_modified > s.google_last_sync OR s.google_last_sync IS NULL)";
		if ($all) {
			if (ACLAction::userNeedsOwnership($this->user->id, 'Contacts', 'view','module')) {
				$level = ACLAction::getUserActions($this->user->id, false, 'Contacts', 'module', 'view');
				$c = new Contact;
				$owner_where = $c->getOwnerWhere($this->user->id, $level['aclaccess'], 'c');
				$query .= ' AND ' . $owner_where;
			}
		} else {
			$query .= " AND c.assigned_user_id='" . $db->quote($this->user->id) . "'";
		}
		$query .= ' ORDER BY c.date_modified ';
		$query .= ' LIMIT ' . (int)array_get_default($this->params, 'max_docs', 50);
		return $query;
	}

	protected function realCreateGoogleItem($iahItem)
	{
		$item = new Zend_Gdata_Contacts_ContactListEntry();
		$this->createOrUpdateGoogleContact($iahItem, $item);
		try {
			$item = $this->feedSource->insertContact($item);
		} catch (Zend_Gdata_App_HttpException $e) {
			$r = $e->getResponse();
			if (empty($r)) {
				throw $e;
			}
			if ($r->getStatus() == 409) {
				$iahItem->_google_fields['google_sync_error'] = 1;
				$iahItem->_google_fields['google_last_sync'] = '1970-01-01 00:00:00';
			} else {
				throw $e;
			}
		}
		return $item;
	}
	
	protected function realUpdateGoogleItem($item, $iahItem)
	{
		$this->createOrUpdateGoogleContact($iahItem, $item);
		return $item;
	}

	protected function getListEntry($googleId)
	{
		return $this->feedSource->getContactListEntry($googleId);
	}

	protected function canCreateInIAH($item, $iahItem)
	{
		return false;
	}

	protected function createOrUpdateGoogleContact($iahContact, $contact)
	{
		$peer = new GoogleContactPeer($contact);
		$peer->setTitle($this->locale->getLocaleFormattedName($iahContact->getField('first_name'), $iahContact->getField('last_name')));
		$contact->setContent(new Zend_Gdata_App_Extension_Content($iahContact->getField('description')));
		if (strlen($iahContact->getField('email1'))) {
			$peer->setEmail($iahContact->getField('email1'), Zend_Gdata_Contacts_Extension_Email::WORK, "true");
		}
		if (strlen($iahContact->getField('email2'))) {
			$peer->setEmail($iahContact->getField('email2'), Zend_Gdata_Contacts_Extension_Email::HOME, "false");
		}

		$row = ListQuery::quick_fetch('Contact', $iahContact->getField('id'));

		$address = $this->locale->getLocaleBeanFormattedAddress($row, 'primary_', 'display_raw');
		if (!empty($address)) {
			$peer->setAddress($address, Zend_Gdata_Contacts_Extension_PostalAddress::WORK, 'true');
		}

		$address = $this->locale->getLocaleBeanFormattedAddress($row, 'alt_', 'display_raw');
		if (!empty($address)) {
			$peer->setAddress($address, Zend_Gdata_Contacts_Extension_PostalAddress::HOME, 'false');
		}

		$phone_fields = array(
			'phone_work' => Zend_Gdata_Contacts_Extension_PhoneNumber::WORK,
			'phone_home' => Zend_Gdata_Contacts_Extension_PhoneNumber::HOME,
			'phone_mobile' => Zend_Gdata_Contacts_Extension_PhoneNumber::MOBILE,
			'phone_other' => Zend_Gdata_Contacts_Extension_PhoneNumber::OTHER,
			'phone_fax' => Zend_Gdata_Contacts_Extension_PhoneNumber::WORK_FAX,
		);

		$i = 0;
		foreach ($phone_fields as $f => $type) {
			$primary = ($i ? 'false' : 'true');
			$peer->setPhone($iahContact->getField($f), $type, $primary);
			$i++;
		}

		$peer->setOrgTitle($iahContact->getField('title'));
		$peer->setOrgName($iahContact->getField('primary_account'));

	}

	protected function saveItem($item, $iahItem)
	{
		try {
			parent::saveItem($item, $iahItem);
		} catch (Zend_Gdata_App_HttpException $e) {
			$r = $e->getResponse();
			if (empty($r)) {
				throw $e;
			}
			if ($r->getStatus() == 409) {
				$iahItem->_google_fields['google_sync_error'] = 1;
				$iahItem->_google_fields['google_last_sync'] = '1970-01-01 00:00:00';
			} else {
				throw $e;
			}
		}
	}

	protected function googleIsNewer($item, $iahItem)
	{
		return false;
	}
}

class GoogleContactPeer/*{{{*/
{
	protected $_contact;

	public function __construct($contact)
	{
		$this->_contact = $contact;
	}

	public function setTitle($name)
	{
		$this->_contact->setTitle(new Zend_Gdata_App_Extension_Title($name));
	}

	public function setEmail($address, $type, $primary)
	{
		$found = false;
		if (!empty($this->_contact->email)) {
			$emails = $this->_contact->email;
			foreach ($emails as $email) {
				if ($email->rel == $type) {
					$found = true;
					$element = $email;
					break;
				}
			}
		}
		if (!$found) {
			$element = new Zend_Gdata_Contacts_Extension_Email;
		}
		$element->rel = $type;
		$element->primary = $primary;
		$element->address = $address;
		if (!$found) {
			$emails = $this->_contact->email;
			$emails[] = $element;
			$this->_contact->email = $emails;
		}
	}

	public function setAddress($address, $type, $primary)
	{
		$found = false;
		if (!empty($this->_contact->address)) {
			$addresses = $this->_contact->address;
			foreach ($addresses as $addr) {
				if ($addr->rel == $type) {
					$found = true;
					$element = $addr;
					break;
				}
			}
		}
		if (!$found) {
			$element = new Zend_Gdata_Contacts_Extension_PostalAddress;
		}
		$element->rel = $type;
		$element->primary = $primary;
		$element->text = $address;
		if (!$found) {
			$addresses = $this->_contact->address;
			$addresses[] = $element;
			$this->_contact->address = $addresses;
		}
	}

	public function setOrgTitle($title)
	{
		if (empty($this->_contact->organization)) {
			$this->_contact->organization = new Zend_Gdata_Contacts_Extension_Organization;
		}
		$this->_contact->organization->orgTitle = $title;
	}

	public function setOrgName($name)
	{
		if (empty($this->_contact->organization)) {
			$this->_contact->organization = new Zend_Gdata_Contacts_Extension_Organization;
		}
		$this->_contact->organization->orgName = $name;
	}
	
	public function setPhone($number, $type, $primary)
	{
		$remove = empty($number);
		$found = false;
		if (!empty($this->_contact->phone)) {
			$phones = $this->_contact->phone;
			foreach ($phones as $i => $phone) {
				if ($phone->rel == $type) {
					if ($remove) {
						unset($this->_contact->phone[$i]);
						return;
					}
					$found = true;
					$element = $phone;
					break;
				}
			}
		}
		if ($remove) {
			return;
		}
		if (!$found) {
			$element = new Zend_Gdata_Contacts_Extension_PhoneNumber;
		}
		$element->rel = $type;
		$element->primary = $primary;
		$element->text = $number;
		if (!$found) {
			$phones = $this->_contact->phone;
			$phones[] = $element;
			$this->_contact->phone = $phones;
		}
	}


}/*}}}*/

