<?php
/**
 * Nextcloud - spreedme
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Leon <leon@struktur.de>
 * @copyright struktur AG 2016
 */

namespace OCA\SpreedME\User;

use OCA\SpreedME\Errors\ErrorCodes;
use OCA\SpreedME\Security\Security;

class User {

	private $user;

	public function __construct() {
		$this->user = \OC::$server->getUserSession()->getUser();
	}

	public function requireLogin() {
		if ($this->user === null) {
			throw new \Exception('Not logged in', ErrorCodes::NOT_LOGGED_IN);
		}
	}

	public function getInfo() {
		return array(
			'id' => $this->getUserId(),
			'display_name' => $this->getDisplayName(),
			'is_admin' => $this->isAdmin(),
			'is_spreedme_admin' => $this->isSpreedMeAdmin(),
		);
	}

	private function getUserId() {
		$this->requireLogin();

		return $this->user->getUID();
	}

	private function getUID() {
		$this->requireLogin();

		return $this->user->getUID();
	}

	private function getDisplayName() {
		$this->requireLogin();

		return $this->user->getDisplayName();
	}

	private function getGroups() {
		$this->requireLogin();

		// TODO(leon): This looks like a private API.
		return \OC_Group::getUserGroups($this->getUserId());
	}

	private function getAdministeredGroups() {
		$this->requireLogin();

		if (class_exists('\OC_SubAdmin', true)) {
			return \OC_SubAdmin::getSubAdminsGroups($this->getUserId());
		}
		// Nextcloud 9
		$subadmin = new \OC\SubAdmin(
			\OC::$server->getUserManager(),
			\OC::$server->getGroupManager(),
			\OC::$server->getDatabaseConnection()
		);
		$ocgroups = $subadmin->getSubAdminsGroups($this->user);
		$groups = array();
		foreach ($ocgroups as $ocgroup) {
			$groups[] = $ocgroup->getGID();
		}
		return $groups;
	}

	private function isAdmin() {
		$groups = $this->getGroups();

		return in_array('admin', $groups, true);
	}

	private function isSpreedMeGroupAdmin() {
		$groups = $this->getAdministeredGroups();

		return in_array('Spreed.ME', $groups, true);
	}

	public function isSpreedMeAdmin() {
		return $this->isAdmin() || $this->isSpreedMeGroupAdmin();
	}

	public function getSignedCombo() {
		$id = $this->getUserId();
		return Security::getSignedCombo($id);
	}

}
